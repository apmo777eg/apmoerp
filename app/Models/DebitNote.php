<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Debit Note Model
 *
 * Accounting documents for supplier returns and adjustments.
 * Tracks amounts to be credited back from suppliers.
 */
class DebitNote extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'debit_note_number',
        'purchase_return_id',
        'purchase_id',
        'branch_id',
        'supplier_id',
        'type',
        'status',
        'amount',
        'currency',
        'reason',
        'notes',
        'issue_date',
        'applied_date',
        'auto_apply',
        'applied_amount',
        'remaining_amount',
        'journal_entry_id',
        'posted_to_accounting',
        'posted_at',
        'created_by',
        'approved_by',
        'approved_at',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'issue_date' => 'date',
        'applied_date' => 'date',
        'auto_apply' => 'boolean',
        'posted_to_accounting' => 'boolean',
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_CANCELLED = 'cancelled';

    // Type constants
    public const TYPE_RETURN = 'return';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_DAMAGE = 'damage';

    public const TYPE_OTHER = 'other';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($debitNote) {
            if (empty($debitNote->debit_note_number)) {
                $debitNote->debit_note_number = static::generateDebitNoteNumber($debitNote->branch_id);
            }
            if (empty($debitNote->remaining_amount)) {
                $debitNote->remaining_amount = $debitNote->amount;
            }
        });
    }

    /**
     * Generate unique debit note number
     * V6-CRITICAL-08 FIX: Use database locking to prevent race conditions
     * V32-CRIT-03 FIX: Wrap in DB::transaction to ensure lockForUpdate is effective
     */
    public static function generateDebitNoteNumber(?int $branchId = null): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($branchId) {
            $prefix = 'DN';
            $branchCode = $branchId ? str_pad($branchId, 3, '0', STR_PAD_LEFT) : '000';
            $date = now()->format('Ymd');

            // Use lockForUpdate to prevent race conditions during concurrent creation
            // V32-CRIT-03 FIX: The outer DB::transaction ensures the lock is effective
            $lastNote = static::where('debit_note_number', 'like', "{$prefix}-{$branchCode}-{$date}-%")
                ->lockForUpdate()
                ->orderByDesc('debit_note_number')
                ->first();

            $sequence = $lastNote ? ((int) substr($lastNote->debit_note_number, -4)) + 1 : 1;

            return sprintf('%s-%s-%s-%04d', $prefix, $branchCode, $date, $sequence);
        });
    }

    // Relationships

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Status helper methods

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function canBeApplied(): bool
    {
        return $this->isApproved() && $this->remaining_amount > 0;
    }

    public function hasBalance(): bool
    {
        return $this->remaining_amount > 0;
    }

    // Scopes

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopePosted($query)
    {
        return $query->where('posted_to_accounting', true);
    }

    public function scopeNotPosted($query)
    {
        return $query->where('posted_to_accounting', false);
    }
}
