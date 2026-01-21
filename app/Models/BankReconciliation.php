<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BankReconciliation - Bank reconciliation model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Bank reconciliations must be isolated by branch to prevent cross-branch access
 * in financial reports and audit trails.
 */
class BankReconciliation extends Model
{
    use HasBranch;
    protected $fillable = [
        'bank_account_id',
        'branch_id',
        'reconciliation_number',
        'statement_date',
        'reconciliation_date',
        'statement_balance',
        'book_balance',
        'difference',
        'status',
        'notes',
        'adjustments',
        'reconciled_by',
        'approved_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'reconciliation_date' => 'date',
        'statement_balance' => 'decimal:4',
        'book_balance' => 'decimal:4',
        'difference' => 'decimal:4',
        'adjustments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reconciliation) {
            if (! $reconciliation->reconciliation_number) {
                $reconciliation->reconciliation_number = 'RECON-'.date('Ymd').'-'.uniqid();
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

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'reconciliation_id');
    }

    /**
     * Check if reconciliation is balanced
     */
    public function isBalanced(): bool
    {
        return abs($this->difference) < 0.01;
    }

    /**
     * Check if reconciliation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if reconciliation is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->approved_by !== null;
    }

    /**
     * Scope for completed reconciliations
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
