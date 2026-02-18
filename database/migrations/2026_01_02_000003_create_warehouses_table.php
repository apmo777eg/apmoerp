<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: warehouses
 * 
 * Warehouse definitions for inventory management.
 * 
 * Classification: BRANCH-OWNED (warehouses belong to branches)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_wh_branch__brnch');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('code', 50);
            $table->string('type', 30)->default('standard'); // standard, transit, virtual
            // Optional categorization used by UI (e.g. "Aisle", "Zone")
            $table->string('section', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 50)->nullable();
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wh_manager__usr');
            $table->boolean('is_active')->default(true);
            // UI/legacy-friendly status string (kept in sync with is_active at app layer)
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_negative_stock')->default(false);
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->json('extra_attributes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wh_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wh_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_wh_branch_code');
            $table->index('branch_id', 'idx_wh_branch_id');
            $table->index('is_active', 'idx_wh_is_active');
            $table->index('status', 'idx_wh_status');
            $table->index('is_default', 'idx_wh_is_default');
            $table->index('type', 'idx_wh_type');
            $table->index('manager_id', 'idx_wh_manager_id');
            $table->index('created_by', 'idx_wh_created_by');
            $table->index('updated_by', 'idx_wh_updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
