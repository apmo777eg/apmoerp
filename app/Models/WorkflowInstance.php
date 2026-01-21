<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * WorkflowInstance - Workflow instance model
 *
 * V56-CRITICAL-03 FIX: Added HasBranch trait for proper multi-branch scoping.
 * Workflow instances must be isolated by branch to prevent cross-branch
 * access to approval workflows and process tracking.
 */
class WorkflowInstance extends Model
{
    use HasBranch;
    protected $fillable = [
        'workflow_definition_id',
        'branch_id',
        'entity_type',
        'entity_id',
        'current_stage',
        'status',
        'initiated_by',
        'initiated_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(WorkflowNotification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(WorkflowAuditLog::class);
    }

    /**
     * Get the entity being approved
     */
    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'cancelled']);
    }

    /**
     * Get current pending approval
     */
    public function currentApproval(): ?WorkflowApproval
    {
        return $this->approvals()
            ->where('status', 'pending')
            ->orderBy('stage_order')
            ->first();
    }
}
