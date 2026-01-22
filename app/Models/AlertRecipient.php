<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRecipient extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'alert_instance_id',
        'branch_id',
        'user_id',
        'notification_sent',
        'email_sent',
        'read',
        'read_at',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'email_sent' => 'boolean',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the alert instance.
     */
    public function alertInstance(): BelongsTo
    {
        return $this->belongsTo(AlertInstance::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as read.
     */
    public function markRead(): void
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope: Unread alerts.
     */
    public function scopeUnread(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('read', false);
    }
}
