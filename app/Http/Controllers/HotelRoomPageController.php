<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotelRoomRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Hotel-only. A room is a Product row (so a reservation can bill it
// as an ordinary order line, exactly like a vehicle), tagged with
// attributes.kind = 'hotel_room'. Rooms are found by that tag, NOT by
// category name, so renaming the "Hotel Rooms" category on the
// generic Categories page can't make rooms disappear from this page.
//
// room_status here is only the *manual* state (available / out for
// maintenance). Whether a room is occupied is never stored — it is
// worked out from reservations, so it can't drift out of sync.
//
// No destroy(): a room that has had guests is history. Mark it
// "maintenance" (hidden from the reservation picker) instead.
class HotelRoomPageController extends Controller
{
    public const KIND = 'hotel_room';

    public function index(Company $company)
    {
        abort_unless($company->type === 'hotel', 404);

        $rooms = Product::where('company_id', $company->id)
            ->where('attributes->kind', self::KIND)
            ->orderBy('sku')
            ->get();

        $today = now()->toDateString();

        // room product id => order covering today (non-cancelled)
        $occupiedNow = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.company_id', $company->id)
            ->where('orders.status', '!=', 'void')
            ->whereDate('orders.rental_start_at', '<=', $today)
            ->whereDate('orders.rental_end_at', '>', $today)
            ->whereIn('order_items.product_id', $rooms->pluck('id'))
            ->pluck('orders.order_number', 'order_items.product_id');

        return view('company.hotel-rooms.index', [
            'company' => $company,
            'rooms' => $rooms,
            'occupiedNow' => $occupiedNow,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreHotelRoomRequest $request, Company $company)
    {
        abort_unless($company->type === 'hotel', 404);
        $data = $request->validated();
        $this->assertNumberFree($company, $data['room_number']);

        $room = Product::create($this->payload($company, $data));

        return redirect()->route('company.hotel-rooms.index', ['company' => $company->slug])
            ->with('status', "{$room->name} added.");
    }

    public function update(StoreHotelRoomRequest $request, Company $company, Product $hotel_room)
    {
        abort_unless($company->type === 'hotel' && $hotel_room->company_id === $company->id
            && ($hotel_room->attributes['kind'] ?? null) === self::KIND, 404);

        $data = $request->validated();
        $this->assertNumberFree($company, $data['room_number'], $hotel_room->id);

        $hotel_room->update($this->payload($company, $data));

        return redirect()->route('company.hotel-rooms.index', ['company' => $company->slug])
            ->with('status', "{$hotel_room->name} updated.");
    }

    private function payload(Company $company, array $data): array
    {
        $category = ProductCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Hotel Rooms']);

        return [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'name' => "Room {$data['room_number']} — {$data['room_type']}",
            'sku' => $data['room_number'],
            'unit_price' => $data['nightly_rate'],
            'attributes' => [
                'kind' => self::KIND,
                'room_number' => $data['room_number'],
                'room_type' => $data['room_type'],
                'floor' => $data['floor'] ?? null,
                'capacity' => (int) $data['capacity'],
                'room_status' => $data['room_status'],
                'notes' => $data['notes'] ?? null,
            ],
            'is_active' => $data['room_status'] === 'available',
        ];
    }

    // Two rooms with the same number in one hotel would make the
    // reservation picker ambiguous.
    private function assertNumberFree(Company $company, string $number, ?string $ignoreId = null): void
    {
        $taken = Product::where('company_id', $company->id)
            ->where('attributes->kind', self::KIND)
            ->where('sku', $number)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['room_number' => "Room {$number} already exists."]);
        }
    }
}
