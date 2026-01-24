<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'reference_number',
        'year',
        'month',
        'status',
        'salary',
        'housing_allowance',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        'overtime_amount',
        'bonus',
        'commission',
        'gross_salary',
        'tax_deduction',
        'insurance_deduction',
        'loan_deduction',
        'advance_deduction',
        'absence_deduction',
        'late_deduction',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'working_days',
        'present_days',
        'absent_days',
        'late_days',
        'overtime_hours',
        'leave_days',
        'payment_date',
        'payment_method',
        'bank_reference',
        'notes',
        'breakdown',
        'extra_attributes',
    ];

    protected $casts = [
        'salary' => 'decimal:4',
        'housing_allowance' => 'decimal:4',
        'transport_allowance' => 'decimal:4',
        'meal_allowance' => 'decimal:4',
        'other_allowances' => 'decimal:4',
        'overtime_amount' => 'decimal:4',
        'bonus' => 'decimal:4',
        'commission' => 'decimal:4',
        'gross_salary' => 'decimal:4',
        'tax_deduction' => 'decimal:4',
        'insurance_deduction' => 'decimal:4',
        'loan_deduction' => 'decimal:4',
        'advance_deduction' => 'decimal:4',
        'absence_deduction' => 'decimal:4',
        'late_deduction' => 'decimal:4',
        'other_deductions' => 'decimal:4',
        'total_deductions' => 'decimal:4',
        'net_salary' => 'decimal:4',
        'payment_date' => 'date',
        'breakdown' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the pay period start date
     */
    public function getPayPeriodStartAttribute(): ?\Carbon\Carbon
    {
        if ($this->year && $this->month) {
            return \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth();
        }

        return null;
    }

    /**
     * Get the pay period end date
     */
    public function getPayPeriodEndAttribute(): ?\Carbon\Carbon
    {
        if ($this->year && $this->month) {
            return \Carbon\Carbon::create($this->year, $this->month, 1)->endOfMonth();
        }

        return null;
    }

    public function scopePaid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('status', ['draft', 'calculated', 'approved']);
    }
}
