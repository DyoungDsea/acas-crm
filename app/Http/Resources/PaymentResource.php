<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference_note' => $this->reference_note,
            'received_by' => new UserResource($this->whenLoaded('receivedByUser')),
            'received_at' => $this->received_at,
            'receipt' => new ReceiptResource($this->whenLoaded('receipt')),
        ];
    }
}
