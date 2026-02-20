<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class WorkflowApproval extends Model
{
    use HasBranch;

    protected $fillable = [
        'branch_id',
        'workflow_instance_id',
        'stage_name',
        'stage_order',
        'approver_id',
        'approver_role',
        'status',
        'comments',
        'requested_at',
        'responded_at',
        'additional_data',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'additional_data' => 'array',
    ];

    /**
     * Ensure branch_id is derived from the parent workflow instance when not provided.
     *
     * These records are often created from jobs/console where BranchContextManager may be missing.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->branch_id !== null) {
                return;
            }

            if ($model->workflow_instance_id) {
                $instance = WorkflowInstance::withoutBranchScope()->find($model->workflow_instance_id);
                if ($instance && $instance->branch_id) {
                    $model->branch_id = $instance->branch_id;
                }
            }
        });
    }


    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
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

    public function isResolved(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'skipped']);
    }
}
