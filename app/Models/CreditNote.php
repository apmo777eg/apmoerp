<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'credit_note_number',
        'sales_return_id',
        'sale_id',
        'branch_id',
        'customer_id',
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

    public const TYPE_REFUND = 'refund';

    public const TYPE_OTHER = 'other';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditNote) {
            if (empty($creditNote->credit_note_number)) {
                $creditNote->credit_note_number = static::generateCreditNoteNumber($creditNote->branch_id);
            }

            if (empty($creditNote->remaining_amount)) {
                $creditNote->remaining_amount = $creditNote->amount;
            }
        });
    }

    /**
     * Generate unique credit note number
     * V6-CRITICAL-08 FIX: Use database locking to prevent race conditions
     * V32-CRIT-03 FIX: Wrap in DB::transaction to ensure lockForUpdate is effective
     */
    public static function generateCreditNoteNumber(?int $branchId = null): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($branchId) {
            $prefix = 'CN';
            $branchCode = $branchId ? str_pad($branchId, 3, '0', STR_PAD_LEFT) : '000';
            $date = now()->format('Ymd');

            // Use lockForUpdate to prevent race conditions during concurrent creation
            // V32-CRIT-03 FIX: The outer DB::transaction ensures the lock is effective
            $lastCN = static::where('credit_note_number', 'like', "{$prefix}-{$branchCode}-{$date}-%")
                ->lockForUpdate()
                ->orderBy('credit_note_number', 'desc')
                ->first();

            if ($lastCN) {
                $lastNumber = (int) substr($lastCN->credit_note_number, -4);
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
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_APPLIED])
            ->where('remaining_amount', '>', 0);
    }

    /**
     * Approve credit note
     */
    public function approve(int $userId): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
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
     * Apply credit note to a sale or customer balance
     */
    public function apply(float $amount, ?int $saleId = null, ?int $userId = null): bool
    {
        if ($this->status !== self::STATUS_APPROVED && $this->status !== self::STATUS_APPLIED) {
            return false;
        }

        if ($amount > $this->remaining_amount) {
            return false;
        }

        // Create application record
        CreditNoteApplication::create([
            'credit_note_id' => $this->id,
            'sale_id' => $saleId,
            'applied_amount' => $amount,
            'application_date' => now()->toDateString(),
            'applied_by' => $userId,
        ]);

        // Update credit note
        $this->applied_amount = bcadd((string) $this->applied_amount, (string) $amount, 2);
        $this->remaining_amount = bcsub((string) $this->remaining_amount, (string) $amount, 2);
        $this->status = self::STATUS_APPLIED;

        if (! $this->applied_date) {
            $this->applied_date = now()->toDateString();
        }

        $this->save();

        return true;
    }

    /**
     * Check if credit note is fully utilized
     */
    public function isFullyUtilized(): bool
    {
        return bccomp((string) $this->remaining_amount, '0', 2) <= 0;
    }

    /**
     * Get available credit amount
     */
    public function getAvailableCredit(): float
    {
        return max(0, $this->remaining_amount);
    }

    /**
     * Post to accounting system
     */
    public function postToAccounting(int $journalEntryId): bool
    {
        if ($this->posted_to_accounting) {
            return false;
        }

        $this->update([
            'journal_entry_id' => $journalEntryId,
            'posted_to_accounting' => true,
            'posted_at' => now(),
        ]);

        return true;
    }
}
