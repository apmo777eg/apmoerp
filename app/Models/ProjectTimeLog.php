<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTimeLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'employee_id',
        'log_date',
        'date',
        'hours',
        'hourly_rate',
        'billable',
        'is_billable',
        'description',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'date' => 'date',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'billable' => 'boolean',
        'is_billable' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeBillable(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_billable', true)
                ->orWhere(function ($nested) {
                    $nested->whereNull('is_billable')
                        ->where('billable', true);
                });
        });
    }

    public function scopeNonBillable(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_billable', false)
                ->orWhere(function ($nested) {
                    $nested->whereNull('is_billable')
                        ->where('billable', false);
                });
        });
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForEmployee(Builder $query, $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    // Business Methods
    public function getCost(): float
    {
        return decimal_float($this->hours * $this->hourly_rate);
    }

    public function isBillable(): bool
    {
        return (bool) $this->is_billable;
    }

    // Helper methods for backwards compatibility
    public function getLogDateAttribute($value)
    {
        // If log_date has a value, return it; otherwise fall back to date
        if ($value !== null) {
            return $this->asDateTime($value);
        }
        $rawDate = $this->attributes['date'] ?? null;

        return $rawDate ? $this->asDateTime($rawDate) : null;
    }

    public function getUserIdAttribute($value)
    {
        // If user_id has a value, return it; otherwise fall back to employee_id
        if ($value !== null) {
            return $value;
        }

        return $this->attributes['employee_id'] ?? null;
    }

    public function getIsBillableAttribute($value)
    {
        // If is_billable has a value, return it; otherwise fall back to billable with default true
        if ($value !== null) {
            return (bool) $value;
        }
        $legacyBillable = $this->attributes['billable'] ?? null;

        return $legacyBillable !== null ? (bool) $legacyBillable : true;
    }

    public function setIsBillableAttribute($value): void
    {
        $boolValue = (bool) $value;
        $this->attributes['is_billable'] = $boolValue;
        $this->attributes['billable'] = $boolValue;
    }

    public function setBillableAttribute($value): void
    {
        $boolValue = (bool) $value;
        $this->attributes['billable'] = $boolValue;
        $this->attributes['is_billable'] = $boolValue;
    }
}
