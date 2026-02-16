<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Purchase Status Enum
 *
 * Represents all possible states of a purchase order.
 *
 * Notes:
 * - Workflow statuses in this codebase appear as: draft, pending, posted, received, completed, cancelled
 * - Some legacy/integration flows may also use: approved, confirmed, partially_received
 *
 * We keep the enum inclusive to avoid breaking existing data/reporting.
 */
enum PurchaseStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';

    // Legacy / integration
    case APPROVED = 'approved';
    case CONFIRMED = 'confirmed';
    case POSTED = 'posted';

    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case COMPLETED = 'completed';

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
            self::PENDING => __('Pending'),
            self::APPROVED => __('Approved'),
            self::CONFIRMED => __('Confirmed'),
            self::POSTED => __('Posted'),
            self::PARTIALLY_RECEIVED => __('Partially Received'),
            self::RECEIVED => __('Received'),
            self::COMPLETED => __('Completed'),
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
            self::PENDING => 'amber',
            self::APPROVED, self::CONFIRMED, self::POSTED => 'blue',
            self::PARTIALLY_RECEIVED => 'amber',
            self::RECEIVED, self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::VOID, self::VOIDED => 'gray',
            self::RETURNED => 'orange',
            self::REFUNDED => 'purple',
        };
    }

    /**
     * Check if status is final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::VOID,
            self::VOIDED,
            self::RETURNED,
            self::REFUNDED,
        ], true);
    }

    /**
     * Allowed next statuses (guideline).
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING, self::APPROVED, self::CONFIRMED, self::POSTED, self::CANCELLED, self::VOID],
            self::PENDING => [self::APPROVED, self::CONFIRMED, self::POSTED, self::CANCELLED, self::VOID],
            self::APPROVED => [self::CONFIRMED, self::POSTED, self::PARTIALLY_RECEIVED, self::RECEIVED, self::CANCELLED, self::VOID],
            self::CONFIRMED => [self::POSTED, self::PARTIALLY_RECEIVED, self::RECEIVED, self::CANCELLED, self::VOID],
            self::POSTED => [self::PARTIALLY_RECEIVED, self::RECEIVED, self::COMPLETED, self::CANCELLED, self::VOID],
            self::PARTIALLY_RECEIVED => [self::RECEIVED, self::COMPLETED, self::CANCELLED, self::VOID],
            self::RECEIVED => [self::COMPLETED, self::RETURNED, self::REFUNDED],
            self::COMPLETED => [self::RETURNED, self::REFUNDED],
            self::CANCELLED, self::VOID, self::VOIDED, self::RETURNED, self::REFUNDED => [],
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
     * All enum values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }

    /**
     * Get statuses that should be excluded from financial/reporting calculations.
     *
     * @return array<string>
     */
    public static function nonRelevantStatuses(): array
    {
        return [
            self::DRAFT->value,
            self::PENDING->value,
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
        return in_array($this->value, self::nonRelevantStatuses(), true);
    }

    /**
     * Statuses that represent relevant purchases.
     *
     * @return array<string>
     */
    public static function relevantStatuses(): array
    {
        return [
            self::APPROVED->value,
            self::CONFIRMED->value,
            self::POSTED->value,
            self::PARTIALLY_RECEIVED->value,
            self::RECEIVED->value,
            self::COMPLETED->value,
        ];
    }
}
