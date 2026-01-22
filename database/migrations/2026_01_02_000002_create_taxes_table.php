<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: taxes
 * 
 * Tax definitions for sales and purchases.
 * 
 * Classification: BRANCH-OWNED (branch-specific tax configurations)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_tax_branch__brnch');
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('rate', 8, 4)->default(0);
            $table->string('type', 30)->default('percentage'); // percentage, fixed
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_tax_branch_code');
            $table->index('branch_id', 'idx_tax_branch_id');
            $table->index('is_active', 'idx_tax_is_active');
            $table->index('type', 'idx_tax_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
