<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Support\DocumentNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Hotel-only. This IS the Orders page for the hotel (the generic
// "All orders" item is hidden for it via 'excludeTypes'). A
// reservation is an ordinary Order with one line item — quantity =
// nights, unit price = the room's nightly rate — and the stay dates
// in rental_start_at / rental_end_at. So payments and receipts need
// nothing hotel-specific: they work on any Order.
//
// check_out is the morning the guest leaves, so a stay occupies the
// nights [check_in, check_out). Two stays clash only when
// existingIn < newOut AND existingOut > newIn — which lets one guest
// check out the same day the next checks in.
//
// One room per reservation. A group booking is several reservations
// (each with its own receipt), which keeps every room's history clean.
//
// Edit and Cancel are only offered while NO payment exists (voided
// receipts don't count — the payment row remains). Refunds aren't
// modelled, so once money is on the order it can't be unwound here.
// Cancel marks the order 'void' (never deletes) which frees the room
// and removes it from revenue.
class ReservationPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'hotel', 404);

        $reservations = Order::where('company_id', $company->id)
            ->whereNotNull('rental_start_at')
            ->with(['contact', 'items.product', 'payments'])
            ->orderByDesc('rental_start_at')
            ->get();

        $rooms = Product::where('company_id', $company->id)
            ->where('attributes->kind', HotelRoomPageController::KIND)
            ->orderBy('sku')
            ->get()
            ->map(fn($room) => [
                'id' => $room->id,
                'label' => $room->name,
                'rate' => (float) $room->unit_price,
                'capacity' => $room->attributes['capacity'] ?? null,
                'bookable' => ($room->attributes['room_status'] ?? 'available') === 'available',
            ])
            ->values();

        return view('company.reservations.index', [
            'company' => $company,
            'reservations' => $reservations,
            'rooms' => $rooms,
            'contacts' => Contact::where('company_id', $company->id)
                ->where('type', 'customer')
                ->orderBy('name')
                ->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreReservationRequest $request, Company $company)
    {
        abort_unless($company->type === 'hotel', 404);
        $this->assertContactBelongsToCompany($request, $company);

        $order = DB::transaction(function () use ($request, $company) {
            $room = $this->lockBookableRoom($company, $request->validated('room_id'));
            [$in, $out, $nights] = $this->stay($request);

            $this->assertRoomFree($company, $room, $in, $out);

            $contactId = $this->resolveContactId($request, $company);
            $total = $nights * (float) $room->unit_price;

            $order = Order::create([
                'company_id' => $company->id,
                'contact_id' => $contactId,
                'order_number' => DocumentNumber::order($company->name),
                'total_amount' => $total,
                'created_by' => Auth::id(),
                'rental_start_at' => $in,
                'rental_end_at' => $out,
            ]);

            $order->items()->create($this->itemPayload($room, $in, $out, $nights));

            return $order;
        });

        return redirect()->route('company.reservations.index', ['company' => $company->slug])
            ->with('status', "Reservation {$order->order_number} created. Record payment on the Payments page.");
    }

    public function update(StoreReservationRequest $request, Company $company, Order $order)
    {
        abort_unless($company->type === 'hotel' && $order->company_id === $company->id, 404);
        abort_if($order->status === 'void', 422, 'A cancelled reservation cannot be edited.');
        abort_if($order->payments()->exists(), 422, 'Cannot edit a reservation that already has payments logged.');
        $this->assertContactBelongsToCompany($request, $company);

        DB::transaction(function () use ($request, $company, $order) {
            $room = $this->lockBookableRoom($company, $request->validated('room_id'), $order);
            [$in, $out, $nights] = $this->stay($request);

            $this->assertRoomFree($company, $room, $in, $out, $order->id);

            $contactId = $this->resolveContactId($request, $company);

            $order->items()->delete();
            $order->items()->create($this->itemPayload($room, $in, $out, $nights));

            $order->update([
                'contact_id' => $contactId,
                'total_amount' => $nights * (float) $room->unit_price,
                'rental_start_at' => $in,
                'rental_end_at' => $out,
            ]);
        });

        return redirect()->route('company.reservations.index', ['company' => $company->slug])
            ->with('status', "Reservation {$order->order_number} updated.");
    }

    public function cancel(Company $company, Order $order)
    {
        abort_unless($company->type === 'hotel' && $order->company_id === $company->id, 404);
        abort_if($order->status === 'void', 422, 'This reservation is already cancelled.');
        abort_if($order->payments()->exists(), 422, 'Cannot cancel a reservation that already has payments logged.');

        $order->update(['status' => 'void']);

        return redirect()->route('company.reservations.index', ['company' => $company->slug])
            ->with('status', "Reservation {$order->order_number} cancelled — the room is free again.");
    }

    // Locks the room row so two staff booking the same room at the
    // same moment are serialised and the second one sees the first's
    // reservation in assertRoomFree(). When editing, the room already
    // on the reservation stays selectable even if it has since been
    // put into maintenance.
    private function lockBookableRoom(Company $company, string $roomId, ?Order $editing = null): Product
    {
        $room = Product::where('company_id', $company->id)
            ->where('attributes->kind', HotelRoomPageController::KIND)
            ->lockForUpdate()
            ->find($roomId);

        if (! $room) {
            throw ValidationException::withMessages(['room_id' => 'Selected room does not belong to this hotel.']);
        }

        $keepsSameRoom = $editing && $editing->items()->where('product_id', $room->id)->exists();

        if (($room->attributes['room_status'] ?? 'available') !== 'available' && ! $keepsSameRoom) {
            throw ValidationException::withMessages(['room_id' => "{$room->name} is under maintenance and cannot be booked."]);
        }

        return $room;
    }

    private function assertRoomFree(Company $company, Product $room, Carbon $in, Carbon $out, ?string $ignoreOrderId = null): void
    {
        $clash = Order::where('company_id', $company->id)
            ->where('status', '!=', 'void')
            ->whereHas('items', fn($q) => $q->where('product_id', $room->id))
            ->whereDate('rental_start_at', '<', $out->toDateString())
            ->whereDate('rental_end_at', '>', $in->toDateString())
            ->when($ignoreOrderId, fn($q) => $q->where('id', '!=', $ignoreOrderId))
            ->first();

        if ($clash) {
            throw ValidationException::withMessages([
                'room_id' => "{$room->name} is already booked "
                    . $clash->rental_start_at->format('d M') . ' – ' . $clash->rental_end_at->format('d M Y')
                    . " ({$clash->order_number}).",
            ]);
        }
    }

    private function stay(StoreReservationRequest $request): array
    {
        $in = Carbon::parse($request->validated('check_in'))->startOfDay();
        $out = Carbon::parse($request->validated('check_out'))->startOfDay();

        return [$in, $out, (int) $in->diffInDays($out)];
    }

    private function itemPayload(Product $room, Carbon $in, Carbon $out, int $nights): array
    {
        $rate = (float) $room->unit_price;

        return [
            'product_id' => $room->id,
            'description' => "{$room->name} · {$in->format('d M Y')} → {$out->format('d M Y')} ({$nights} " . ($nights === 1 ? 'night' : 'nights') . ')',
            'quantity' => $nights,
            'unit_price' => $rate,
            'subtotal' => $nights * $rate,
        ];
    }

    private function assertContactBelongsToCompany(StoreReservationRequest $request, Company $company): void
    {
        $contactId = $request->validated('contact_id');

        abort_unless(
            ! $contactId || Contact::where('id', $contactId)->where('company_id', $company->id)->exists(),
            422,
            'Selected guest does not belong to this company.'
        );
    }

    private function resolveContactId(StoreReservationRequest $request, Company $company): string
    {
        if ($contactId = $request->validated('contact_id')) {
            return $contactId;
        }

        return Contact::create([
            'company_id' => $company->id,
            'type' => 'customer',
            'name' => $request->validated('new_contact_name'),
            'phone' => $request->validated('new_contact_phone'),
            'email' => $request->validated('new_contact_email'),
        ])->id;
    }
}
