<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'status' => $this->status,
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'stage' => new PipelineStageResource($this->whenLoaded('stage')),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'created_at' => $this->created_at,
        ];
    }
}
