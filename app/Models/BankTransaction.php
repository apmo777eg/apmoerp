<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BankTransaction - Bank transaction record model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Bank transactions must be isolated by branch to prevent cross-branch data leakage
 * in banking reports and reconciliation.
 */
class BankTransaction extends Model
{
    use HasBranch;
    protected $fillable = [
        'bank_account_id',
        'branch_id',
        'reference_number',
        'transaction_date',
        'value_date',
        'type',
        'amount',
        'balance_after',
        'payee_payer',
        'category',
        'description',
        'status',
        'reconciliation_id',
        'journal_entry_id',
        'related_type',
        'related_id',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (! $transaction->reference_number) {
                $transaction->reference_number = 'BTX-'.date('Ymd').'-'.uniqid();
            }
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if transaction is reconciled
     */
    public function isReconciled(): bool
    {
        return $this->status === 'reconciled' && $this->reconciliation_id !== null;
    }

    /**
     * Check if transaction is a deposit
     */
    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }

    /**
     * Check if transaction is a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return $this->type === 'withdrawal';
    }

    /**
     * Get signed amount (positive for deposits, negative for withdrawals)
     */
    public function getSignedAmount(): float
    {
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $amount = decimal_float($this->amount);
        if ($this->isDeposit() || $this->type === 'interest') {
            return $amount;
        }

        return -$amount;
    }

    /**
     * Scope for unreconciled transactions
     */
    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'cleared'])
            ->whereNull('reconciliation_id');
    }

    /**
     * Scope for a date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
