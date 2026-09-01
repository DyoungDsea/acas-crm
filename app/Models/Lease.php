<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Lease extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'property_unit_id', 'contact_id', 'start_date', 'end_date', 'rent_amount', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rent_amount' => 'decimal:2',
    ];

    public function propertyUnit()
    {
        return $this->belongsTo(PropertyUnit::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
