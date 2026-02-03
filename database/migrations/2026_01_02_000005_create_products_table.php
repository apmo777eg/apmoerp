<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: products
 * 
 * Main products table for inventory and sales.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prd_branch__brnch');
            $table->foreignId('module_id')
                ->nullable()
                ->constrained('modules')
                ->nullOnDelete()
                ->name('fk_prd_module__mod');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete()
                ->name('fk_prd_category__prdcat');
            
            // Basic info
            $table->string('name', 191);
            $table->string('sku', 100);
            $table->string('barcode', 100)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('image', 500)->nullable();
            $table->json('gallery')->nullable();
            
            // Type and classification
            $table->string('product_type', 50)->default('physical'); // physical, service, digital
            $table->string('type', 50)->nullable();
            $table->string('status', 30)->default('active');
            
            // Variations
            $table->boolean('has_variations')->default(false);
            $table->boolean('has_variants')->default(false);
            $table->foreignId('parent_product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_prd_parent__prd');
            $table->json('variation_attributes')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Units
            $table->string('uom', 30)->nullable();
            $table->decimal('uom_factor', 18, 4)->default(1);
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_prd_unit__uom');
            
            // Costing
            $table->string('cost_method', 30)->default('average'); // average, fifo, lifo, standard
            $table->string('cost_currency', 10)->nullable();
            $table->decimal('standard_cost', 18, 4)->default(0);
            $table->decimal('cost', 18, 4)->default(0);
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('taxes')
                ->nullOnDelete()
                ->name('fk_prd_tax__tax');
            
            // Pricing
            $table->foreignId('price_list_id')
                ->nullable()
                ->constrained('price_groups')
                ->nullOnDelete()
                ->name('fk_prd_price_list__prcgrp');
            $table->decimal('default_price', 18, 4)->default(0);
            $table->decimal('price', 18, 4)->default(0);
            $table->string('price_currency', 10)->nullable();
            $table->decimal('msrp', 18, 4)->nullable();
            $table->decimal('wholesale_price', 18, 4)->nullable();
            
            // Stock management
            $table->decimal('min_stock', 18, 4)->default(0);
            $table->decimal('reorder_point', 18, 4)->default(0);
            $table->decimal('max_stock', 18, 2)->nullable();
            $table->decimal('reorder_qty', 18, 4)->default(0);
            $table->decimal('stock_quantity', 18, 4)->default(0);
            $table->decimal('stock_alert_threshold', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->decimal('lead_time_days', 5, 1)->nullable();
            $table->string('location_code', 50)->nullable();
            
            // Tracking options
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('track_stock_alerts')->default(true);
            
            // Warranty
            $table->boolean('has_warranty')->default(false);
            $table->unsignedInteger('warranty_period_days')->nullable();
            $table->string('warranty_period', 50)->nullable();
            $table->string('warranty_type', 50)->nullable();
            
            // Dimensions
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            
            // Manufacturer info
            $table->string('manufacturer', 191)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->string('origin_country', 100)->nullable();
            $table->string('hs_code', 20)->nullable();
            
            // Perishable products
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_perishable')->default(false);
            $table->unsignedInteger('shelf_life_days')->nullable();
            
            // Ordering options
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->decimal('minimum_order_quantity', 18, 4)->nullable();
            $table->decimal('maximum_order_quantity', 18, 4)->nullable();
            
            // Service-specific
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->unsignedInteger('service_duration')->nullable();
            $table->string('duration_unit', 20)->nullable();
            
            // Price updates
            $table->date('last_cost_update')->nullable();
            $table->date('last_price_update')->nullable();
            
            // Notes and extra
            $table->text('notes')->nullable();
            $table->json('extra_attributes')->nullable();
            
            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prd_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prd_updated_by__usr');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['branch_id', 'sku'], 'uq_prd_branch_sku');
            $table->index('branch_id', 'idx_prd_branch_id');
            $table->index('category_id', 'idx_prd_category_id');
            $table->index('module_id', 'idx_prd_module_id');
            $table->index('status', 'idx_prd_status');
            $table->index('product_type', 'idx_prd_product_type');
            $table->index('barcode', 'idx_prd_barcode');
            $table->index('parent_product_id', 'idx_prd_parent_id');
            $table->index('is_serialized', 'idx_prd_is_serialized');
            $table->index('is_batch_tracked', 'idx_prd_is_batch_tracked');
            $table->index(['branch_id', 'id'], 'idx_prd_branch_id_id');
        });

        // Product variations
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdvar_product__prd');
            $table->string('sku', 100);
            $table->string('name', 191);
            $table->json('attributes')->nullable();
            $table->decimal('price', 18, 4)->default(0);
            $table->decimal('cost_price', 18, 4)->default(0);
            $table->decimal('current_stock', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'sku'], 'uq_prdvar_product_sku');
            $table->index('product_id', 'idx_prdvar_product_id');
            $table->index('is_active', 'idx_prdvar_is_active');
        });

        // Product price tiers
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdprc_product__prd');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prdprc_branch__brnch');
            $table->string('tier_name', 100);
            $table->string('tier_name_ar', 100)->nullable();
            $table->decimal('min_quantity', 18, 4)->default(1);
            $table->decimal('max_quantity', 18, 4)->nullable();
            $table->decimal('cost_price', 18, 4)->default(0);
            $table->decimal('selling_price', 18, 4)->default(0);
            $table->decimal('wholesale_price', 18, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'branch_id', 'tier_name'], 'uq_prdprc_prod_brnch_tier');
            $table->index('product_id', 'idx_prdprc_product_id');
            $table->index('branch_id', 'idx_prdprc_branch_id');
        });

        // Product field values (for custom module fields)
        Schema::create('product_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdfv_product__prd');
            $table->foreignId('module_product_field_id')
                ->constrained('module_product_fields')
                ->cascadeOnDelete()
                ->name('fk_prdfv_field__modpf');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'module_product_field_id'], 'uq_prdfv_prod_field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_field_values');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
    }
};
