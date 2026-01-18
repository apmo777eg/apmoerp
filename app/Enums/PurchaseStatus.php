<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Purchase Status Enum
 *
 * Represents all possible states of a purchase order
 */
enum PurchaseStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case RECEIVED = 'received';
    case PARTIALLY_RECEIVED = 'partially_received';
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
            self::APPROVED => __('Approved'),
            self::RECEIVED => __('Received'),
            self::PARTIALLY_RECEIVED => __('Partially Received'),
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
            self::APPROVED => 'blue',
            self::RECEIVED => 'green',
            self::PARTIALLY_RECEIVED => 'amber',
            self::CANCELLED => 'red',
            self::VOID => 'gray',
            self::VOIDED => 'gray',
            self::RETURNED => 'orange',
            self::REFUNDED => 'purple',
        };
    }

    /**
     * Check if status is final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::RECEIVED, self::CANCELLED, self::VOID, self::VOIDED, self::RETURNED, self::REFUNDED]);
    }

    /**
     * Get allowed next statuses.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::APPROVED, self::CANCELLED, self::VOID],
            self::APPROVED => [self::RECEIVED, self::PARTIALLY_RECEIVED, self::CANCELLED, self::VOID],
            self::PARTIALLY_RECEIVED => [self::RECEIVED, self::CANCELLED, self::VOID],
            self::RECEIVED => [self::RETURNED, self::REFUNDED],
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
     * Get statuses that should be excluded from financial/reporting calculations.
     *
     * These statuses represent purchases that should not be included in reports:
     * - Draft: Not finalized
     * - Cancelled: Never completed
     * - Void/Voided: Invalidated
     * - Returned: Goods returned to supplier
     * - Refunded: Money returned from supplier
     *
     * @return array<string> Array of status values for use in whereNotIn queries
     */
    public static function nonRelevantStatuses(): array
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
     * Check if this status is a non-relevant status.
     */
    public function isNonRelevant(): bool
    {
        return in_array($this->value, self::nonRelevantStatuses());
    }

    /**
     * Get statuses that represent completed/relevant purchases.
     *
     * @return array<string> Array of status values for use in whereIn queries
     */
    public static function relevantStatuses(): array
    {
        return [
            self::APPROVED->value,
            self::RECEIVED->value,
            self::PARTIALLY_RECEIVED->value,
        ];
    }
}
