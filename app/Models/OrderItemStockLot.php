<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class OrderItemStockLot extends Model
{
    use HasUlids;

    protected $fillable = ['order_item_id', 'stock_lot_id', 'quantity'];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function stockLot()
    {
        return $this->belongsTo(StockLot::class);
    }
}
