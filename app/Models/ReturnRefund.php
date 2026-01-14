<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRefund extends Model
{
    use HasFactory, HasBranch;

    /**
     * V9-CRITICAL-02 FIX: Added return_note_id to support both SalesReturn and ReturnNote refunds
     * - sales_return_id: FK to sales_returns table (advanced returns)
     * - return_note_id: FK to return_notes table (simple/legacy returns)
     */
    protected $fillable = [
        'sales_return_id',
        'return_note_id',  // V9-CRITICAL-02 FIX: Added for ReturnNote refunds
        'credit_note_id',
        'branch_id',
        'refund_method',
        'amount',
        'currency',
        'reference_number',
        'transaction_id',
        'status',
        'notes',
        'bank_name',
        'account_number',
        'card_last_four',
        'processed_by',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // Method constants
    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_STORE_CREDIT = 'store_credit';
    public const METHOD_ORIGINAL = 'original_method';

    /**
     * Relationships
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * V9-CRITICAL-02 FIX: Added relationship for ReturnNote refunds
     */
    public function returnNote(): BelongsTo
    {
        return $this->belongsTo(ReturnNote::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
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

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Mark refund as processing
     */
    public function markAsProcessing(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update(['status' => self::STATUS_PROCESSING]);
        return true;
    }

    /**
     * Complete the refund
     */
    public function complete(int $userId, ?string $transactionId = null): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_by' => $userId,
            'processed_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);

        return true;
    }

    /**
     * Mark refund as failed
     */
    public function markAsFailed(?string $reason = null): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason ? "Failed: {$reason}" : $this->notes,
        ]);

        return true;
    }
}
