<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDiscount implements ValidationRule
{
    public function __construct(
        protected bool $percentage = true,
        protected float $maxPercent = 50.0,
        protected float $maxAmount = 1000.0
    ) {}

    public static function percent(float $maxPercent = 50.0): self
    {
        return new self(true, $maxPercent);
    }

    public static function amount(float $maxAmount = 1000.0): self
    {
        return new self(false, 0.0, $maxAmount);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail('Discount must be numeric.');

            return;
        }
        $num = decimal_float($value);
        if ($this->percentage) {
            if ($num < 0 || $num > $this->maxPercent) {
                $fail('Discount percent out of range.');
            }
        } else {
            if ($num < 0 || $num > $this->maxAmount) {
                $fail('Discount amount out of range.');
            }
        }
    }
}
