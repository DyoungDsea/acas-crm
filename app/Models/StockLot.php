<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StockLot extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id',
        'product_id',
        'vendor_contact_id',
        'reference',
        'quantity_received',
        'quantity_remaining',
        'received_at',
        'status',
    ];

    protected $casts = [
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

    // Order items that have drawn stock from this lot.
    public function orderItemAllocations()
    {
        return $this->hasMany(OrderItemStockLot::class);
    }
}
