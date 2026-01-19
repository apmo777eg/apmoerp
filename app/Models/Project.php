<?php

namespace App\Models;

use App\Traits\HasBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasBranch, HasFactory, SoftDeletes;

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000011_create_projects_documents_support_tables.php
     */
    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'name_ar',
        'description',
        'customer_id',
        'manager_id',
        'status',
        'priority',
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'progress_percent',
        'budget',
        'actual_cost',
        'billing_type',
        'hourly_rate',
        'category',
        'tags',
        'notes',
        'custom_fields',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'decimal:4',
        'actual_cost' => 'decimal:4',
        'hourly_rate' => 'decimal:4',
        'progress_percent' => 'decimal:2',
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->code)) {
                // V8-HIGH-N02 FIX: Use lockForUpdate to prevent race condition
                // Get the last code with a lock to prevent duplicates
                $lastProject = static::whereDate('created_at', Carbon::today())
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $seq = 1;
                if ($lastProject && preg_match('/PRJ-\d{8}-(\d{6})$/', $lastProject->code, $matches)) {
                    $seq = ((int) $matches[1]) + 1;
                }

                $project->code = 'PRJ-'.date('Ymd').'-'.str_pad(
                    (string) $seq,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Backward compatibility - returns the customer relationship
    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Backward compatibility accessors
    public function getProjectManagerIdAttribute()
    {
        return $this->manager_id;
    }

    public function getBudgetAmountAttribute()
    {
        return $this->budget;
    }

    public function getProgressAttribute()
    {
        return (int) $this->progress_percent;
    }

    public function getClientIdAttribute()
    {
        return $this->customer_id;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverBudget(Builder $query): Builder
    {
        return $query->whereRaw('actual_cost > budget');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('end_date', '<', Carbon::now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    // Business Methods
    public function getCalculatedProgress(): int
    {
        $totalTasks = $this->tasks()->count();

        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();

        return (int) round(($completedTasks / $totalTasks) * 100);
    }

    public function getTotalBudget(): float
    {
        return decimal_float($this->budget);
    }

    public function getTotalActualCost(): float
    {
        $timeLogsCost = $this->timeLogs()
            ->selectRaw('SUM(hours * hourly_rate) as total')
            ->value('total') ?? 0;

        $expensesCost = $this->expenses()
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        return decimal_float($timeLogsCost + $expensesCost);
    }

    public function getBudgetVariance(): float
    {
        return $this->getTotalBudget() - $this->getTotalActualCost();
    }

    public function isOverBudget(): bool
    {
        return $this->getTotalActualCost() > $this->getTotalBudget();
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->end_date && $this->end_date->isPast();
    }

    public function getTeamMembers(): array
    {
        // Get unique users from tasks, time logs
        $taskAssignees = $this->tasks()->pluck('assigned_to')->unique()->filter();
        $timeLoggers = $this->timeLogs()->pluck('employee_id')->unique()->filter();

        return $taskAssignees->merge($timeLoggers)->unique()->values()->toArray();
    }

    public function getRemainingDays(): ?int
    {
        if (! $this->end_date || in_array($this->status, ['completed', 'cancelled'])) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->end_date, false);
    }
}
