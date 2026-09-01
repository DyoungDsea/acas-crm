<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EngagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'scope' => $this->scope,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'lead_advisor' => new UserResource($this->whenLoaded('leadAdvisor')),
        ];
    }
}
