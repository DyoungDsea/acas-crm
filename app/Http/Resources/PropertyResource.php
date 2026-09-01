<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'address' => $this->address,
            'status' => $this->status,
            'units' => PropertyUnitResource::collection($this->whenLoaded('units')),
        ];
    }
}
