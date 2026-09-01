<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PropertyUnit extends Model
{
    use HasUlids;

    protected $fillable = ['property_id', 'unit_label', 'rent_amount', 'status'];

    protected $casts = ['rent_amount' => 'decimal:2'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }
}
