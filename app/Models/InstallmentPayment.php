<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_plan_id',
        'installment_number',
        'amount_due',
        'amount_paid',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'payment_reference',
        'paid_by',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount_due - (float) ($this->amount_paid ?? 0));
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function recordPayment(float $amount, string $method, ?string $reference = null, ?int $userId = null): void
    {
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $amountPaid = decimal_float($this->amount_paid ?? 0);
        $newAmountPaid = min($amountPaid + $amount, decimal_float($this->amount_due));
        $newStatus = $newAmountPaid >= decimal_float($this->amount_due) ? 'paid' : 'partial';

        $this->update([
            'amount_paid' => $newAmountPaid,
            'paid_at' => now(),
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_by' => $userId,
            'status' => $newStatus,
        ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }
}
