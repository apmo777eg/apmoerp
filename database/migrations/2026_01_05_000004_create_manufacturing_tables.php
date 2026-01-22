<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: manufacturing tables
 * 
 * BOM, work centers, production orders.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Work centers
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_wkctr_branch__brnch');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->string('type', 30)->nullable(); // machine, labor, outsourced
            $table->decimal('capacity_per_hour', 10, 2)->default(0);
            $table->decimal('cost_per_hour', 18, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->json('operating_hours')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_wkctr_branch_code');
            $table->index('branch_id', 'idx_wkctr_branch_id');
            $table->index('status', 'idx_wkctr_status');
        });

        // Bills of materials
        Schema::create('bills_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_bom_branch__brnch');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_bom_product__prd');
            $table->string('reference_number', 50);
            $table->string('name', 191);
            $table->string('version', 20)->default('1.0');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('yield_percentage', 5, 2)->default(100);
            $table->decimal('estimated_cost', 18, 4)->default(0);
            $table->decimal('estimated_time_hours', 8, 2)->default(0);
            $table->string('status', 30)->default('draft'); // draft, active, inactive
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_bom_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_bom_branch_ref');
            $table->index('branch_id', 'idx_bom_branch_id');
            $table->index('product_id', 'idx_bom_product_id');
            $table->index('status', 'idx_bom_status');
        });

        // BOM items
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')
                ->constrained('bills_of_materials')
                ->cascadeOnDelete()
                ->name('fk_bomi_bom__bom');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_bomi_product__prd');
            $table->decimal('quantity', 18, 4);
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_bomi_unit__uom');
            $table->decimal('scrap_percentage', 5, 2)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->string('type', 30)->default('raw_material'); // raw_material, sub_assembly
            $table->boolean('is_optional')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('bom_id', 'idx_bomi_bom_id');
            $table->index('product_id', 'idx_bomi_product_id');
        });

        // BOM operations
        Schema::create('bom_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')
                ->constrained('bills_of_materials')
                ->cascadeOnDelete()
                ->name('fk_bomop_bom__bom');
            $table->foreignId('work_center_id')
                ->nullable()
                ->constrained('work_centers')
                ->nullOnDelete()
                ->name('fk_bomop_work_center__wkctr');
            $table->string('operation_name', 191);
            $table->string('operation_name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->decimal('duration_minutes', 10, 2)->default(0);
            $table->decimal('setup_time_minutes', 10, 2)->default(0);
            $table->decimal('labor_cost', 18, 2)->default(0);
            $table->json('quality_criteria')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('bom_id', 'idx_bomop_bom_id');
            $table->index('work_center_id', 'idx_bomop_wkctr_id');
        });

        // Production orders
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prodord_branch__brnch');
            $table->foreignId('bom_id')
                ->nullable()
                ->constrained('bills_of_materials')
                ->nullOnDelete()
                ->name('fk_prodord_bom__bom');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prodord_product__prd');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_prodord_warehouse__wh');
            $table->string('reference_number', 50);
            $table->string('status', 30)->default('draft'); // draft, pending, in_progress, completed, cancelled
            $table->string('priority', 20)->default('normal');
            $table->decimal('planned_quantity', 18, 4);
            $table->decimal('produced_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();
            $table->decimal('estimated_cost', 18, 4)->default(0);
            $table->decimal('actual_cost', 18, 4)->default(0);
            $table->decimal('material_cost', 18, 4)->default(0);
            $table->decimal('labor_cost', 18, 4)->default(0);
            $table->decimal('overhead_cost', 18, 4)->default(0);
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_prodord_sale__sale');
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prodord_created_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prodord_approved_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_prodord_branch_ref');
            $table->index('branch_id', 'idx_prodord_branch_id');
            $table->index('product_id', 'idx_prodord_product_id');
            $table->index('status', 'idx_prodord_status');
            $table->index('planned_start_date', 'idx_prodord_start_date');
        });

        // Production order items
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')
                ->constrained('production_orders')
                ->cascadeOnDelete()
                ->name('fk_prodordi_order__prodord');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prodordi_product__prd');
            $table->decimal('quantity_required', 18, 4);
            $table->decimal('quantity_consumed', 18, 4)->default(0);
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_prodordi_unit__uom');
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_prodordi_warehouse__wh');
            $table->boolean('is_issued')->default(false);
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index('production_order_id', 'idx_prodordi_order_id');
            $table->index('product_id', 'idx_prodordi_product_id');
        });

        // Production order operations
        Schema::create('production_order_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')
                ->constrained('production_orders')
                ->cascadeOnDelete()
                ->name('fk_prodordop_order__prodord');
            $table->foreignId('bom_operation_id')
                ->nullable()
                ->constrained('bom_operations')
                ->nullOnDelete()
                ->name('fk_prodordop_bomop__bomop');
            $table->foreignId('work_center_id')
                ->nullable()
                ->constrained('work_centers')
                ->nullOnDelete()
                ->name('fk_prodordop_wkctr__wkctr');
            $table->string('operation_name', 191);
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed, skipped
            $table->decimal('planned_duration_minutes', 10, 2)->default(0);
            $table->decimal('actual_duration_minutes', 10, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prodordop_operator__usr');
            $table->text('notes')->nullable();
            $table->json('quality_results')->nullable();
            $table->timestamps();

            $table->index('production_order_id', 'idx_prodordop_order_id');
            $table->index('status', 'idx_prodordop_status');
        });

        // Manufacturing transactions
        Schema::create('manufacturing_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')
                ->constrained('production_orders')
                ->cascadeOnDelete()
                ->name('fk_mfgtxn_order__prodord');
            $table->string('transaction_type', 30); // material_issue, labor, overhead, output
            $table->decimal('amount', 18, 2);
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_mfgtxn_journal__je');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('production_order_id', 'idx_mfgtxn_order_id');
            $table->index('transaction_type', 'idx_mfgtxn_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_transactions');
        Schema::dropIfExists('production_order_operations');
        Schema::dropIfExists('production_order_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bom_operations');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bills_of_materials');
        Schema::dropIfExists('work_centers');
    }
};
