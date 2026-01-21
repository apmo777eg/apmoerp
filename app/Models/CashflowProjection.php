<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CashflowProjection - Cash flow projection model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Cash flow projections must be isolated by branch to prevent cross-branch
 * access in financial reports and forecasting.
 */
class CashflowProjection extends Model
{
    use HasBranch;
    protected $fillable = [
        'branch_id',
        'projection_date',
        'period_type',
        'opening_balance',
        'expected_inflows',
        'expected_outflows',
        'projected_balance',
        'actual_balance',
        'variance',
        'inflow_breakdown',
        'outflow_breakdown',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'projection_date' => 'date',
        'opening_balance' => 'decimal:4',
        'expected_inflows' => 'decimal:4',
        'expected_outflows' => 'decimal:4',
        'projected_balance' => 'decimal:4',
        'actual_balance' => 'decimal:4',
        'variance' => 'decimal:4',
        'inflow_breakdown' => 'array',
        'outflow_breakdown' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if projection has been realized
     */
    public function isRealized(): bool
    {
        return $this->actual_balance !== null;
    }

    /**
     * Calculate variance
     */
    public function calculateVariance(): float
    {
        if ($this->actual_balance === null) {
            return 0;
        }

        return $this->actual_balance - $this->projected_balance;
    }

    /**
     * Scope for a date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('projection_date', [$startDate, $endDate]);
    }

    /**
     * Scope by period type
     */
    public function scopeByPeriodType(Builder $query, string $type): Builder
    {
        return $query->where('period_type', $type);
    }
}
