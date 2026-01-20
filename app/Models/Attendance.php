<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'shift_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'scheduled_in',
        'scheduled_out',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'worked_minutes',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'notes',
        'is_manual',
        'approved_by',
        'extra_attributes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'is_manual' => 'boolean',
    ];

    // Alias for backward compatibility
    public function getDateAttribute()
    {
        return $this->attendance_date;
    }

    // V45-CRIT-01 FIX: Add setter for backward compatibility with controllers using 'date'
    public function setDateAttribute($value): void
    {
        $this->attributes['attendance_date'] = $value;
    }

    public function getCheckInAttribute()
    {
        return $this->clock_in;
    }

    // V45-CRIT-01 FIX: Add setter for backward compatibility with controllers using 'check_in'
    public function setCheckInAttribute($value): void
    {
        $this->attributes['clock_in'] = $value;
    }

    public function getCheckOutAttribute()
    {
        return $this->clock_out;
    }

    // V45-CRIT-01 FIX: Add setter for backward compatibility with controllers using 'check_out'
    public function setCheckOutAttribute($value): void
    {
        $this->attributes['clock_out'] = $value;
    }

    public function getIsLateAttribute(): bool
    {
        return $this->late_minutes > 0;
    }

    public function getIsEarlyLeaveAttribute(): bool
    {
        return $this->early_leave_minutes > 0;
    }

    public function getTotalHoursAttribute(): ?string
    {
        if ($this->worked_minutes) {
            $hours = floor($this->worked_minutes / 60);
            $minutes = $this->worked_minutes % 60;

            return sprintf('%d:%02d', $hours, $minutes);
        }

        return null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
