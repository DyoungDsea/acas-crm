<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'pdf_path' => $this->pdf_path,
            'issued_by' => new UserResource($this->whenLoaded('issuedByUser')),
            'issued_at' => $this->issued_at,
            'is_voided' => $this->isVoided(),
            'voided_at' => $this->voided_at,
        ];
    }
}
