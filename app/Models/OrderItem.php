<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Which pharmacy batch(es) this item's quantity was drawn from,
    // and how much came from each. Only populated for pharmacy
    // companies — see OrderPageController's stock-drawdown logic.
    public function batchAllocations()
    {
        return $this->hasMany(OrderItemBatch::class);
    }
}
