<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    /**
     * Fillable fields aligned with migration:
     * shifts table in 2026_01_04_000006_create_hr_payroll_tables.php
     */
    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'name_ar',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'break_duration_minutes',
        'late_grace_minutes',
        'early_leave_grace_minutes',
        'overtime_rate',
        'working_days',
        'is_night_shift',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_night_shift' => 'boolean',
        'break_duration_minutes' => 'integer',
        'late_grace_minutes' => 'integer',
        'early_leave_grace_minutes' => 'integer',
        'overtime_rate' => 'decimal:2',
        'working_days' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function employees()
    {
        return $this->belongsToMany(HREmployee::class, 'employee_shifts', 'shift_id', 'employee_id')
            ->withPivot(['start_date', 'end_date', 'is_current'])
            ->withTimestamps();
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function getShiftDurationAttribute(): float
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        if ($end->lt($start) || $this->is_night_shift) {
            // Shift crosses midnight
            $end->addDay();
        }

        // Subtract break duration
        $durationMinutes = $start->diffInMinutes($end) - ($this->break_duration_minutes ?? 0);

        return $durationMinutes / 60;
    }

    public function isWorkingDay(string $day): bool
    {
        if (! $this->working_days) {
            return true;
        }

        return in_array(strtolower($day), array_map('strtolower', $this->working_days), true);
    }

    /**
     * Default late grace minutes if not specified
     */
    public const DEFAULT_LATE_GRACE_MINUTES = 15;

    /**
     * Get grace period minutes - returns late_grace_minutes for backward compatibility
     */
    public function getGracePeriodMinutesAttribute(): int
    {
        return $this->late_grace_minutes ?? self::DEFAULT_LATE_GRACE_MINUTES;
    }
}
