<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: HR and payroll tables
 * 
 * Employees, attendance, shifts, leave, payroll.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_shft_branch__brnch');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_duration_minutes')->default(0);
            $table->unsignedSmallInteger('grace_period_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_threshold_minutes')->default(0);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5);
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('working_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'name'], 'uq_shft_branch_name');
            $table->index('branch_id', 'idx_shft_branch_id');
            $table->index('is_active', 'idx_shft_is_active');
        });

        // HR Employees
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_hremp_branch__brnch');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_hremp_user__usr');
            $table->string('employee_code', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('first_name_ar', 100)->nullable();
            $table->string('last_name_ar', 100)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->date('passport_expiry')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('emergency_contact_name', 191)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('hr_employees')
                ->nullOnDelete()
                ->name('fk_hremp_manager__hremp');
            $table->date('hire_date');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('employment_type', 30)->default('full_time'); // full_time, part_time, contract, intern
            $table->string('status', 30)->default('active'); // active, probation, on_leave, terminated, resigned
            $table->boolean('is_active')->default(true);
            $table->decimal('basic_salary', 18, 4)->default(0);
            $table->string('salary_currency', 10)->default('USD');
            $table->string('payment_method', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->decimal('housing_allowance', 18, 4)->default(0);
            $table->decimal('transport_allowance', 18, 4)->default(0);
            $table->decimal('meal_allowance', 18, 4)->default(0);
            $table->decimal('other_allowances', 18, 4)->default(0);
            $table->decimal('annual_leave_balance', 8, 2)->default(0);
            $table->decimal('sick_leave_balance', 8, 2)->default(0);
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->json('work_days')->nullable();
            $table->string('profile_photo', 500)->nullable();
            $table->json('documents')->nullable();
            $table->json('skills')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'employee_code'], 'uq_hremp_branch_code');
            $table->index('branch_id', 'idx_hremp_branch_id');
            $table->index('user_id', 'idx_hremp_user_id');
            $table->index('department', 'idx_hremp_department');
            $table->index('status', 'idx_hremp_status');
            $table->index('is_active', 'idx_hremp_is_active');
            $table->index('manager_id', 'idx_hremp_manager_id');
            $table->index(['branch_id', 'id'], 'idx_hremp_branch_id_id');
        });

        // Employee shifts (pivot)
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_empshft_employee__hremp');
            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnDelete()
                ->name('fk_empshft_shift__shft');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'shift_id', 'start_date'], 'uq_empshft_emp_shft_start');
            $table->index('employee_id', 'idx_empshft_employee_id');
            $table->index('shift_id', 'idx_empshft_shift_id');
            $table->index('is_current', 'idx_empshft_is_current');
        });

        // Branch employee (pivot)
        Schema::create('branch_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_brnemp_branch__brnch');
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_brnemp_employee__hremp');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['branch_id', 'employee_id'], 'uq_brnemp_branch_employee');
        });

        // Attendance
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_att_employee__hremp');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_att_branch__brnch');
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete()
                ->name('fk_att_shift__shft');
            $table->date('attendance_date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->time('scheduled_in')->nullable();
            $table->time('scheduled_out')->nullable();
            $table->string('status', 30)->default('present'); // present, absent, late, half_day, holiday, leave
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('clock_out_ip', 45)->nullable();
            $table->decimal('clock_in_latitude', 10, 7)->nullable();
            $table->decimal('clock_in_longitude', 10, 7)->nullable();
            $table->decimal('clock_out_latitude', 10, 7)->nullable();
            $table->decimal('clock_out_longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_att_approved_by__usr');
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'attendance_date'], 'uq_att_employee_date');
            $table->index('branch_id', 'idx_att_branch_id');
            $table->index('attendance_date', 'idx_att_date');
            $table->index('status', 'idx_att_status');
        });

        // Leave types
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_lvtyp_branch__brnch');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('default_days', 5, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_document')->default(false);
            $table->boolean('can_carry_forward')->default(false);
            $table->unsignedSmallInteger('max_carry_forward_days')->default(0);
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->unsignedSmallInteger('max_consecutive_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('color', 20)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'name'], 'uq_lvtyp_branch_name');
            $table->index('branch_id', 'idx_lvtyp_branch_id');
            $table->index('is_active', 'idx_lvtyp_is_active');
        });

        // Leave balances
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_lvbal_employee__hremp');
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete()
                ->name('fk_lvbal_type__lvtyp');
            $table->unsignedSmallInteger('year');
            $table->decimal('entitled_days', 5, 2)->default(0);
            $table->decimal('carried_forward', 5, 2)->default(0);
            $table->decimal('accrued_days', 5, 2)->default(0);
            $table->decimal('used_days', 5, 2)->default(0);
            $table->decimal('pending_days', 5, 2)->default(0);
            $table->decimal('encashed_days', 5, 2)->default(0);
            $table->decimal('expired_days', 5, 2)->default(0);
            $table->decimal('remaining_days', 5, 2)->default(0);
            $table->date('last_accrual_date')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'uq_lvbal_emp_type_year');
            $table->index('employee_id', 'idx_lvbal_employee_id');
            $table->index('year', 'idx_lvbal_year');
        });

        // Leave accrual rules
        Schema::create('leave_accrual_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete()
                ->name('fk_lvacr_type__lvtyp');
            $table->string('accrual_frequency', 30); // monthly, quarterly, annually
            $table->decimal('accrual_amount', 5, 2);
            $table->unsignedSmallInteger('cap_amount')->nullable();
            $table->boolean('prorate_for_new_hires')->default(true);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index('leave_type_id', 'idx_lvacr_type_id');
            $table->index('is_active', 'idx_lvacr_is_active');
        });

        // Leave requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_lvreq_employee__hremp');
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete()
                ->name('fk_lvreq_type__lvtyp');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 5, 2);
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, cancelled
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('attachment', 500)->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_lvreq_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id', 'idx_lvreq_employee_id');
            $table->index('leave_type_id', 'idx_lvreq_type_id');
            $table->index('status', 'idx_lvreq_status');
            $table->index(['start_date', 'end_date'], 'idx_lvreq_dates');
        });

        // Leave request approvals
        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')
                ->constrained('leave_requests')
                ->cascadeOnDelete()
                ->name('fk_lvreqa_request__lvreq');
            $table->foreignId('approver_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_lvreqa_approver__usr');
            $table->unsignedSmallInteger('level')->default(1);
            $table->string('status', 30)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('leave_request_id', 'idx_lvreqa_request_id');
        });

        // Leave adjustments
        Schema::create('leave_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_lvadj_employee__hremp');
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete()
                ->name('fk_lvadj_type__lvtyp');
            $table->unsignedSmallInteger('year');
            $table->decimal('days', 5, 2);
            $table->string('adjustment_type', 30); // add, deduct
            $table->text('reason')->nullable();
            $table->foreignId('adjusted_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_lvadj_adjusted_by__usr');
            $table->timestamp('adjusted_at');
            $table->timestamps();

            $table->index('employee_id', 'idx_lvadj_employee_id');
            $table->index('year', 'idx_lvadj_year');
        });

        // Leave encashments
        Schema::create('leave_encashments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_lvenc_employee__hremp');
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete()
                ->name('fk_lvenc_type__lvtyp');
            $table->unsignedSmallInteger('year');
            $table->decimal('days_encashed', 5, 2);
            $table->decimal('daily_rate', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->string('status', 30)->default('pending');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_lvenc_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_lvenc_paid_by__usr');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('employee_id', 'idx_lvenc_employee_id');
            $table->index('status', 'idx_lvenc_status');
        });

        // Leave holidays
        Schema::create('leave_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_lvhol_branch__brnch');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->date('holiday_date');
            $table->unsignedSmallInteger('year');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'holiday_date'], 'uq_lvhol_branch_date');
            $table->index('year', 'idx_lvhol_year');
            $table->index('is_active', 'idx_lvhol_is_active');
        });

        // Payrolls
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_payr_branch__brnch');
            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->cascadeOnDelete()
                ->name('fk_payr_employee__hremp');
            $table->string('reference_number', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 30)->default('draft'); // draft, calculated, approved, paid
            // Earnings
            $table->decimal('basic_salary', 18, 4)->default(0);
            $table->decimal('housing_allowance', 18, 4)->default(0);
            $table->decimal('transport_allowance', 18, 4)->default(0);
            $table->decimal('meal_allowance', 18, 4)->default(0);
            $table->decimal('other_allowances', 18, 4)->default(0);
            $table->decimal('overtime_amount', 18, 4)->default(0);
            $table->decimal('bonus', 18, 4)->default(0);
            $table->decimal('commission', 18, 4)->default(0);
            $table->decimal('gross_salary', 18, 4)->default(0);
            // Deductions
            $table->decimal('tax_deduction', 18, 4)->default(0);
            $table->decimal('insurance_deduction', 18, 4)->default(0);
            $table->decimal('loan_deduction', 18, 4)->default(0);
            $table->decimal('advance_deduction', 18, 4)->default(0);
            $table->decimal('absence_deduction', 18, 4)->default(0);
            $table->decimal('late_deduction', 18, 4)->default(0);
            $table->decimal('other_deductions', 18, 4)->default(0);
            $table->decimal('total_deductions', 18, 4)->default(0);
            $table->decimal('net_salary', 18, 4)->default(0);
            // Attendance summary
            $table->unsignedSmallInteger('working_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedSmallInteger('late_days')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->unsignedSmallInteger('leave_days')->default(0);
            // Payment
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('bank_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('breakdown')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_payr_branch_ref');
            $table->unique(['employee_id', 'year', 'month'], 'uq_payr_emp_year_month');
            $table->index('branch_id', 'idx_payr_branch_id');
            $table->index('status', 'idx_payr_status');
            $table->index(['year', 'month'], 'idx_payr_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('leave_holidays');
        Schema::dropIfExists('leave_encashments');
        Schema::dropIfExists('leave_adjustments');
        Schema::dropIfExists('leave_request_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_accrual_rules');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('branch_employee');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('shifts');
    }
};
