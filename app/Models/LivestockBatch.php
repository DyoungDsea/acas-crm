<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class LivestockBatch extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'type', 'batch_label', 'quantity_start',
        'quantity_current', 'start_date', 'expected_ready_at', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_ready_at' => 'date',
    ];
}
