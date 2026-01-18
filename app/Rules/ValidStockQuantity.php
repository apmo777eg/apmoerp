<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidStockQuantity implements ValidationRule
{
    private const FLOAT_EPSILON = 1e-8;

    public function __construct(
        private float $maxQuantity = 999999.99,
        private int $decimalPlaces = 2,
        private bool $allowZero = true,
        private ?string $context = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail(__('validation.numeric', ['attribute' => $attribute]));

            return;
        }

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $quantity = decimal_float($value);

        if ($quantity < 0) {
            $fail(__('validation.min.numeric', ['attribute' => $attribute, 'min' => 0]));

            return;
        }

        if (! $this->allowZero && abs($quantity) < self::FLOAT_EPSILON) {
            $fail(__('validation.gt.numeric', ['attribute' => $attribute, 'value' => 0]));

            return;
        }

        if ($quantity - $this->maxQuantity > self::FLOAT_EPSILON) {
            $fail(__('validation.max.numeric', ['attribute' => $attribute, 'max' => $this->maxQuantity]));

            return;
        }

        if ($this->decimalPlaces >= 0) {
            if ($this->decimalPlaces === 0) {
                // For zero decimal places, reject values with decimal separator
                $decimalPattern = '/^\d+$/';
            } else {
                // Require at least 1 digit after decimal point when decimal is present
                $decimalPattern = '/^\d+(\.\d{1,'.((int) $this->decimalPlaces).'})?$/';
            }
            if (! preg_match($decimalPattern, (string) $value)) {
                $fail(__('validation.decimal', ['attribute' => $attribute, 'decimal' => $this->decimalPlaces]));

                return;
            }
        }

        if ($this->context !== null) {
            // Context hook for future extensions or logging
            $failContext = false;
            if ($failContext) {
                $fail(__('validation.custom', ['attribute' => $attribute, 'rule' => $this->context]));
            }
        }
    }
}
