<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
                return [
            'id' => $this->id,
            'order_number' => $this->reference_number,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'status' => $this->status,
            'order_date' => $this->sale_date?->toIso8601String(),
            'channel' => $this->channel,

            // Optional: infer from latest payment if loaded
            'payment_method' => $this->relationLoaded('payments')
                ? optional($this->payments->last())->payment_method
                : null,

            'sub_total' => decimal_float($this->subtotal ?? 0.0),
            'discount' => decimal_float($this->discount_amount ?? 0.0),
            'discount_type' => $this->discount_type,
            'tax' => decimal_float($this->tax_amount ?? 0.0),
            'shipping' => decimal_float($this->shipping_amount ?? 0.0),
            'grand_total' => decimal_float($this->total_amount ?? 0.0),
            'paid_total' => decimal_float($this->paid_amount ?? 0.0),
            'due_total' => decimal_float($this->remaining_amount ?? 0.0),

            'notes' => $this->notes,

            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }

    /**
     * Compute payment status safely, handling models with or without isPaid() method.
     */
    protected function computePaymentStatus(): string
    {
        if (method_exists($this->resource, 'isPaid') && $this->resource->isPaid()) {
            return 'paid';
        }

        $total = decimal_float($this->total_amount ?? 0.0);
        $paid = decimal_float($this->paid_amount ?? 0.0);

        if ($total <= 0) {
            return 'unpaid';
        }

        if ($paid >= $total) {
            return 'paid';
        }

        return $paid > 0 ? 'partial' : 'unpaid';
    }
}
