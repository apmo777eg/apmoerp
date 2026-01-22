<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeShift extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    /**
     * Fillable fields aligned with migration:
     * employee_shifts table in 2026_01_04_000006_create_hr_payroll_tables.php
     */
    protected $fillable = [
        'employee_id',
        'branch_id',
        'shift_id',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeCurrent(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $today = now()->toDateString();

        return $query->where('is_current', true)
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_current) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->start_date > $today) {
            return false;
        }

        if ($this->end_date && $this->end_date < $today) {
            return false;
        }

        return true;
    }

    // Backward compatibility accessor
    public function getIsActiveAttribute(): bool
    {
        return $this->is_current;
    }
}
