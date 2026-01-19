<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transfer Model - Basic warehouse transfers
 * 
 * For basic stock transfers between warehouses. For advanced transfers with
 * multi-level approvals, documents, and detailed tracking, use StockTransfer model.
 * 
 * @property int $id
 * @property int $branch_id
 * @property string $reference_number
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property string $status
 * @property string|null $notes
 * @property float $total_value
 * @property \Carbon\Carbon|null $shipped_at
 * @property \Carbon\Carbon|null $received_at
 * @property int|null $created_by
 * @property int|null $received_by
 */
class Transfer extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'inventory';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000003_create_inventory_tables.php
     */
    protected $fillable = [
        'branch_id',
        'reference_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'notes',
        'total_value',
        'shipped_at',
        'received_at',
        'created_by',
        'received_by',
        // For BaseModel compatibility
        'extra_attributes',
    ];

    protected $casts = [
        'total_value' => 'decimal:4',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'extra_attributes' => 'array',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Scopes
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInTransit(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Helper Methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInTransit(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeShipped(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_TRANSIT]);
    }

    /**
     * Mark as shipped
     * V23-HIGH-05 FIX: Remove created_by update - it should be immutable
     * The user who ships is not the creator
     */
    public function ship(?int $userId = null): bool
    {
        if (!$this->canBeShipped()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_IN_TRANSIT,
            'shipped_at' => now(),
        ]);
    }

    /**
     * Mark as received/completed
     * 
     * V29-MED-04 FIX: Require explicit userId parameter instead of falling back to auth()->id().
     * This prevents potential null values when called from CLI/queue contexts without authentication.
     * The service/controller layer is responsible for providing the userId.
     */
    public function receive(int $userId): bool
    {
        if (!$this->canBeReceived()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'received_at' => now(),
            'received_by' => $userId,
        ]);
    }

    /**
     * Cancel transfer
     */
    public function cancel(?int $userId = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Calculate total value from items
     */
    public function calculateTotalValue(): float
    {
        return decimal_float($this->items()
            ->selectRaw('SUM(quantity * unit_cost) as total')
            ->value('total') ?? 0.0);
    }

    /**
     * Update total value
     */
    public function updateTotalValue(): bool
    {
        $this->total_value = $this->calculateTotalValue();
        return $this->save();
    }

    // Backward compatibility accessor
    public function getNoteAttribute()
    {
        return $this->notes;
    }
}
