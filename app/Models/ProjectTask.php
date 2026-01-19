<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'parent_task_id',
        'title',
        'description',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'progress',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'progress' => 'integer',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class, 'task_id');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectTask::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectTask::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    // Business Methods
    public function getProgress(): int
    {
        return (int) $this->progress;
    }

    public function canBeStarted(): bool
    {
        // Check if all dependencies are completed
        $incompleteDependencies = $this->dependencies()
            ->where('status', '!=', 'completed')
            ->count();

        return $incompleteDependencies === 0;
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast();
    }

    public function getTimeSpent(): float
    {
        return decimal_float($this->timeLogs()->sum('hours'));
    }

    public function getTimeRemaining(): float
    {
        $spent = $this->getTimeSpent();
        $estimated = decimal_float($this->estimated_hours);

        return max(0, $estimated - $spent);
    }

    public function isBlocking(): bool
    {
        return $this->dependents()
            ->where('status', '!=', 'completed')
            ->exists();
    }

    public function getDependentTasks()
    {
        return $this->dependents()->get();
    }
}
