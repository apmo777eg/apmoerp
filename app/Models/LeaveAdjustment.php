<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave Adjustment Model
 * 
 * Manual adjustments to leave balances (additions, deductions, corrections).
 */
class LeaveAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'adjustment_type',
        'amount',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Type constants
    public const TYPE_ADDITION = 'addition';
    public const TYPE_DEDUCTION = 'deduction';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_CARRY_FORWARD = 'carry_forward';
    public const TYPE_ENCASHMENT = 'encashment';

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Type helper methods

    public function isAddition(): bool
    {
        return $this->adjustment_type === self::TYPE_ADDITION;
    }

    public function isDeduction(): bool
    {
        return $this->adjustment_type === self::TYPE_DEDUCTION;
    }

    public function isCorrection(): bool
    {
        return $this->adjustment_type === self::TYPE_CORRECTION;
    }

    public function isCarryForward(): bool
    {
        return $this->adjustment_type === self::TYPE_CARRY_FORWARD;
    }

    public function isEncashment(): bool
    {
        return $this->adjustment_type === self::TYPE_ENCASHMENT;
    }

    public function isApproved(): bool
    {
        return !is_null($this->approved_at);
    }

    // Scopes

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('adjustment_type', $type);
    }

    public function scopeAdditions($query)
    {
        return $query->where('adjustment_type', self::TYPE_ADDITION);
    }

    public function scopeDeductions($query)
    {
        return $query->where('adjustment_type', self::TYPE_DEDUCTION);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }
}
