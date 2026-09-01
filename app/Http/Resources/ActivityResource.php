<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'occurred_at' => $this->occurred_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'subject_type' => class_basename($this->subject_type),
            'subject_id' => $this->subject_id,
        ];
    }
}
