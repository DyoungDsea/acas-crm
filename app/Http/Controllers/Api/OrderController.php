<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['contact', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return OrderResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreOrderRequest $request)
    {
        $order = DB::transaction(function () use ($request) {
            $items = $request->validated('items');
            $total = collect($items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $order = Order::create([
                'contact_id' => $request->validated('contact_id'),
                'order_number' => $this->generateOrderNumber($request->user()->company),
                'total_amount' => $total,
                'created_by' => $request->user()->id,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order;
        });

        return new OrderResource($order->load(['contact', 'items']));
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load(['contact', 'items', 'payments.receipt']));
    }

    public function destroy(Order $order)
    {
        abort_if($order->payments()->exists(), 422, 'Cannot delete an order with payments already logged. Void the payments first.');

        $order->delete();

        return response()->noContent();
    }

    private function generateOrderNumber($company): string
    {
        $prefix = strtoupper(Str::substr(Str::slug($company->name), 0, 3));
        $year = now()->year;
        $sequence = str_pad((string) (Order::whereYear('created_at', $year)->count() + 1), 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
