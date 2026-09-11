<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PharmacyBatch extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id',
        'product_id',
        'vendor_contact_id',
        'batch_number',
        'quantity_received',
        'quantity_remaining',
        'expiry_date',
        'received_at',
        'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendorContact()
    {
        return $this->belongsTo(Contact::class, 'vendor_contact_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    // Order items that have drawn stock from this batch. Only
    // relevant while quantity_remaining is being tracked against
    // sales — see OrderPageController.
    public function orderItemAllocations()
    {
        return $this->hasMany(OrderItemBatch::class);
    }
}
