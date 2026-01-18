<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDiscountPercentage implements ValidationRule
{
    public function __construct(
        private float $maxDiscount = 100.0,
        private int $decimalPlaces = 2,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_numeric($value)) {
            $fail(__('validation.numeric', ['attribute' => $attribute]));

            return;
        }

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $discount = decimal_float($value);

        if ($discount < 0) {
            $fail(__('validation.min.numeric', ['attribute' => $attribute, 'min' => 0]));

            return;
        }

        if ($discount > $this->maxDiscount) {
            $fail(__('validation.max.numeric', ['attribute' => $attribute, 'max' => $this->maxDiscount]));

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
            }
        }
    }
}
