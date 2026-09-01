<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_label' => $this->unit_label,
            'rent_amount' => $this->rent_amount,
            'status' => $this->status,
        ];
    }
}
