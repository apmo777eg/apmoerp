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
class WorkflowNotification extends Model
{
    use HasBranch;

    protected $fillable = [
        'branch_id',
        'workflow_instance_id',
        'workflow_approval_id',
        'user_id',
        'type',
        'channel',
        'message',
        'metadata',
        'is_sent',
        'delivery_status',
        'priority',
        'delivered_at',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'metadata' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowApproval(): BelongsTo
    {
        return $this->belongsTo(WorkflowApproval::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_sent', false);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('is_sent', true);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
            'sent_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
        ]);
    }
}
