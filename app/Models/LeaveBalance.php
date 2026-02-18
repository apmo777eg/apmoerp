<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave Balance Model
 * 
 * Tracks employee leave balances by type and year with accrual tracking.
 */
class LeaveBalance extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'annual_quota',
        'accrued',
        'used',
        'pending',
        'adjusted',
        'encashed',
        'available_balance',
        'carried_forward',
        'expires_at',
        'last_accrual_date',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'opening_balance' => 'decimal:2',
        'annual_quota' => 'decimal:2',
        'accrued' => 'decimal:2',
        'used' => 'decimal:2',
        'pending' => 'decimal:2',
        'adjusted' => 'decimal:2',
        'encashed' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'expires_at' => 'date',
        'last_accrual_date' => 'date',
    ];

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Helper methods

    /**
     * Calculate and update available balance
     */
    public function calculateAvailable(): float
    {
        return max(0, (
            $this->opening_balance
            + $this->annual_quota
            + $this->accrued
            + $this->adjusted
            + $this->carried_forward
        ) - (
            $this->used
            + $this->pending
            + $this->encashed
        ));
    }

    /**
     * Update available balance
     */
    public function updateAvailable(): self
    {
        $this->available_balance = $this->calculateAvailable();
        $this->save();
        return $this;
    }

    /**
     * Check if sufficient balance exists
     */
    public function hasSufficientBalance(float $requestedDays): bool
    {
        return $this->available_balance >= $requestedDays;
    }

    /**
     * Check if carry forward has expired
     */
    public function isCarryForwardExpired(): bool
    {
        return $this->carried_forward > 0
            && !is_null($this->expires_at)
            && now()->isAfter($this->expires_at);
    }

    /**
     * Get total balance (available + pending)
     */
    public function getTotalBalance(): float
    {
        return $this->available_balance + $this->pending;
    }

    // Scopes

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByLeaveType($query, int $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }

    public function scopeWithAvailableBalance($query)
    {
        return $query->where('available_balance', '>', 0);
    }

    public function scopeExpiredCarryForward($query)
    {
        return $query->where('carried_forward', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }
}
