<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: branch pivot tables
 * 
 * Pivot tables for branch relationships.
 * 
 * Classification: BRANCH-OWNED (pivots for branch associations)
 */
return new class extends Migration
{
    public function up(): void
    {
        // branch_user pivot
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_brusr_branch__brnch');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_brusr_user__usr');
            $table->timestamps();

            $table->unique(['branch_id', 'user_id'], 'uq_brusr_branch_user');
            $table->index('user_id', 'idx_brusr_user_id');
        });

        // branch_modules pivot
        Schema::create('branch_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_brmod_branch__brnch');
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_brmod_module__mod');
            $table->string('module_key', 50)->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'module_id'], 'uq_brmod_branch_module');
            $table->index('module_id', 'idx_brmod_module_id');
            $table->index('enabled', 'idx_brmod_enabled');
            $table->index('module_key', 'idx_brmod_module_key');
        });

        // branch_admins pivot
        Schema::create('branch_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_bradm_branch__brnch');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_bradm_user__usr');
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_manage_roles')->default(false);
            $table->boolean('can_view_reports')->default(true);
            $table->boolean('can_export_data')->default(true);
            $table->boolean('can_manage_settings')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'user_id'], 'uq_bradm_branch_user');
            $table->index('user_id', 'idx_bradm_user_id');
            $table->index('is_active', 'idx_bradm_is_active');
            $table->index('is_primary', 'idx_bradm_is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_admins');
        Schema::dropIfExists('branch_modules');
        Schema::dropIfExists('branch_user');
    }
};
