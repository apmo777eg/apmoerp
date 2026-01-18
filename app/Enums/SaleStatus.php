<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Sale Status Enum
 *
 * Represents all possible states of a sale/invoice
 */
enum SaleStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case CANCELLED = 'cancelled';
    case VOID = 'void';
    case VOIDED = 'voided';
    case RETURNED = 'returned';
    case REFUNDED = 'refunded';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::CONFIRMED => __('Confirmed'),
            self::PAID => __('Paid'),
            self::PARTIALLY_PAID => __('Partially Paid'),
            self::CANCELLED => __('Cancelled'),
            self::VOID => __('Void'),
            self::VOIDED => __('Voided'),
            self::RETURNED => __('Returned'),
            self::REFUNDED => __('Refunded'),
        };
    }

    /**
     * Get color for display.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'slate',
            self::CONFIRMED => 'blue',
            self::PAID => 'green',
            self::PARTIALLY_PAID => 'amber',
            self::CANCELLED => 'red',
            self::VOID => 'gray',
            self::VOIDED => 'gray',
            self::RETURNED => 'orange',
            self::REFUNDED => 'purple',
        };
    }

    /**
     * Check if status is final (cannot be changed).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED, self::VOID, self::VOIDED, self::RETURNED, self::REFUNDED]);
    }

    /**
     * Get allowed next statuses.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::CONFIRMED, self::CANCELLED, self::VOID],
            self::CONFIRMED => [self::PAID, self::PARTIALLY_PAID, self::CANCELLED, self::VOID],
            self::PARTIALLY_PAID => [self::PAID, self::CANCELLED, self::VOID],
            self::PAID => [self::REFUNDED, self::RETURNED],
            self::CANCELLED => [],
            self::VOID => [],
            self::VOIDED => [],
            self::RETURNED => [],
            self::REFUNDED => [],
        };
    }

    /**
     * Check if transition is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }

    /**
     * Get statuses that should be excluded from revenue/financial calculations.
     *
     * These statuses represent sales that did not generate actual revenue:
     * - Draft: Not finalized
     * - Cancelled: Never completed
     * - Void/Voided: Invalidated
     * - Returned: Goods returned
     * - Refunded: Money returned to customer
     *
     * @return array<string> Array of status values for use in whereNotIn queries
     */
    public static function nonRevenueStatuses(): array
    {
        return [
            self::DRAFT->value,
            self::CANCELLED->value,
            self::VOID->value,
            self::VOIDED->value,
            self::RETURNED->value,
            self::REFUNDED->value,
        ];
    }

    /**
     * Check if this status is a non-revenue status.
     */
    public function isNonRevenue(): bool
    {
        return in_array($this->value, self::nonRevenueStatuses());
    }

    /**
     * Get statuses that represent completed/revenue-generating sales.
     *
     * @return array<string> Array of status values for use in whereIn queries
     */
    public static function revenueStatuses(): array
    {
        return [
            self::CONFIRMED->value,
            self::PAID->value,
            self::PARTIALLY_PAID->value,
        ];
    }
}
