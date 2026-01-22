<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * V65-BUG-FIX: Added HasBranch trait for proper branch scoping.
 */
class TicketReply extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $table = 'ticket_replies';

    protected $fillable = [
        'branch_id',
        'ticket_id',
        'user_id',
        'message',
        'is_internal',
        'read_at',
        'customer_email',
        'customer_name',
        'ip_address',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'reply_id');
    }

    // Scopes
    public function scopeCustomerReplies(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternalNotes(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    // Business Methods
    public function isFromCustomer(): bool
    {
        return $this->ticket && $this->user_id === $this->ticket->customer_id;
    }

    public function isFromAgent(): bool
    {
        return ! $this->isFromCustomer();
    }

    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    public function markAsRead()
    {
        if (! $this->read_at) {
            $this->read_at = now();
            $this->save();
        }

        return $this;
    }
}
