<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tax;
use App\Services\Contracts\TaxServiceInterface;
use App\Traits\HandlesServiceErrors;

class TaxService implements TaxServiceInterface
{
    use HandlesServiceErrors;

    public function rate(?int $taxId): float
    {
        if (! $taxId || ! class_exists(Tax::class)) {
            return 0.0;
        }
        $tax = Tax::find($taxId);

        // NEW-HIGH-02 FIX: Use nullsafe operator to prevent crash when tax is deleted/missing
        return decimal_float($tax?->rate ?? 0.0);
    }

    public function compute(float $base, ?int $taxId): float
    {
        $r = $this->rate($taxId);

        // BUG FIX #5: Use bcmath for precise tax calculation with line-level rounding (2 decimals)
        $rateDecimal = bcdiv((string) $r, '100', 6);
        $taxAmount = bcmul((string) $base, $rateDecimal, 4);

        // V30-MED-08 FIX: Use bcround() for proper half-up rounding
        return decimal_float(bcround($taxAmount, 2));
    }

    public function amountFor(float $base, ?int $taxId): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($base, $taxId) {
                if (! $taxId || ! class_exists(Tax::class)) {
                    return 0.0;
                }

                $tax = Tax::find($taxId);
                if (! $tax) {
                    return 0.0;
                }

                $rate = decimal_float($tax->rate);

                if ($rate <= 0) {
                    return 0.0;
                }

                if ($tax->is_inclusive ?? false) {
                    // Use bcmath for precise inclusive tax calculation
                    $divisor = bcadd('1', bcdiv((string) $rate, '100', 6), 6);
                    $baseExcl = bcdiv((string) $base, $divisor, 6);
                    $taxPortion = bcsub((string) $base, $baseExcl, 6);

                    return decimal_float(bcdiv($taxPortion, '1', 4));
                }

                // Use bcmath for precise tax calculation
                $taxAmount = bcmul((string) $base, bcdiv((string) $rate, '100', 6), 6);

                return decimal_float(bcdiv($taxAmount, '1', 4));
            },
            operation: 'amountFor',
            context: ['base' => $base, 'tax_id' => $taxId],
            defaultValue: 0.0
        );
    }

    public function totalWithTax(float $base, ?int $taxId): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($base, $taxId) {
                if (! $taxId || ! class_exists(Tax::class)) {
                    return decimal_float(bcdiv((string) $base, '1', 4));
                }

                $tax = Tax::find($taxId);
                if (! $tax) {
                    return decimal_float(bcdiv((string) $base, '1', 4));
                }

                if ($tax->is_inclusive ?? false) {
                    return decimal_float(bcdiv((string) $base, '1', 4));
                }

                // Use bcmath for precise total calculation
                $taxAmount = $this->amountFor($base, $taxId);
                $total = bcadd((string) $base, (string) $taxAmount, 6);

                return decimal_float(bcdiv($total, '1', 4));
            },
            operation: 'totalWithTax',
            context: ['base' => $base, 'tax_id' => $taxId],
            defaultValue: decimal_float(bcdiv((string) $base, '1', 4))
        );
    }

    /**
     * Calculate tax lines for multiple items
     *
     * @param  array  $items  Array of items with subtotal and tax_id keys
     * @param  mixed  $customer  Customer model or null - Reserved for future tax exemption logic
     * @param  mixed  $warehouse  Warehouse model or null - Reserved for future location-based tax
     * @param  string|null  $date  Date for tax rate lookup - Reserved for future rate versioning
     * @return array Returns ['lines' => [...], 'total_tax' => float]
     *
     * @todo Implement customer tax exemption checking
     * @todo Implement warehouse/location-based tax rates
     * @todo Implement date-based tax rate versioning
     */
    public function calculateTaxLines(array $items, mixed $customer = null, mixed $warehouse = null, ?string $date = null): array
    {
        // Note: $customer, $warehouse, $date parameters are reserved for future functionality
        // They are intentionally unused in the current implementation

        return $this->handleServiceOperation(
            callback: function () use ($items) {
                $lines = [];
                $totalTax = '0';

                foreach ($items as $index => $item) {
                    $subtotal = decimal_float($item['subtotal'] ?? $item['line_total'] ?? 0);
                    $taxId = $item['tax_id'] ?? null;

                    $taxAmount = $taxId ? $this->amountFor($subtotal, $taxId) : 0.0;
                    $taxRate = $taxId ? $this->rate($taxId) : 0.0;

                    $lines[] = [
                        'index' => $index,
                        'subtotal' => $subtotal,
                        'tax_id' => $taxId,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total_with_tax' => decimal_float(bcadd((string) $subtotal, (string) $taxAmount, 4)),
                    ];

                    $totalTax = bcadd($totalTax, (string) $taxAmount, 4);
                }

                return [
                    'lines' => $lines,
                    // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
                    'total_tax' => decimal_float(bcround($totalTax, 2)),
                ];
            },
            operation: 'calculateTaxLines',
            context: ['items_count' => count($items)],
            defaultValue: ['lines' => [], 'total_tax' => 0.0]
        );
    }

    /**
     * Calculate total tax for a subtotal given tax rate rules
     *
     * @param  float  $subtotal  Base amount before tax
     * @param  array  $taxRateRules  Tax rules array with rate, is_inclusive keys
     * @return float Total tax amount
     */
    public function calculateTotalTax(float $subtotal, array $taxRateRules): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($subtotal, $taxRateRules) {
                if (empty($taxRateRules) || $subtotal <= 0) {
                    return 0.0;
                }

                $rate = decimal_float($taxRateRules['rate'] ?? 0);
                $isInclusive = (bool) ($taxRateRules['is_inclusive'] ?? false);

                if ($rate <= 0) {
                    return 0.0;
                }

                if ($isInclusive) {
                    // Extract tax from inclusive price: tax = price - (price / (1 + rate/100))
                    $divisor = bcadd('1', bcdiv((string) $rate, '100', 6), 6);
                    $baseExcl = bcdiv((string) $subtotal, $divisor, 6);
                    $taxAmount = bcsub((string) $subtotal, $baseExcl, 6);

                    // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
                    return decimal_float(bcround($taxAmount, 2));
                }

                // Exclusive: tax = subtotal * (rate / 100)
                $taxAmount = bcmul((string) $subtotal, bcdiv((string) $rate, '100', 6), 6);

                // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
                return decimal_float(bcround($taxAmount, 2));
            },
            operation: 'calculateTotalTax',
            context: ['subtotal' => $subtotal, 'rules' => $taxRateRules],
            defaultValue: 0.0
        );
    }
}
