<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPriceOverride implements ValidationRule
{
    public function __construct(
        protected float $cost,
        protected float $minMarginPercent = 0.0
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail('Price must be numeric.');

            return;
        }

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $price = decimal_float($value);
        if ($price < 0) {
            $fail('Price cannot be negative.');

            return;
        }

        $min = $this->cost * (1 + ($this->minMarginPercent / 100));
        if ($this->minMarginPercent > 0 && $price + 1e-6 < $min) {
            $fail(sprintf('Price must be at least %.2f to respect margin.', $min));
        }
    }
}
