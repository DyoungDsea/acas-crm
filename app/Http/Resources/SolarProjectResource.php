<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolarProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'completion_date' => $this->completion_date,
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'site_surveys' => SiteSurveyResource::collection($this->whenLoaded('siteSurveys')),
        ];
    }
}
