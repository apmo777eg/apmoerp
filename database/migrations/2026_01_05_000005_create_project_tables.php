<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: project management tables
 * 
 * Projects, tasks, milestones, expenses, time logs.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Projects
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_proj_branch__brnch');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_proj_customer__cust');
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_proj_manager__usr');
            $table->string('status', 30)->default('draft'); // draft, active, on_hold, completed, cancelled
            $table->string('priority', 20)->default('normal');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->decimal('budget', 18, 4)->nullable();
            $table->decimal('actual_cost', 18, 4)->default(0);
            $table->string('billing_type', 30)->nullable(); // fixed, hourly, milestone
            $table->decimal('hourly_rate', 18, 4)->nullable();
            $table->string('category', 50)->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_proj_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_proj_branch_code');
            $table->index('branch_id', 'idx_proj_branch_id');
            $table->index('customer_id', 'idx_proj_customer_id');
            $table->index('manager_id', 'idx_proj_manager_id');
            $table->index('status', 'idx_proj_status');
            $table->index('start_date', 'idx_proj_start_date');
        });

        // Project milestones
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_projms_project__proj');
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed
            $table->unsignedSmallInteger('progress')->default(0);
            $table->text('deliverables')->nullable();
            $table->timestamp('achieved_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projms_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projms_updated_by__usr');
            $table->timestamps();

            $table->index('project_id', 'idx_projms_project_id');
            $table->index('status', 'idx_projms_status');
            $table->index('due_date', 'idx_projms_due_date');
        });

        // Project tasks
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_projtsk_project__proj');
            $table->foreignId('parent_task_id')
                ->nullable()
                ->constrained('project_tasks')
                ->nullOnDelete()
                ->name('fk_projtsk_parent__projtsk');
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projtsk_assigned__usr');
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed, blocked
            $table->string('priority', 20)->default('normal');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->unsignedSmallInteger('progress')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projtsk_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projtsk_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id', 'idx_projtsk_project_id');
            $table->index('assigned_to', 'idx_projtsk_assigned_to');
            $table->index('status', 'idx_projtsk_status');
            $table->index('due_date', 'idx_projtsk_due_date');
            $table->index('parent_task_id', 'idx_projtsk_parent_id');
        });

        // Task dependencies (pivot)
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('project_tasks')
                ->cascadeOnDelete()
                ->name('fk_tskdep_task__projtsk');
            $table->foreignId('depends_on_task_id')
                ->constrained('project_tasks')
                ->cascadeOnDelete()
                ->name('fk_tskdep_depends__projtsk');
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_task_id'], 'uq_tskdep_task_depends');
        });

        // Project expenses
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_projexp_project__proj');
            $table->foreignId('task_id')
                ->nullable()
                ->constrained('project_tasks')
                ->nullOnDelete()
                ->name('fk_projexp_task__projtsk');
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('USD');
            $table->foreignId('currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete()
                ->name('fk_projexp_currency__curr');
            $table->date('expense_date');
            $table->date('date')->nullable();
            $table->string('vendor', 191)->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projexp_user__usr');
            $table->boolean('billable')->default(true);
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, reimbursed
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projexp_approved_by__usr');
            $table->date('approved_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_reimbursable')->default(false);
            $table->foreignId('reimbursed_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projexp_reimbursed_to__usr');
            $table->timestamp('reimbursed_at')->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projexp_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projexp_updated_by__usr');
            $table->timestamps();

            $table->index('project_id', 'idx_projexp_project_id');
            $table->index('task_id', 'idx_projexp_task_id');
            $table->index('expense_date', 'idx_projexp_date');
            $table->index('status', 'idx_projexp_status');
        });

        // Project time logs
        Schema::create('project_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->name('fk_projtl_project__proj');
            $table->foreignId('task_id')
                ->nullable()
                ->constrained('project_tasks')
                ->nullOnDelete()
                ->name('fk_projtl_task__projtsk');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projtl_user__usr');
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('hr_employees')
                ->nullOnDelete()
                ->name('fk_projtl_employee__hremp');
            $table->date('log_date');
            $table->date('date')->nullable();
            $table->decimal('hours', 8, 2);
            $table->decimal('hourly_rate', 18, 2)->nullable();
            $table->boolean('billable')->default(true);
            $table->boolean('is_billable')->default(true);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projtl_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_projtl_updated_by__usr');
            $table->timestamps();

            $table->index('project_id', 'idx_projtl_project_id');
            $table->index('task_id', 'idx_projtl_task_id');
            $table->index('user_id', 'idx_projtl_user_id');
            $table->index('log_date', 'idx_projtl_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_time_logs');
        Schema::dropIfExists('project_expenses');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('projects');
    }
};
