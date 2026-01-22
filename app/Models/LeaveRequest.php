<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * LeaveRequest Model - Basic leave requests
 * 
 * For basic leave request tracking. For advanced leave management with
 * balances, types, accruals, and detailed tracking, use Leave* models
 * (LeaveType, LeaveBalance, LeaveRequestApproval, etc.)
 * 
 * @property int $id
 * @property int $employee_id
 * @property string $leave_type
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property float $days_count
 * @property string $status
 * @property string|null $reason
 * @property string|null $rejection_reason
 * @property string|null $attachment
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 */
class LeaveRequest extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'hr';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'status',
        'reason',
        'rejection_reason',
        'attachment',
        'approved_by',
        'approved_at',
        'extra_attributes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_count' => 'decimal:2',
        'approved_at' => 'datetime',
        'extra_attributes' => 'array',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    // Leave type constants (basic)
    public const TYPE_ANNUAL = 'annual';
    public const TYPE_SICK = 'sick';
    public const TYPE_CASUAL = 'casual';
    public const TYPE_EMERGENCY = 'emergency';
    public const TYPE_MATERNITY = 'maternity';
    public const TYPE_PATERNITY = 'paternity';

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('leave_type', $type);
    }

    public function scopeInDateRange(\Illuminate\Database\Eloquent\Builder $query, $startDate, $endDate): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * Helper Methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Approve leave request
     */
    public function approve(?int $userId = null, ?string $note = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $data = [
            'status' => self::STATUS_APPROVED,
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            'approved_by' => $userId ?? actual_user_id(),
            'approved_at' => now(),
        ];

        if ($note) {
            $attributes = $this->extra_attributes ?? [];
            $attributes['approval_note'] = $note;
            $data['extra_attributes'] = $attributes;
        }

        return $this->update($data);
    }

    /**
     * Reject leave request
     */
    public function reject(?int $userId = null, ?string $reason = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            'approved_by' => $userId ?? actual_user_id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancel leave request
     */
    public function cancel(?int $userId = null, ?string $reason = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $data = ['status' => self::STATUS_CANCELLED];

        if ($reason) {
            $attributes = $this->extra_attributes ?? [];
            $attributes['cancellation_reason'] = $reason;
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $attributes['cancelled_by'] = $userId ?? actual_user_id();
            $attributes['cancelled_at'] = now()->toDateTimeString();
            $data['extra_attributes'] = $attributes;
        }

        return $this->update($data);
    }

    /**
     * Calculate actual days (excluding weekends/holidays)
     */
    public function calculateActualDays(array $holidays = []): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $days = 0;
        $current = $this->start_date->copy();

        while ($current->lte($this->end_date)) {
            // Skip weekends (Friday & Saturday for many regions, adjust as needed)
            if (!in_array($current->dayOfWeek, [5, 6])) {
                // Skip holidays
                $isHoliday = false;
                foreach ($holidays as $holiday) {
                    if ($current->isSameDay($holiday)) {
                        $isHoliday = true;
                        break;
                    }
                }
                if (!$isHoliday) {
                    $days++;
                }
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Check if overlaps with another leave request
     */
    public function overlapsWith(LeaveRequest $other): bool
    {
        return $this->start_date->lte($other->end_date) 
            && $this->end_date->gte($other->start_date);
    }

    // Backward compatibility accessors
    public function getFromDateAttribute()
    {
        return $this->start_date;
    }

    public function getToDateAttribute()
    {
        return $this->end_date;
    }

    public function getTypeAttribute()
    {
        return $this->leave_type;
    }
}
