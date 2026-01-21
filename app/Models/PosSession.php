<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PosSession - Point of Sale session model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * POS sessions must be isolated by branch to prevent cross-branch data access
 * and ensure proper cash handling accountability.
 */
class PosSession extends Model
{
    use HasBranch;
    protected $fillable = [
        'branch_id',
        'user_id',
        'session_number',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'payment_summary',
        'total_transactions',
        'total_sales',
        'total_refunds',
        'status',
        'opened_at',
        'closed_at',
        'closing_notes',
        'closed_by',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:4',
        'closing_cash' => 'decimal:4',
        'expected_cash' => 'decimal:4',
        'cash_difference' => 'decimal:4',
        'total_sales' => 'decimal:4',
        'total_refunds' => 'decimal:4',
        'payment_summary' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
