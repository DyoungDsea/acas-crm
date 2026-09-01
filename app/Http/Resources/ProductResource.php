<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'name' => $this->name,
            'sku' => $this->sku,
            'unit_price' => $this->unit_price,
            'attributes' => $this->attributes,
            'is_active' => $this->is_active,
        ];
    }
}
