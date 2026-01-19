<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Format rating value (0-5 scale)
     * Returns null for empty values, float for valid ratings
     */
    private function formatRating($value): ?float
    {
        return $value ? decimal_float($value) : null;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_person' => $this->contact_person,
            'contact_person_phone' => $this->contact_person_phone,
            'contact_person_email' => $this->contact_person_email,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
            'payment_terms' => $this->payment_terms,
            'payment_due_days' => (int) ($this->payment_due_days ?? 30),
            'minimum_order_value' => $this->when(
                $request->user()?->can('suppliers.view-financial'),
                decimal_float($this->minimum_order_value ?? 0.0)
            ),
            'supplier_rating' => $this->supplier_rating,
            'quality_rating' => $this->formatRating($this->quality_rating),
            'delivery_rating' => $this->formatRating($this->delivery_rating),
            'service_rating' => $this->formatRating($this->service_rating),
            'last_purchase_date' => $this->last_purchase_date?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'is_approved' => (bool) $this->is_approved,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'purchases_count' => $this->when(
                $request->user()?->can('suppliers.view-purchases'),
                $this->whenCounted('purchases')
            ),
            'total_purchases_amount' => $this->when(
                $request->user()?->can('suppliers.view-financial') && $this->relationLoaded('purchases'),
                fn () => decimal_float($this->purchases->sum('total_amount'))
            ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
