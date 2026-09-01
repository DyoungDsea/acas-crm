<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'status' => $this->status,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
        ];
    }
}
