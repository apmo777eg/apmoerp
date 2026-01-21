<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
            // V52-HIGH-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for quantities and prices
            'quantity' => decimal_float($this->qty, 4),
            'unit_price' => decimal_float($this->unit_price, 4),
            'discount' => decimal_float($this->discount, 4),
            'tax' => decimal_float($this->tax ?? 0, 4),
            'total' => decimal_float($this->line_total, 4),
            'notes' => $this->notes,
        ];
    }
}
