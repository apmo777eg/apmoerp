<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Leave Encashment Model
 * 
 * Tracks conversion of leave days to cash payment.
 */
class LeaveEncashment extends Model
{
    use HasFactory;

    protected $fillable = [
        'encashment_number',
        'employee_id',
        'leave_type_id',
        'year',
        'days_encashed',
        'rate_per_day',
        'total_amount',
        'currency',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'days_encashed' => 'decimal:2',
        'rate_per_day' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($encashment) {
            if (empty($encashment->encashment_number)) {
                $encashment->encashment_number = static::generateEncashmentNumber();
            }
            if (empty($encashment->total_amount)) {
                $encashment->total_amount = $encashment->days_encashed * $encashment->rate_per_day;
            }
        });
    }

    /**
     * Generate unique encashment number
     * V43-HIGH-03 FIX: Wrap in transaction with lockForUpdate to prevent race conditions
     */
    public static function generateEncashmentNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'ENC';
            $date = now()->format('Ymd');
            
            // V43-HIGH-03 FIX: Use lockForUpdate to prevent duplicate codes
            $lastEncashment = static::whereDate('created_at', now()->toDateString())
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            
            $sequence = $lastEncashment ? ((int) substr($lastEncashment->encashment_number, -4)) + 1 : 1;
            
            return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
        });
    }

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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Status helper methods

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeProcessed(): bool
    {
        return $this->isApproved();
    }

    public function canBePaid(): bool
    {
        return $this->isProcessed();
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }
}
