<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LivestockBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'batch_label' => $this->batch_label,
            'quantity_start' => $this->quantity_start,
            'quantity_current' => $this->quantity_current,
            'start_date' => $this->start_date,
            'expected_ready_at' => $this->expected_ready_at,
            'status' => $this->status,
        ];
    }
}
