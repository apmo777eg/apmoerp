<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AssetDepreciation - Asset depreciation record model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Asset depreciation records must be isolated by branch for accurate
 * financial reporting per branch.
 */
class AssetDepreciation extends Model
{
    use HasBranch;
    protected $fillable = [
        'asset_id',
        'branch_id',
        'depreciation_date',
        'period',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'journal_entry_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'book_value' => 'decimal:4',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
     * Check if depreciation is posted
     */
    public function isPosted(): bool
    {
        return $this->status === 'posted' && $this->journal_entry_id !== null;
    }

    /**
     * Scope for posted depreciations
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope for a specific period
     */
    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('period', $period);
    }
}
