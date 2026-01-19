<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    use HasBranch;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'branch_id',
        'total_amount',
        'down_payment',
        'remaining_amount',
        'num_installments',
        'installment_amount',
        'interest_rate',
        'status',
        'start_date',
        'end_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaidAmountAttribute(): float
    {
        return decimal_float($this->payments()->sum('amount_paid'));
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, decimal_float($this->total_amount) - decimal_float($this->down_payment) - $this->paid_amount);
    }

    public function getNextPaymentAttribute(): ?InstallmentPayment
    {
        return $this->payments()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('installment_number')
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeDefaulted(Builder $query): Builder
    {
        return $query->where('status', 'defaulted');
    }
}
