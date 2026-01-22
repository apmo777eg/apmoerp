<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: inventory tables
 * 
 * Stock movements, batches, serials, adjustments, transfers.
 * 
 * Classification: BRANCH-OWNED (transactional)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Inventory batches
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_invbch_product__prd');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_invbch_warehouse__wh');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_invbch_branch__brnch');
            $table->string('batch_number', 100);
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->string('supplier_batch_ref', 100)->nullable();
            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->nullOnDelete()
                ->name('fk_invbch_purchase__purch');
            $table->string('status', 30)->default('available'); // available, reserved, expired, consumed
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'batch_number'], 'uq_invbch_prod_wh_batch');
            $table->index('branch_id', 'idx_invbch_branch_id');
            $table->index('product_id', 'idx_invbch_product_id');
            $table->index('warehouse_id', 'idx_invbch_warehouse_id');
            $table->index('expiry_date', 'idx_invbch_expiry');
            $table->index('status', 'idx_invbch_status');
        });

        // Inventory serials
        Schema::create('inventory_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_invser_product__prd');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_invser_warehouse__wh');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_invser_branch__brnch');
            $table->string('serial_number', 100);
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('inventory_batches')
                ->nullOnDelete()
                ->name('fk_invser_batch__invbch');
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->string('status', 30)->default('available'); // available, sold, reserved, returned, defective
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_invser_customer__cust');
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_invser_sale__sale');
            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->nullOnDelete()
                ->name('fk_invser_purchase__purch');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'serial_number'], 'uq_invser_prod_serial');
            $table->index('branch_id', 'idx_invser_branch_id');
            $table->index('warehouse_id', 'idx_invser_warehouse_id');
            $table->index('status', 'idx_invser_status');
            $table->index('serial_number', 'idx_invser_serial');
        });

        // Stock adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_stkadjt_branch__brnch');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_stkadjt_warehouse__wh');
            $table->string('reference_number', 50);
            $table->string('adjustment_type', 30); // increase, decrease, count
            $table->string('status', 30)->default('draft'); // draft, pending, approved, completed
            $table->text('reason')->nullable();
            $table->decimal('total_adjustment_value', 18, 4)->default(0);
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stkadjt_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stkadjt_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_stkadjt_branch_ref');
            $table->index('branch_id', 'idx_stkadjt_branch_id');
            $table->index('warehouse_id', 'idx_stkadjt_warehouse_id');
            $table->index('status', 'idx_stkadjt_status');
            $table->index('created_by', 'idx_stkadjt_created_by');
        });

        // Adjustment items
        Schema::create('adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')
                ->constrained('stock_adjustments')
                ->cascadeOnDelete()
                ->name('fk_stkadjti_adj__stkadjt');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_stkadjti_product__prd');
            $table->decimal('system_quantity', 18, 4)->default(0);
            $table->decimal('counted_quantity', 18, 4)->default(0);
            $table->decimal('difference', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('adjustment_id', 'idx_stkadjti_adj_id');
            $table->index('product_id', 'idx_stkadjti_product_id');
        });

        // Stock transfers
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_trf_branch__brnch');
            $table->string('reference_number', 50);
            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_trf_from_wh__wh');
            $table->foreignId('to_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_trf_to_wh__wh');
            $table->string('status', 30)->default('draft'); // draft, pending, in_transit, completed, cancelled
            $table->text('notes')->nullable();
            $table->decimal('total_value', 18, 4)->default(0);
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_trf_created_by__usr');
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_trf_received_by__usr');
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_trf_branch_ref');
            $table->index('branch_id', 'idx_trf_branch_id');
            $table->index('from_warehouse_id', 'idx_trf_from_wh');
            $table->index('to_warehouse_id', 'idx_trf_to_wh');
            $table->index('status', 'idx_trf_status');
            $table->index('created_by', 'idx_trf_created_by');
        });

        // Transfer items
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')
                ->constrained('transfers')
                ->cascadeOnDelete()
                ->name('fk_trfi_transfer__trf');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_trfi_product__prd');
            $table->decimal('quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('transfer_id', 'idx_trfi_transfer_id');
            $table->index('product_id', 'idx_trfi_product_id');
        });

        // Stock transfers (detailed version with approvals)
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_stktrf_branch__brnch');
            $table->string('reference_number', 50);
            $table->string('type', 30)->default('internal'); // internal, inter_branch
            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_stktrf_from_wh__wh');
            $table->foreignId('to_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_stktrf_to_wh__wh');
            $table->foreignId('from_branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_stktrf_from_branch__brnch');
            $table->foreignId('to_branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_stktrf_to_branch__brnch');
            $table->string('status', 30)->default('draft');
            $table->string('priority', 20)->default('normal');
            $table->date('transfer_date');
            $table->date('expected_arrival_date')->nullable();
            $table->date('actual_arrival_date')->nullable();
            $table->decimal('total_items', 18, 3)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->string('shipping_method', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_auto_generated')->default(false);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stktrf_created_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stktrf_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stktrf_shipped_by__usr');
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stktrf_received_by__usr');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_stktrf_branch_ref');
            $table->index('branch_id', 'idx_stktrf_branch_id');
            $table->index('status', 'idx_stktrf_status');
            $table->index('transfer_date', 'idx_stktrf_date');
        });

        // Stock transfer items
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')
                ->constrained('stock_transfers')
                ->cascadeOnDelete()
                ->name('fk_stktrfi_transfer__stktrf');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_stktrfi_product__prd');
            $table->string('sku', 100)->nullable();
            $table->decimal('quantity_requested', 18, 3);
            $table->decimal('quantity_shipped', 18, 3)->default(0);
            $table->decimal('quantity_received', 18, 3)->default(0);
            $table->decimal('quantity_damaged', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('stock_transfer_id', 'idx_stktrfi_transfer_id');
            $table->index('product_id', 'idx_stktrfi_product_id');
        });

        // Stock transfer approvals
        Schema::create('stock_transfer_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')
                ->constrained('stock_transfers')
                ->cascadeOnDelete()
                ->name('fk_stktrfa_transfer__stktrf');
            $table->foreignId('approver_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_stktrfa_approver__usr');
            $table->string('status', 30)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('stock_transfer_id', 'idx_stktrfa_transfer_id');
        });

        // Stock transfer documents
        Schema::create('stock_transfer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')
                ->constrained('stock_transfers')
                ->cascadeOnDelete()
                ->name('fk_stktrfd_transfer__stktrf');
            $table->string('document_type', 50);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stktrfd_uploaded_by__usr');
            $table->timestamps();

            $table->index('stock_transfer_id', 'idx_stktrfd_transfer_id');
        });

        // Stock transfer history
        Schema::create('stock_transfer_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')
                ->constrained('stock_transfers')
                ->cascadeOnDelete()
                ->name('fk_stktrfh_transfer__stktrf');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stktrfh_user__usr');
            $table->string('action', 50);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index('stock_transfer_id', 'idx_stktrfh_transfer_id');
        });

        // Inventory in transit
        Schema::create('inventory_transits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_invtrs_product__prd');
            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_invtrs_from_wh__wh');
            $table->foreignId('to_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_invtrs_to_wh__wh');
            $table->foreignId('stock_transfer_id')
                ->nullable()
                ->constrained('stock_transfers')
                ->nullOnDelete()
                ->name('fk_invtrs_transfer__stktrf');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('in_transit'); // in_transit, received, cancelled
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('expected_arrival')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_invtrs_created_by__usr');
            $table->timestamps();

            $table->index('product_id', 'idx_invtrs_product_id');
            $table->index('from_warehouse_id', 'idx_invtrs_from_wh');
            $table->index('to_warehouse_id', 'idx_invtrs_to_wh');
            $table->index('status', 'idx_invtrs_status');
        });

        // Stock movements
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_stkmv_product__prd');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_stkmv_warehouse__wh');
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('inventory_batches')
                ->nullOnDelete()
                ->name('fk_stkmv_batch__invbch');
            $table->string('movement_type', 30); // purchase, sale, transfer_in, transfer_out, adjustment, return, initial
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('stock_before', 18, 4)->default(0);
            $table->decimal('stock_after', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_stkmv_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id', 'idx_stkmv_product_id');
            $table->index('warehouse_id', 'idx_stkmv_warehouse_id');
            $table->index('movement_type', 'idx_stkmv_type');
            $table->index(['reference_type', 'reference_id'], 'idx_stkmv_reference');
            $table->index('created_at', 'idx_stkmv_created_at');
            $table->index('created_by', 'idx_stkmv_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_transits');
        Schema::dropIfExists('stock_transfer_history');
        Schema::dropIfExists('stock_transfer_documents');
        Schema::dropIfExists('stock_transfer_approvals');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('inventory_serials');
        Schema::dropIfExists('inventory_batches');
    }
};
