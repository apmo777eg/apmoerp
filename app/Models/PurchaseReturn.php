<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase Return Model
 *
 * Manages returns to suppliers for defective, damaged, wrong items, or excess inventory.
 * Integrates with GRN for quality control and generates debit notes for accounting.
 */
class PurchaseReturn extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'return_number',
        'purchase_id',
        'grn_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'return_type',
        'status',
        'reason',
        'subtotal',
        'tax_amount',
        'total_amount',
        'expected_credit',
        'currency',
        'notes',
        'internal_notes',
        'return_date',
        'tracking_number',
        'courier_name',
        'shipped_date',
        'received_by_supplier_date',
        'approved_by',
        'approved_at',
        'shipped_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expected_credit' => 'decimal:2',
        'return_date' => 'date',
        'shipped_date' => 'date',
        'received_by_supplier_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    // Type constants
    public const TYPE_FULL = 'full';

    public const TYPE_PARTIAL = 'partial';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = static::generateReturnNumber($return->branch_id);
            }
        });
    }

    /**
     * Generate unique return number
     * V6-CRITICAL-08 FIX: Use database locking to prevent race conditions and scope by branch
     * V32-CRIT-03 FIX: Wrap in DB::transaction to ensure lockForUpdate is effective
     */
    public static function generateReturnNumber(?int $branchId = null): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($branchId) {
            $prefix = 'PR';
            $branchCode = $branchId ? str_pad($branchId, 3, '0', STR_PAD_LEFT) : '000';
            $date = now()->format('Ymd');

            // Use lockForUpdate to prevent race conditions during concurrent creation
            // Also properly scope by branch_id in the query
            // V32-CRIT-03 FIX: The outer DB::transaction ensures the lock is effective
            $lastReturn = static::where('return_number', 'like', "{$prefix}-{$branchCode}-{$date}-%")
                ->lockForUpdate()
                ->orderByDesc('return_number')
                ->first();

            $sequence = $lastReturn ? ((int) substr($lastReturn->return_number, -4)) + 1 : 1;

            return sprintf('%s-%s-%s-%04d', $prefix, $branchCode, $date, $sequence);
        });
    }

    // Relationships

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function debitNote(): HasOne
    {
        return $this->hasOne(DebitNote::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Status helper methods

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeShipped(): bool
    {
        return $this->isApproved();
    }

    public function canBeCompleted(): bool
    {
        return $this->isShipped();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    // Type helper methods

    public function isFullReturn(): bool
    {
        return $this->return_type === self::TYPE_FULL;
    }

    public function isPartialReturn(): bool
    {
        return $this->return_type === self::TYPE_PARTIAL;
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeShipped($query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('return_date', [$startDate, $endDate]);
    }
}
