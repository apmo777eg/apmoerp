<?php

namespace App\Models;

use App\Traits\HasBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket Model
 *
 * V55-CRITICAL-04 FIX: Added HasBranch trait for branch scoping
 * This prevents cross-branch data access and IDOR vulnerabilities.
 */
class Ticket extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'status',
        'priority_id',
        'customer_id',
        'assigned_to',
        'category_id',
        'sla_policy_id',
        'branch_id',
        'due_date',
        'resolved_at',
        'closed_at',
        'first_response_at',
        'satisfaction_rating',
        'satisfaction_comment',
        'tags',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'first_response_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
        'satisfaction_rating' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'TKT-'.date('Ymd').'-'.str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            }

            // Calculate due date based on SLA policy
            if ($ticket->sla_policy_id && ! $ticket->due_date) {
                $slaPolicy = TicketSLAPolicy::find($ticket->sla_policy_id);
                if ($slaPolicy) {
                    $ticket->due_date = $slaPolicy->calculateDueDate($ticket->priority_id);
                }
            }
        });
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function priority()
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function slaPolicy()
    {
        return $this->belongsTo(TicketSLAPolicy::class, 'sla_policy_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedTo(Builder $query, $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    // Business Methods
    public function assign($userId)
    {
        $this->assigned_to = $userId;
        $this->save();

        return $this;
    }

    public function resolve($resolutionNote = null)
    {
        $this->status = 'resolved';
        $this->resolved_at = now();
        $this->save();

        if ($resolutionNote) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $this->addReply($resolutionNote, $this->assigned_to ?? actual_user_id(), false);
        }

        return $this;
    }

    public function close()
    {
        $this->status = 'closed';
        $this->closed_at = now();
        $this->save();

        return $this;
    }

    public function reopen()
    {
        $this->status = 'open';
        $this->resolved_at = null;
        $this->closed_at = null;
        $this->save();

        return $this;
    }

    public function addReply($message, $userId, $isInternal = false)
    {
        $reply = new TicketReply([
            'message' => $message,
            'user_id' => $userId,
            'is_internal' => $isInternal,
        ]);

        $this->replies()->save($reply);

        // Mark first response time if not set
        if (! $this->first_response_at && ! $isInternal) {
            $this->first_response_at = now();
            $this->save();
        }

        return $reply;
    }

    public function getRemainingTime()
    {
        if (! $this->due_date || in_array($this->status, ['resolved', 'closed'])) {
            return null;
        }

        $now = Carbon::now();
        if ($this->due_date < $now) {
            return 'Overdue';
        }

        return $this->due_date->diffForHumans($now, true);
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date || in_array($this->status, ['resolved', 'closed'])) {
            return false;
        }

        return $this->due_date < Carbon::now();
    }

    public function getResponseTime()
    {
        if (! $this->first_response_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->first_response_at);
    }

    public function getResolutionTime()
    {
        if (! $this->resolved_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at);
    }

    public function getAgingDays(): int
    {
        if ($this->closed_at) {
            return $this->created_at->diffInDays($this->closed_at);
        }

        return $this->created_at->diffInDays(Carbon::now());
    }

    public function canBeClosed(): bool
    {
        return in_array($this->status, ['resolved']);
    }

    public function canBeReopened(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }
}
