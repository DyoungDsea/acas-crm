<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyPaymentSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'allow_partial_payments' => $this->allow_partial_payments,
            'require_full_payment' => $this->require_full_payment,
            'minimum_partial_percent' => $this->minimum_partial_percent,
        ];
    }
}
