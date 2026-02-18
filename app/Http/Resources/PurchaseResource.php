<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
                return [
            'id' => $this->id,
            // Compatibility keys (DB-first identifier is reference_number)
            'code' => $this->reference_number,
            'reference_no' => $this->reference_number,

            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'supplier_id' => $this->supplier_id,

            'status' => $this->status,
            'expected_date' => $this->expected_date?->toIso8601String(),
            'due_date' => $this->due_date?->toIso8601String(),

            'notes' => $this->notes,
            'payment_status' => $this->payment_status,

            // Compatibility totals (canonical columns are *_amount and total_amount)
            'sub_total' => decimal_float($this->subtotal ?? 0.0),
            'tax_total' => decimal_float($this->tax_amount ?? 0.0),
            'discount_total' => decimal_float($this->discount_amount ?? 0.0),
            'shipping_total' => decimal_float($this->shipping_amount ?? 0.0),
            'grand_total' => decimal_float($this->total_amount ?? 0.0),
            'paid_total' => decimal_float($this->paid_amount ?? 0.0),
            'due_total' => decimal_float($this->remaining_amount ?? 0.0),

            'approved_at' => $this->approved_at?->toIso8601String(),

            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'items' => $this->whenLoaded('items'),
            'items_count' => $this->whenCounted('items'),

            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
