<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPaymentSetting extends Model
{
    protected $fillable = [
        'company_id', 'allow_partial_payments', 'require_full_payment', 'minimum_partial_percent',
    ];

    protected $casts = [
        'allow_partial_payments' => 'boolean',
        'require_full_payment' => 'boolean',
        'minimum_partial_percent' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
