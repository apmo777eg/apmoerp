<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'return_number',
        'sale_id',
        'branch_id',
        'warehouse_id',
        'customer_id',
        'return_type',
        'status',
        'reason',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'refund_amount',
        'currency',
        'refund_method',
        'notes',
        'internal_notes',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

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
     * V6-CRITICAL-08 FIX: Use database locking to prevent race conditions
     * V32-CRIT-03 FIX: Wrap in DB::transaction to ensure lockForUpdate is effective
     */
    public static function generateReturnNumber(?int $branchId = null): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($branchId) {
            $prefix = 'RET';
            $branchCode = $branchId ? str_pad($branchId, 3, '0', STR_PAD_LEFT) : '000';
            $date = now()->format('Ymd');

            // Use lockForUpdate to prevent race conditions during concurrent creation
            // V32-CRIT-03 FIX: The outer DB::transaction ensures the lock is effective
            $lastReturn = static::where('return_number', 'like', "{$prefix}-{$branchCode}-{$date}-%")
                ->lockForUpdate()
                ->orderBy('return_number', 'desc')
                ->first();

            if ($lastReturn) {
                $lastNumber = (int) substr($lastReturn->return_number, -4);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }

            return "{$prefix}-{$branchCode}-{$date}-{$newNumber}";
        });
    }

    /**
     * Relationships
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(ReturnRefund::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if return can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if return can be processed (refunded)
     */
    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Approve the return
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

        return true;
    }

    /**
     * Reject the return
     */
    public function reject(int $userId, ?string $reason = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'internal_notes' => $reason ? "Rejected: {$reason}" : $this->internal_notes,
        ]);

        return true;
    }

    /**
     * Complete the return (after refund processed)
     */
    public function complete(int $userId): bool
    {
        if (! $this->canBeProcessed()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_by' => $userId,
            'processed_at' => now(),
        ]);

        return true;
    }

    /**
     * Calculate total refund amount based on items
     */
    public function calculateTotals(): void
    {
        $items = $this->items;

        $this->subtotal = $items->sum(fn ($item) => $item->qty_returned * $item->unit_price);
        $this->tax_amount = $items->sum('tax_amount');
        $this->discount_amount = $items->sum('discount');
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;

        if ($this->refund_amount === 0) {
            $this->refund_amount = $this->total_amount;
        }

        $this->save();
    }
}
