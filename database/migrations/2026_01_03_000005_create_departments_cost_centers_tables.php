<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Departments & Cost Centers (DB-first)
 *
 * These tables are referenced by purchase requisitions and other modules.
 * They must exist before purchases migrations to allow proper FK constraints.
 *
 * Classification: BRANCH-OWNED (organizational structure)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Departments (Branch-owned)
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_dept_branch__brnch');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete()
                ->name('fk_dept_parent__dept');
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_dept_manager__usr');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_dept_branch_code');
            $table->index('branch_id', 'idx_dept_branch_id');
            $table->index('is_active', 'idx_dept_is_active');
            $table->index('parent_id', 'idx_dept_parent_id');
        });

        // Cost Centers (Branch-owned)
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_cc_branch__brnch');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('cost_centers')
                ->nullOnDelete()
                ->name('fk_cc_parent__cc');
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete()
                ->name('fk_cc_dept__dept');
            $table->decimal('budget', 18, 4)->default(0);
            $table->string('budget_period', 20)->default('yearly'); // monthly, quarterly, yearly
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_cc_branch_code');
            $table->index('branch_id', 'idx_cc_branch_id');
            $table->index('is_active', 'idx_cc_is_active');
            $table->index('department_id', 'idx_cc_dept_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('departments');
    }
};
