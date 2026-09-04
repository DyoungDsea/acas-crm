<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'category_id', 'name', 'sku', 'unit_price', 'attributes', 'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pharmacyBatches()
    {
        return $this->hasMany(PharmacyBatch::class);
    }
}
