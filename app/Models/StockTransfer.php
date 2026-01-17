<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',  // NEW-HIGH-08 FIX: Added branch_id to fillable for HasBranch trait
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'from_branch_id',
        'to_branch_id',
        'transfer_type',
        'status',
        'transfer_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'priority',
        'reason',
        'notes',
        'internal_notes',
        'tracking_number',
        'courier_name',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'shipping_cost',
        'insurance_cost',
        'total_cost',
        'currency',
        'total_qty_requested',
        'total_qty_shipped',
        'total_qty_received',
        'total_qty_damaged',
        'requested_by',
        'approved_by',
        'approved_at',
        'shipped_by',
        'shipped_at',
        'received_by',
        'received_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'shipping_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_qty_requested' => 'decimal:3',
        'total_qty_shipped' => 'decimal:3',
        'total_qty_received' => 'decimal:3',
        'total_qty_damaged' => 'decimal:3',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REJECTED = 'rejected';

    // Priority constants
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    // Type constants
    public const TYPE_INTER_WAREHOUSE = 'inter_warehouse';

    public const TYPE_INTER_BRANCH = 'inter_branch';

    public const TYPE_INTERNAL = 'internal';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = static::generateTransferNumber();
            }
        });
    }

    /**
     * Generate unique transfer number
     * NEW-HIGH-08 FIX: Use database locking to prevent race conditions
     * V32-CRIT-03 FIX: Wrap in DB::transaction to ensure lockForUpdate is effective
     */
    public static function generateTransferNumber(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $prefix = 'TRF';
            $date = now()->format('Ymd');

            // Use lockForUpdate to prevent concurrent reads from getting the same number
            // V32-CRIT-03 FIX: The outer DB::transaction ensures the lock is effective
            $lastTransfer = static::where('transfer_number', 'like', "{$prefix}-{$date}-%")
                ->lockForUpdate()
                ->orderBy('transfer_number', 'desc')
                ->first();

            if ($lastTransfer) {
                $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }

            return "{$prefix}-{$date}-{$newNumber}";
        });
    }

    /**
     * Relationships
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(StockTransferApproval::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StockTransferDocument::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(StockTransferHistory::class)->orderBy('changed_at', 'desc');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Check if transfer can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transfer can be shipped
     */
    public function canBeShipped(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if transfer can be received
     */
    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    /**
     * Approve transfer
     */
    public function approve(int $userId): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        $this->recordStatusChange(self::STATUS_PENDING, self::STATUS_APPROVED, $userId);

        return true;
    }

    /**
     * Mark as shipped
     */
    public function markAsShipped(int $userId, ?array $shippingData = null): bool
    {
        if (! $this->canBeShipped()) {
            return false;
        }

        $updateData = [
            'status' => self::STATUS_IN_TRANSIT,
            'shipped_by' => $userId,
            'shipped_at' => now(),
        ];

        if ($shippingData) {
            $updateData = array_merge($updateData, array_filter([
                'tracking_number' => $shippingData['tracking_number'] ?? null,
                'courier_name' => $shippingData['courier_name'] ?? null,
                'vehicle_number' => $shippingData['vehicle_number'] ?? null,
                'driver_name' => $shippingData['driver_name'] ?? null,
                'driver_phone' => $shippingData['driver_phone'] ?? null,
            ]));
        }

        $this->update($updateData);
        $this->recordStatusChange(self::STATUS_APPROVED, self::STATUS_IN_TRANSIT, $userId);

        return true;
    }

    /**
     * Mark as received
     */
    public function markAsReceived(int $userId): bool
    {
        if (! $this->canBeReceived()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_RECEIVED,
            'received_by' => $userId,
            'received_at' => now(),
            'actual_delivery_date' => now()->toDateString(),
        ]);

        $this->recordStatusChange(self::STATUS_IN_TRANSIT, self::STATUS_RECEIVED, $userId);

        return true;
    }

    /**
     * Complete transfer (after all items processed)
     *
     * V32-HIGH-03 FIX: Accept userId parameter instead of using auth()->id() directly.
     * This allows the method to work correctly in CLI/queue/webhook contexts
     * where there is no authenticated user.
     *
     * @param  int|null  $userId  User ID for audit trail. Falls back to auth()->id() if null.
     */
    public function complete(?int $userId = null): bool
    {
        if ($this->status !== self::STATUS_RECEIVED) {
            return false;
        }

        $this->update(['status' => self::STATUS_COMPLETED]);
        // V32-HIGH-03 FIX: Use provided userId or fall back to auth()->id()
        $this->recordStatusChange(self::STATUS_RECEIVED, self::STATUS_COMPLETED, $userId ?? auth()->id());

        return true;
    }

    /**
     * Reject transfer
     */
    public function reject(int $userId, ?string $reason = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'internal_notes' => $reason ? "Rejected: {$reason}" : $this->internal_notes,
        ]);

        $this->recordStatusChange(self::STATUS_PENDING, self::STATUS_REJECTED, $userId, $reason);

        return true;
    }

    /**
     * Cancel transfer
     */
    public function cancel(int $userId, ?string $reason = null): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }

        $oldStatus = $this->status;
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'internal_notes' => $reason ? "Cancelled: {$reason}" : $this->internal_notes,
        ]);

        $this->recordStatusChange($oldStatus, self::STATUS_CANCELLED, $userId, $reason);

        return true;
    }

    /**
     * Record status change in history
     */
    protected function recordStatusChange(string $fromStatus, string $toStatus, ?int $userId = null, ?string $notes = null): void
    {
        StockTransferHistory::create([
            'stock_transfer_id' => $this->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'changed_by' => $userId,
            'changed_at' => now(),
        ]);
    }

    /**
     * Calculate transfer totals
     */
    public function calculateTotals(): void
    {
        $this->total_qty_requested = $this->items->sum('qty_requested');
        $this->total_qty_shipped = $this->items->sum('qty_shipped');
        $this->total_qty_received = $this->items->sum('qty_received');
        $this->total_qty_damaged = $this->items->sum('qty_damaged');
        $this->save();
    }

    /**
     * Check if transfer is overdue
     */
    public function isOverdue(): bool
    {
        if (! $this->expected_delivery_date || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return now()->isAfter($this->expected_delivery_date);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): float
    {
        if ($this->total_qty_requested <= 0) {
            return 0;
        }

        return ($this->total_qty_received / $this->total_qty_requested) * 100;
    }
}
