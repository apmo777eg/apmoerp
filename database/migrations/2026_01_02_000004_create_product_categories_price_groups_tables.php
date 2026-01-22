<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: product categories and price groups
 * 
 * Categories and price groups for product management.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Product categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prdcat_branch__brnch');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('slug', 191);
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete()
                ->name('fk_prdcat_parent__prdcat');
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prdcat_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prdcat_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'slug'], 'uq_prdcat_branch_slug');
            $table->index('branch_id', 'idx_prdcat_branch_id');
            $table->index('parent_id', 'idx_prdcat_parent_id');
            $table->index('is_active', 'idx_prdcat_is_active');
        });

        // Price groups
        Schema::create('price_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prcgrp_branch__brnch');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_prcgrp_branch_code');
            $table->index('branch_id', 'idx_prcgrp_branch_id');
            $table->index('is_active', 'idx_prcgrp_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_groups');
        Schema::dropIfExists('product_categories');
    }
};
