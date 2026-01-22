<?php

namespace App\Models;

use Carbon\Carbon;

/** @mixin \Illuminate\Database\Eloquent\Builder */
class TicketSLAPolicy extends BaseModel
{
    protected $table = 'ticket_sla_policies';

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'first_response_time_hours',
        'resolution_time_hours',
        'response_time_minutes',
        'resolution_time_minutes',
        'business_hours',
        'business_hours_only',
        'business_hours_start',
        'business_hours_end',
        'working_days',
        'exclude_weekends',
        'excluded_dates',
        'auto_escalate',
        'escalate_to_user_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'first_response_time_hours' => 'integer',
        'resolution_time_hours' => 'integer',
        'response_time_minutes' => 'integer',
        'resolution_time_minutes' => 'integer',
        'business_hours' => 'array',
        'working_days' => 'array',
        'excluded_dates' => 'array',
        'business_hours_only' => 'boolean',
        'exclude_weekends' => 'boolean',
        'auto_escalate' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }

    public function categories()
    {
        return $this->hasMany(TicketCategory::class, 'sla_policy_id');
    }

    // Scopes
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    // Business Methods
    public function calculateDueDate($priority = 'medium', $baseTime = null): Carbon
    {
        $baseTime = $baseTime ?? Carbon::now();
        $minutes = $this->resolution_time_minutes;

        // Adjust based on priority if needed
        $priorityModel = null;
        if ($priority instanceof TicketPriority) {
            $priorityModel = $priority;
        } elseif (is_numeric($priority)) {
            $priorityModel = TicketPriority::find((int) $priority);
        } elseif (is_string($priority)) {
            $priorityModel = TicketPriority::where('name', $priority)->first();
        }

        if ($priorityModel && $priorityModel->resolution_time_minutes) {
            $minutes = min($minutes, $priorityModel->resolution_time_minutes);
        }

        if (! $this->business_hours_only) {
            return $baseTime->copy()->addMinutes($minutes);
        }

        // Calculate considering business hours
        $dueDate = $baseTime->copy();
        $remainingMinutes = $minutes;
        $workingDays = $this->working_days ?? [1, 2, 3, 4, 5]; // Default Mon-Fri

        while ($remainingMinutes > 0) {
            // Skip to next business day if needed
            while (! in_array($dueDate->dayOfWeek, $workingDays)) {
                $dueDate->addDay()->setTime(
                    (int) substr($this->business_hours_start, 0, 2),
                    (int) substr($this->business_hours_start, 3, 2)
                );
            }

            $startHour = (int) substr($this->business_hours_start, 0, 2);
            $startMinute = (int) substr($this->business_hours_start, 3, 2);
            $endHour = (int) substr($this->business_hours_end, 0, 2);
            $endMinute = (int) substr($this->business_hours_end, 3, 2);

            // If before business hours, set to start
            if ($dueDate->hour < $startHour || ($dueDate->hour === $startHour && $dueDate->minute < $startMinute)) {
                $dueDate->setTime($startHour, $startMinute);
            }

            // If after business hours, move to next day
            if ($dueDate->hour > $endHour || ($dueDate->hour === $endHour && $dueDate->minute >= $endMinute)) {
                $dueDate->addDay()->setTime($startHour, $startMinute);

                continue;
            }

            // Calculate minutes until end of business day
            $endTime = $dueDate->copy()->setTime($endHour, $endMinute);
            $availableMinutes = $dueDate->diffInMinutes($endTime);

            if ($remainingMinutes <= $availableMinutes) {
                $dueDate->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutes;
                $dueDate->addDay()->setTime($startHour, $startMinute);
            }
        }

        return $dueDate;
    }

    public function isBusinessHour(?Carbon $time = null): bool
    {
        $time = $time ?? Carbon::now();

        if (! $this->business_hours_only) {
            return true;
        }

        $workingDays = $this->working_days ?? [1, 2, 3, 4, 5];
        if (! in_array($time->dayOfWeek, $workingDays)) {
            return false;
        }

        $startHour = (int) substr($this->business_hours_start, 0, 2);
        $startMinute = (int) substr($this->business_hours_start, 3, 2);
        $endHour = (int) substr($this->business_hours_end, 0, 2);
        $endMinute = (int) substr($this->business_hours_end, 3, 2);

        $currentMinutes = $time->hour * 60 + $time->minute;
        $startMinutes = $startHour * 60 + $startMinute;
        $endMinutes = $endHour * 60 + $endMinute;

        return $currentMinutes >= $startMinutes && $currentMinutes < $endMinutes;
    }

    public function getResponseTimeFormatted(): string
    {
        $hours = floor($this->response_time_minutes / 60);
        $minutes = $this->response_time_minutes % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }

    public function getResolutionTimeFormatted(): string
    {
        $hours = floor($this->resolution_time_minutes / 60);
        $minutes = $this->resolution_time_minutes % 60;

        if ($hours > 24) {
            $days = floor($hours / 24);
            $remainingHours = $hours % 24;

            return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days}d";
        }

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }
}
