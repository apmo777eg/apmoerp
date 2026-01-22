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
            $table->string('address', 500)->nullable();
            $table->string('phone', 50)->nullable();
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wh_manager__usr');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_negative_stock')->default(false);
            $table->json('settings')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_wh_branch_code');
            $table->index('branch_id', 'idx_wh_branch_id');
            $table->index('is_active', 'idx_wh_is_active');
            $table->index('is_default', 'idx_wh_is_default');
            $table->index('type', 'idx_wh_type');
            $table->index('manager_id', 'idx_wh_manager_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
