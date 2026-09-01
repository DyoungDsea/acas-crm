<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CropCycle extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'crop_type', 'plot_label', 'planted_at', 'expected_harvest_at',
        'harvested_at', 'yield_quantity', 'yield_unit', 'status',
    ];

    protected $casts = [
        'planted_at' => 'date',
        'expected_harvest_at' => 'date',
        'harvested_at' => 'date',
        'yield_quantity' => 'decimal:2',
    ];
}
