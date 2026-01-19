<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HelpdeskService
{
    /**
     * Create a new ticket
     */
    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::create($data);

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log('Ticket created');

            // Clear cache
            $this->clearTicketStatsCache();

            return $ticket;
        });
    }

    /**
     * Update an existing ticket
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $changes = [];

            foreach ($data as $key => $value) {
                if ($ticket->{$key} !== $value) {
                    $changes[$key] = ['from' => $ticket->{$key}, 'to' => $value];
                }
            }

            $ticket->update($data);

            // Log activity
            if (! empty($changes)) {
                activity()
                    ->performedOn($ticket)
                    ->causedBy(auth()->user())
                    ->withProperties(['changes' => $changes])
                    ->log('Ticket updated');
            }

            // Clear cache
            $this->clearTicketStatsCache();

            return $ticket->fresh();
        });
    }

    /**
     * Assign ticket to an agent
     */
    public function assignTicket(Ticket $ticket, int $userId): Ticket
    {
        $assignee = User::findOrFail($userId);
        $actor = auth()->user();

        if (
            $ticket->branch_id
            && $assignee->branch_id
            && $ticket->branch_id !== $assignee->branch_id
            && ! $actor?->hasAnyRole(['Super Admin', 'super-admin'])
        ) {
            throw new AuthorizationException('You cannot assign this ticket outside its branch.');
        }

        return DB::transaction(function () use ($ticket, $userId) {
            $oldAssignee = $ticket->assigned_to;
            $ticket->assigned_to = $userId;
            $ticket->save();

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_assignee' => $oldAssignee,
                    'new_assignee' => $userId,
                ])
                ->log('Ticket assigned');

            // Clear cache
            $this->clearTicketStatsCache();

            return $ticket->fresh();
        });
    }

    /**
     * Add a reply to a ticket
     */
    public function addReply(Ticket $ticket, array $data): TicketReply
    {
        return DB::transaction(function () use ($ticket, $data) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $reply = $ticket->addReply(
                $data['message'],
                $data['user_id'] ?? actual_user_id(),
                $data['is_internal'] ?? false
            );

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reply_id' => $reply->id,
                    'is_internal' => $reply->is_internal,
                ])
                ->log($reply->is_internal ? 'Internal note added' : 'Reply added');

            return $reply;
        });
    }

    /**
     * Close a ticket
     */
    public function closeTicket(Ticket $ticket, ?string $note = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $note) {
            $ticket->close();

            if ($note) {
                $this->addReply($ticket, [
                    'message' => $note,
                    'is_internal' => false,
                ]);
            }

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log('Ticket closed');

            // Clear cache
            $this->clearTicketStatsCache();

            return $ticket->fresh();
        });
    }

    /**
     * Reopen a ticket
     */
    public function reopenTicket(Ticket $ticket, ?string $reason = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $reason) {
            $ticket->reopen();

            if ($reason) {
                $this->addReply($ticket, [
                    'message' => $reason,
                    'is_internal' => false,
                ]);
            }

            // Log activity
            activity()
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log('Ticket reopened');

            // Clear cache
            $this->clearTicketStatsCache();

            return $ticket->fresh();
        });
    }

    /**
     * Calculate SLA compliance for a ticket
     */
    public function calculateSLA(Ticket $ticket): array
    {
        if (! $ticket->slaPolicy) {
            return [
                'has_sla' => false,
                'response_sla_met' => null,
                'resolution_sla_met' => null,
            ];
        }

        $slaPolicy = $ticket->slaPolicy;
        $result = ['has_sla' => true];

        // Check response SLA
        if ($ticket->first_response_at) {
            $responseTime = $ticket->created_at->diffInMinutes($ticket->first_response_at);
            $result['response_sla_met'] = $responseTime <= $slaPolicy->response_time_minutes;
            $result['response_time_minutes'] = $responseTime;
        } else {
            $result['response_sla_met'] = false;
            $result['response_overdue'] = ! in_array($ticket->status, ['resolved', 'closed']);
        }

        // Check resolution SLA
        if ($ticket->resolved_at) {
            $resolutionTime = $ticket->created_at->diffInMinutes($ticket->resolved_at);
            $result['resolution_sla_met'] = $resolutionTime <= $slaPolicy->resolution_time_minutes;
            $result['resolution_time_minutes'] = $resolutionTime;
        } else {
            $result['resolution_sla_met'] = false;
            $result['resolution_overdue'] = ! in_array($ticket->status, ['resolved', 'closed']);
        }

        return $result;
    }

    /**
     * Get ticket statistics
     */
    public function getTicketStats(?int $branchId = null, ?int $userId = null): array
    {
        $cacheKey = "ticket_stats_{$branchId}_{$userId}";
        $cache = Cache::getStore() instanceof TaggableStore
            ? Cache::tags('ticket_stats')
            : Cache::store();

        return $cache->remember($cacheKey, 300, function () use ($branchId, $userId) {
            $query = Ticket::query();

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($userId) {
                $query->where('assigned_to', $userId);
            }

            // V23-HIGH-06 FIX: Clone the query builder for each metric to prevent
            // accumulating WHERE conditions that would make later counts incorrect
            return [
                'total' => (clone $query)->count(),
                'new' => (clone $query)->where('status', 'new')->count(),
                'open' => (clone $query)->where('status', 'open')->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'resolved' => (clone $query)->where('status', 'resolved')->count(),
                'closed' => (clone $query)->where('status', 'closed')->count(),
                'overdue' => (clone $query)->overdue()->count(),
                'unassigned' => (clone $query)->whereNull('assigned_to')->count(),
                'avg_response_time' => $this->getAverageResponseTime($branchId, $userId),
                'avg_resolution_time' => $this->getAverageResolutionTime($branchId, $userId),
            ];
        });
    }

    /**
     * Get average response time in minutes
     */
    protected function getAverageResponseTime(?int $branchId = null, ?int $userId = null): ?float
    {
        $query = Ticket::query()->whereNotNull('first_response_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return $ticket->getResponseTime();
        });

        return decimal_float(bcdiv((string) $totalMinutes, (string) $tickets->count(), 2));
    }

    /**
     * Get average resolution time in minutes
     */
    protected function getAverageResolutionTime(?int $branchId = null, ?int $userId = null): ?float
    {
        $query = Ticket::query()->whereNotNull('resolved_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return $ticket->getResolutionTime();
        });

        return decimal_float(bcdiv((string) $totalMinutes, (string) $tickets->count(), 2));
    }

    /**
     * Clear ticket statistics cache
     */
    protected function clearTicketStatsCache(): void
    {
        $cache = Cache::getStore();

        if ($cache instanceof TaggableStore) {
            Cache::tags('ticket_stats')->flush();

            return;
        }

        // Fallback: clear entire cache to avoid serving stale statistics when tags are unavailable
        Cache::flush();
    }
}
