<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FiscalPeriod - Accounting fiscal period model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Fiscal periods must be isolated by branch for proper financial reporting.
 */
class FiscalPeriod extends Model
{
    use HasBranch;
    protected $fillable = [
        'branch_id',
        'year',
        'period',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('status', 'locked');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Get current fiscal period
     */
    public static function getCurrentPeriod(?int $branchId = null): ?self
    {
        $query = static::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('status', 'open');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->first();
    }
}
