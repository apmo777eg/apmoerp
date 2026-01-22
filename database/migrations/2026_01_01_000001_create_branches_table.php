<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: branches
 * 
 * Core branch table for multi-branch ERP support.
 * All branch-owned tables reference this table.
 * 
 * Classification: GLOBAL (reference table for branch isolation)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('code', 50)->unique('uq_brnch_code');
            $table->boolean('is_active')->default(true);
            $table->string('address', 500)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->string('currency', 10)->default('USD');
            $table->boolean('is_main')->default(false);
            $table->foreignId('parent_id')->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_brnch_parent__brnch');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active', 'idx_brnch_is_active');
            $table->index('is_main', 'idx_brnch_is_main');
            $table->index('parent_id', 'idx_brnch_parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
