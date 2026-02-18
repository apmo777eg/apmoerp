<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BankAccount - Bank account management
 *
 * Extends BaseModel for:
 * - Automatic branch scoping
 * - Common query scopes (active, forBranch, etc.)
 * - Dynamic fields support
 * - Audit logging
 */
class BankAccount extends BaseModel
{
    protected $fillable = [
        'branch_id',
        'account_number',
        'account_name',
        'bank_name',
        'bank_branch',
        'swift_code',
        'iban',
        'currency',
        'account_type',
        'opening_balance',
        'current_balance',
        'last_reconciled_at',
        'last_reconciled_balance',
        'opening_date',
        'status',
        'notes',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'last_reconciled_balance' => 'decimal:2',
        'last_reconciled_at' => 'datetime',
        'opening_date' => 'date',
        'meta' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get unreconciled transactions
     */
    public function unreconciledTransactions()
    {
        return $this->transactions()
            ->whereIn('status', ['pending', 'cleared'])
            ->whereNull('reconciliation_id');
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by currency
     */
    public function scopeByCurrency(\Illuminate\Database\Eloquent\Builder $query, string $currency): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('currency', $currency);
    }
}
