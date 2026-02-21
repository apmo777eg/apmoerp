<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
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
        'period',
        'basic',
        'allowances',
        'deductions',
        'net',
        'paid_at',
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
        'extra_attributes' => 'array',
    ];


    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            // If the legacy 'period' attribute was set, ensure year/month are populated.
            // This is safe for fresh DBs because payrolls table is canonical on (year, month).
            if ((! $model->year || ! $model->month) && ! empty($model->attributes['period'] ?? null)) {
                $model->setPeriodAttribute($model->attributes['period']);
            }

            // Ensure a reference_number exists (required by schema).
            if (empty($model->reference_number)) {
                $year = (int) ($model->year ?: now()->year);
                $month = (int) ($model->month ?: now()->month);
                $emp = $model->employee_id ?: null;

                $suffix = $emp ? (string) $emp : Str::upper(Str::random(6));
                $model->reference_number = sprintf('PAY-%04d%02d-%s', $year, $month, $suffix);
            }
        });
    }

    /**
     * Backward-compat: legacy "period" (Y-m) used across controllers/views.
     */
    public function getPeriodAttribute(): ?string
    {
        if ($this->year && $this->month) {
            return sprintf('%04d-%02d', (int) $this->year, (int) $this->month);
        }

        return null;
    }

    public function setPeriodAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            $dt = Carbon::createFromFormat('Y-m', (string) $value);
            $this->attributes['year'] = (int) $dt->year;
            $this->attributes['month'] = (int) $dt->month;
        } catch (\Throwable) {
            // Ignore invalid legacy input; validation should catch it at the edge.
        }
    }

    /**
     * Backward-compat: legacy payroll fields used in UI (basic/allowances/deductions/net/paid_at).
     *
     * Canonical columns:
     *  - salary (basic)
     *  - allowance columns (housing/transport/meal/other/overtime/bonus/commission)
     *  - total_deductions (deductions)
     *  - net_salary (net)
     *  - payment_date (paid_at)
     */
    public function getBasicAttribute(): float
    {
        return decimal_float($this->salary ?? 0);
    }

    public function setBasicAttribute($value): void
    {
        $this->attributes['salary'] = $value;
    }

    public function getAllowancesAttribute(): float
    {
        $parts = [
            $this->housing_allowance ?? 0,
            $this->transport_allowance ?? 0,
            $this->meal_allowance ?? 0,
            $this->other_allowances ?? 0,
            $this->overtime_amount ?? 0,
            $this->bonus ?? 0,
            $this->commission ?? 0,
        ];

        $sum = 0.0;
        foreach ($parts as $p) {
            $sum += decimal_float($p);
        }

        return $sum;
    }

    public function setAllowancesAttribute($value): void
    {
        // Legacy code may set a single total; map it to other_allowances (generic bucket).
        $this->attributes['other_allowances'] = $value;
    }

    public function getDeductionsAttribute(): float
    {
        return decimal_float($this->total_deductions ?? 0);
    }

    public function setDeductionsAttribute($value): void
    {
        // Legacy code may set a single total; map it to total_deductions + other_deductions.
        $this->attributes['total_deductions'] = $value;
        $this->attributes['other_deductions'] = $value;
    }

    public function getNetAttribute(): float
    {
        return decimal_float($this->net_salary ?? 0);
    }

    public function setNetAttribute($value): void
    {
        $this->attributes['net_salary'] = $value;
    }

    public function getPaidAtAttribute(): ?Carbon
    {
        return $this->payment_date ? Carbon::parse($this->payment_date) : null;
    }

    public function setPaidAtAttribute($value): void
    {
        $this->attributes['payment_date'] = $value;
    }


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
