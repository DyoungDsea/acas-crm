<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CropCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crop_type' => $this->crop_type,
            'plot_label' => $this->plot_label,
            'planted_at' => $this->planted_at,
            'expected_harvest_at' => $this->expected_harvest_at,
            'harvested_at' => $this->harvested_at,
            'yield_quantity' => $this->yield_quantity,
            'yield_unit' => $this->yield_unit,
            'status' => $this->status,
        ];
    }
}
