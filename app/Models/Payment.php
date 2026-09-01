<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUlids;

    protected $fillable = [
        'order_id', 'amount', 'method', 'reference_note', 'received_by', 'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Keep the parent order's status in sync every time a payment
        // is recorded, so `orders.status` never has to be set by hand.
        static::created(fn (Payment $payment) => $payment->order->refreshStatus());
        static::deleted(fn (Payment $payment) => $payment->order->refreshStatus());
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}
