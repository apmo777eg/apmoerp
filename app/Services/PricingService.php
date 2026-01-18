<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceGroup;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceInterface;
use App\Services\Contracts\PricingServiceInterface;
use App\Services\Contracts\TaxServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Arr;

class PricingService implements PricingServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(
        protected DiscountServiceInterface $discounts,
        protected TaxServiceInterface $taxes
    ) {}

    public function resolveUnitPrice(Product $product, ?int $priceGroupId = null, ?float $override = null): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($product, $priceGroupId, $override) {
                if ($override !== null) {
                    // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                    return decimal_float(bcdiv((string) $override, '1', 4), 4);
                }

                if ($priceGroupId && class_exists(PriceGroup::class)) {
                    $pg = PriceGroup::find($priceGroupId);
                    if ($pg && method_exists($pg, 'priceFor')) {
                        $p = $pg->priceFor($product->getKey());
                        if ($p !== null) {
                            return decimal_float(bcdiv((string) $p, '1', 4), 4);
                        }
                    }
                }

                $base = $product->default_price ?? 0.0;

                return decimal_float(bcdiv((string) $base, '1', 4), 4);
            },
            operation: 'resolveUnitPrice',
            context: ['product_id' => $product->id, 'price_group_id' => $priceGroupId],
            defaultValue: 0.0
        );
    }

    public function lineTotals(array $line): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($line) {
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                $qty = max(0.0, decimal_float(Arr::get($line, 'qty', 1)));
                $price = max(0.0, decimal_float(Arr::get($line, 'price', 0)));
                $percent = (bool) Arr::get($line, 'percent', true);
                $discVal = decimal_float(Arr::get($line, 'discount', 0));
                $taxId = Arr::get($line, 'tax_id');

                $subtotal = $qty * $price;

                $discount = $this->discounts->lineTotal($qty, $price, $discVal, $percent);
                $discount = min($discount, $subtotal);

                $baseAfterDiscount = max(0.0, $subtotal - $discount);

                $taxAmount = 0.0;
                if (! empty($taxId)) {
                    $taxAmount = $this->taxes->amountFor($baseAfterDiscount, (int) $taxId);
                }

                $total = ! empty($taxId)
                    ? $this->taxes->totalWithTax($baseAfterDiscount, (int) $taxId)
                    : $baseAfterDiscount;

                // Ensure total never goes below zero (prevent negative pricing)
                $total = max(0.0, $total);

                // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                return [
                    'subtotal' => decimal_float(bcround((string) $subtotal, 2)),
                    'discount' => decimal_float(bcround((string) $discount, 2)),
                    'tax' => decimal_float(bcround((string) $taxAmount, 2)),
                    'total' => decimal_float(bcround((string) $total, 2)),
                ];
            },
            operation: 'lineTotals',
            context: ['line' => $line],
            defaultValue: ['subtotal' => 0.0, 'discount' => 0.0, 'tax' => 0.0, 'total' => 0.0]
        );
    }
}
