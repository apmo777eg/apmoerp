<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Sale Status Enum
 *
 * Represents all possible states of a sale/order/invoice.
 *
 * NOTE:
 * - The system uses `sales.status` for workflow state (draft/pending/processing/completed...)
 * - Payment state is tracked separately in `sales.payment_status` (unpaid/partial/paid)
 *
 * This enum intentionally includes legacy / integration statuses that may appear in existing data
 * (e.g. paid/partially_paid/closed) to avoid breaking edits, reports, and API sync.
 */
enum SaleStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PROCESSING = 'processing';

    case CONFIRMED = 'confirmed';
    case POSTED = 'posted';
    case COMPLETED = 'completed';

    // Legacy / integration states (may be present in existing installations)
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case CLOSED = 'closed';

    case CANCELLED = 'cancelled';
    case VOID = 'void';
    case VOIDED = 'voided';

    case RETURNED = 'returned';
    case PARTIALLY_RETURNED = 'partially_returned';

    case REFUNDED = 'refunded';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::PENDING => __('Pending'),
            self::PROCESSING => __('Processing'),
            self::CONFIRMED => __('Confirmed'),
            self::POSTED => __('Posted'),
            self::COMPLETED => __('Completed'),
            self::PAID => __('Paid'),
            self::PARTIALLY_PAID => __('Partially Paid'),
            self::CLOSED => __('Closed'),
            self::CANCELLED => __('Cancelled'),
            self::VOID => __('Void'),
            self::VOIDED => __('Voided'),
            self::RETURNED => __('Returned'),
            self::PARTIALLY_RETURNED => __('Partially Returned'),
            self::REFUNDED => __('Refunded'),
        };
    }

    /**
     * Get a semantic color name (used by some UI components).
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'slate',
            self::PENDING => 'amber',
            self::PROCESSING => 'amber',
            self::CONFIRMED => 'blue',
            self::POSTED => 'blue',
            self::COMPLETED => 'green',
            self::PAID => 'green',
            self::PARTIALLY_PAID => 'amber',
            self::CLOSED => 'gray',
            self::CANCELLED => 'red',
            self::VOID, self::VOIDED => 'gray',
            self::RETURNED, self::PARTIALLY_RETURNED => 'orange',
            self::REFUNDED => 'purple',
        };
    }

    /**
     * Check if status is final (cannot be changed in normal workflows).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::VOID,
            self::VOIDED,
            self::CLOSED,
            self::REFUNDED,
            self::RETURNED,
        ], true);
    }

    /**
     * Get allowed next statuses.
     *
     * This is used as a guideline for UI/workflows; some integrations may allow different paths.
     * Keep transitions permissive enough to not block existing installations.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING, self::CONFIRMED, self::CANCELLED, self::VOID],
            self::PENDING => [self::PROCESSING, self::CONFIRMED, self::CANCELLED, self::VOID],
            self::PROCESSING => [self::COMPLETED, self::CANCELLED, self::VOID],

            self::CONFIRMED => [self::POSTED, self::COMPLETED, self::CANCELLED, self::VOID],
            self::POSTED => [self::COMPLETED, self::PAID, self::CANCELLED, self::VOID],
            self::COMPLETED => [self::REFUNDED, self::RETURNED, self::PARTIALLY_RETURNED],

            self::PARTIALLY_PAID => [self::PAID, self::CANCELLED, self::VOID],
            self::PAID => [self::REFUNDED, self::RETURNED, self::PARTIALLY_RETURNED, self::CLOSED],

            self::PARTIALLY_RETURNED => [self::RETURNED, self::REFUNDED],

            self::CANCELLED, self::VOID, self::VOIDED, self::RETURNED, self::REFUNDED, self::CLOSED => [],
        };
    }

    /**
     * Check if transition is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    /**
     * All enum values (useful for validation / dropdowns).
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }

    /**
     * Statuses that should be excluded from revenue/financial calculations.
     *
     * These statuses represent sales that are not finalized revenue:
     * - Draft/Pending/Processing: not finalized
     * - Cancelled/Void/Voided: invalid
     * - Returned/Partially returned: goods returned
     * - Refunded: money returned
     *
     * @return array<string>
     */
    public static function nonRevenueStatuses(): array
    {
        return [
            self::DRAFT->value,
            self::PENDING->value,
            self::PROCESSING->value,
            self::CANCELLED->value,
            self::VOID->value,
            self::VOIDED->value,
            self::RETURNED->value,
            self::PARTIALLY_RETURNED->value,
            self::REFUNDED->value,
        ];
    }

    /**
     * Check if this status is a non-revenue status.
     */
    public function isNonRevenue(): bool
    {
        return in_array($this->value, self::nonRevenueStatuses(), true);
    }

    /**
     * Statuses that represent completed/revenue-generating sales.
     *
     * @return array<string>
     */
    public static function revenueStatuses(): array
    {
        return [
            self::CONFIRMED->value,
            self::POSTED->value,
            self::COMPLETED->value,
            self::PAID->value,
            self::PARTIALLY_PAID->value,
        ];
    }
}
