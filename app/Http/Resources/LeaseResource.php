<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'rent_amount' => $this->rent_amount,
            'status' => $this->status,
            'property_unit' => new PropertyUnitResource($this->whenLoaded('propertyUnit')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
        ];
    }
}
