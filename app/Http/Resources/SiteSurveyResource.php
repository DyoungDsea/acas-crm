<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteSurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_date' => $this->survey_date,
            'findings' => $this->findings,
            'status' => $this->status,
            'surveyor' => new UserResource($this->whenLoaded('surveyor')),
        ];
    }
}
