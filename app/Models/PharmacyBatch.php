<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PharmacyBatch extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'product_id', 'batch_number', 'quantity_received',
        'quantity_remaining', 'expiry_date', 'received_at', 'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
