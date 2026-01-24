<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HREmployee extends BaseModel
{
    protected $table = 'hr_employees';

    protected ?string $moduleKey = 'hr';

    /**
     * Fillable fields aligned with migration:
     * 2026_01_04_000006_create_hr_payroll_tables.php
     */
    protected $fillable = [
        'branch_id',
        'user_id',
        'code',
        'name',
        'name_ar',
        // Personal info
        'email',
        'phone',
        'mobile',
        'birth_date',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'passport_number',
        'passport_expiry',
        // Address
        'address',
        'city',
        'country',
        // Emergency contact
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        // Employment info
        'position',
        'department',
        'manager_id',
        'hire_date',
        'contract_start_date',
        'contract_end_date',
        'termination_date',
        'employment_type',
        'status',
        'is_active',
        // Salary info
        'salary',
        'salary_currency',
        'payment_method',
        'bank_name',
        'bank_account',
        'bank_iban',
        // Allowances
        'housing_allowance',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        // Leave balances
        'annual_leave_balance',
        'sick_leave_balance',
        // Working hours
        'work_start_time',
        'work_end_time',
        'work_days',
        // Additional
        'profile_photo',
        'documents',
        'skills',
        'notes',
        'custom_fields',
        'extra_attributes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'salary' => 'decimal:4',
        'housing_allowance' => 'decimal:4',
        'transport_allowance' => 'decimal:4',
        'meal_allowance' => 'decimal:4',
        'other_allowances' => 'decimal:4',
        'birth_date' => 'date',
        'passport_expiry' => 'date',
        'hire_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'termination_date' => 'date',
        'work_days' => 'array',
        'documents' => 'array',
        'skills' => 'array',
        'custom_fields' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Many-to-many relationship with branches via pivot table
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_employee', 'employee_id', 'branch_id')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class, 'employee_id');
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'employee_shifts', 'employee_id', 'shift_id')
            ->withPivot(['start_date', 'end_date', 'is_current'])
            ->withTimestamps();
    }

    public function currentShift()
    {
        return $this->employeeShifts()
            ->where('is_current', true)
            ->where('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->with('shift')
            ->first();
    }

    // Backward compatibility accessors
    public function getDateOfBirthAttribute()
    {
        return $this->birth_date;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getFullNameArAttribute(): string
    {
        return $this->name_ar ?? '';
    }

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            // Auto-generate code if empty
            if (empty($employee->code)) {
                $employee->code = 'EMP-'.Str::upper(Str::random(8));
            }
            
            // Sync is_active with status on creation
            if (isset($employee->status)) {
                $employee->is_active = ($employee->status === 'active');
            }
        });

        static::updating(function (self $employee): void {
            // Sync is_active with status on update
            if ($employee->isDirty('status')) {
                $employee->is_active = ($employee->status === 'active');
            }
            
            // Sync status with is_active if is_active is changed
            if ($employee->isDirty('is_active') && !$employee->isDirty('status')) {
                $employee->status = $employee->is_active ? 'active' : 'inactive';
            }
        });
    }
}
