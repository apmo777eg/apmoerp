<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'tax_number' => $this->tax_number,
            'credit_limit' => $this->when(
                $request->user()?->can('customers.view-financial'),
                decimal_float($this->credit_limit ?? 0.0)
            ),
            'discount_percentage' => $this->when(
                $request->user()?->can('customers.view-financial'),
                decimal_float($this->discount_percentage ?? 0.0)
            ),
            'payment_terms' => $this->payment_terms,
            'payment_due_days' => (int) ($this->payment_due_days ?? 30),
            'customer_tier' => $this->customer_tier,
            'preferred_currency' => $this->preferred_currency,
            'balance' => $this->when(
                $request->user()?->can('customers.view-financial'),
                decimal_float($this->balance ?? 0.0)
            ),
            'status' => $this->status,
            'loyalty_points' => $this->loyalty_points,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'sales_count' => $this->when(
                $request->user()?->can('customers.view-sales'),
                $this->whenCounted('sales')
            ),
            'total_purchases' => $this->when(
                $request->user()?->can('customers.view-financial'),
                decimal_float($this->total_purchases ?? 0.0)
            ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
