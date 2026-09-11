<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class OrderItemBatch extends Model
{
    use HasUlids;

    protected $fillable = ['order_item_id', 'pharmacy_batch_id', 'quantity'];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function pharmacyBatch()
    {
        return $this->belongsTo(PharmacyBatch::class);
    }
}
