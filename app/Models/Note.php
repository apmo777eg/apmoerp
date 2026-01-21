<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Note - Polymorphic note model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Notes must be isolated by branch to prevent cross-branch data access.
 */
class Note extends Model
{
    use HasBranch;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'noteable_type',
        'noteable_id',
        'content',
        'type',
        'is_pinned',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
