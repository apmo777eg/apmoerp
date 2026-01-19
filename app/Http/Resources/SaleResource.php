<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'reference_no' => $this->reference_no,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
            'delivery_date' => $this->delivery_date?->toIso8601String(),
            'shipping_method' => $this->shipping_method,
            'tracking_number' => $this->tracking_number,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'payment_status' => $this->payment_status,
            'payment_due_date' => $this->payment_due_date?->toIso8601String(),
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount ? decimal_float($this->discount_amount) : null,
            'sub_total' => decimal_float($this->sub_total ?? 0.0),
            'tax_total' => decimal_float($this->tax_total ?? 0.0),
            'discount_total' => decimal_float($this->discount_total ?? 0.0),
            'shipping_total' => decimal_float($this->shipping_total ?? 0.0),
            'grand_total' => decimal_float($this->grand_total ?? 0.0),
            'paid_total' => decimal_float($this->paid_total ?? 0.0),
            'due_total' => decimal_float($this->due_total ?? 0.0),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'items' => $this->whenLoaded('items'),
            'items_count' => $this->whenCounted('items'),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
