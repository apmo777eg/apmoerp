<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: purchases tables
 * 
 * Purchases, purchase items, payments, requisitions, GRN.
 * 
 * Classification: BRANCH-OWNED (transactional)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Purchase requisitions
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prreq_branch__brnch');
            $table->string('code', 50);
            $table->string('department_id', 50)->nullable();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prreq_requested_by__usr');
            $table->string('status', 30)->default('draft'); // draft, pending, approved, rejected, converted
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->date('required_date')->nullable();
            $table->text('justification')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('estimated_total', 18, 4)->default(0);
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prreq_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->unsignedBigInteger('converted_to_po_id')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prreq_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prreq_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_prreq_branch_code');
            $table->index('branch_id', 'idx_prreq_branch_id');
            $table->index('status', 'idx_prreq_status');
            $table->index('required_date', 'idx_prreq_required_date');
            $table->index('priority', 'idx_prreq_priority');
        });

        // Purchase requisition items
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')
                ->constrained('purchase_requisitions')
                ->cascadeOnDelete()
                ->name('fk_prreqi_req__prreq');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_prreqi_product__prd');
            $table->decimal('quantity', 18, 4);
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_prreqi_unit__uom');
            $table->decimal('estimated_price', 18, 4)->default(0);
            $table->text('specifications')->nullable();
            $table->foreignId('preferred_supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->name('fk_prreqi_supplier__supp');
            $table->json('extra_attributes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prreqi_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prreqi_updated_by__usr');
            $table->timestamps();

            $table->index('requisition_id', 'idx_prreqi_req_id');
            $table->index('product_id', 'idx_prreqi_product_id');
        });

        // Supplier quotations
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_supqt_branch__brnch');
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete()
                ->name('fk_supqt_supplier__supp');
            $table->foreignId('requisition_id')
                ->nullable()
                ->constrained('purchase_requisitions')
                ->nullOnDelete()
                ->name('fk_supqt_req__prreq');
            $table->string('reference_number', 50);
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('status', 30)->default('pending'); // pending, accepted, rejected, expired
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_supqt_branch_ref');
            $table->index('branch_id', 'idx_supqt_branch_id');
            $table->index('supplier_id', 'idx_supqt_supplier_id');
            $table->index('status', 'idx_supqt_status');
            $table->index('quotation_date', 'idx_supqt_date');
        });

        // Supplier quotation items
        Schema::create('supplier_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')
                ->constrained('supplier_quotations')
                ->cascadeOnDelete()
                ->name('fk_supqti_quot__supqt');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_supqti_product__prd');
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 18, 4);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->index('quotation_id', 'idx_supqti_quot_id');
            $table->index('product_id', 'idx_supqti_product_id');
        });

        // Purchases
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_purch_branch__brnch');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_purch_warehouse__wh');
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->name('fk_purch_supplier__supp');
            $table->string('reference_number', 50);
            $table->string('external_reference', 100)->nullable();
            $table->string('supplier_invoice', 100)->nullable();
            $table->string('type', 30)->default('purchase_order'); // purchase_order, return
            $table->string('channel', 30)->nullable();
            $table->string('status', 30)->default('draft'); // draft, pending, confirmed, received, completed, cancelled
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partial, paid
            // Dates
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->date('expected_date')->nullable();
            // Amounts
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('shipping_amount', 18, 4)->default(0);
            $table->decimal('other_charges', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            // Additional
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('custom_fields')->nullable();
            // Approvals
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_purch_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_purch_updated_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_purch_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_purch_journal__je');
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_purch_branch_ref');
            $table->index('branch_id', 'idx_purch_branch_id');
            $table->index('supplier_id', 'idx_purch_supplier_id');
            $table->index('warehouse_id', 'idx_purch_warehouse_id');
            $table->index('status', 'idx_purch_status');
            $table->index('payment_status', 'idx_purch_payment_status');
            $table->index('purchase_date', 'idx_purch_date');
            $table->index('type', 'idx_purch_type');
            $table->index('created_by', 'idx_purch_created_by');
            $table->index(['branch_id', 'id'], 'idx_purch_branch_id_id');
        });

        // Update requisitions FK
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->foreign('converted_to_po_id', 'fk_prreq_converted__purch')
                ->references('id')
                ->on('purchases')
                ->nullOnDelete();
        });

        // Purchase items
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete()
                ->name('fk_purchi_purchase__purch');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_purchi_product__prd');
            $table->foreignId('variation_id')
                ->nullable()
                ->constrained('product_variations')
                ->nullOnDelete()
                ->name('fk_purchi_variation__prdvar');
            $table->string('product_name', 191)->nullable();
            $table->string('sku', 100)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_purchi_unit__uom');
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_id', 'idx_purchi_purchase_id');
            $table->index('product_id', 'idx_purchi_product_id');
        });

        // Purchase payments
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete()
                ->name('fk_purchp_purchase__purch');
            $table->string('reference_number', 50)->nullable();
            $table->decimal('amount', 18, 4);
            $table->string('payment_method', 50);
            $table->string('status', 30)->default('completed');
            $table->date('payment_date');
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('card_last_four', 4)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_purchp_paid_by__usr');
            $table->timestamps();

            $table->index('purchase_id', 'idx_purchp_purchase_id');
            $table->index('payment_date', 'idx_purchp_date');
            $table->index('status', 'idx_purchp_status');
        });

        // Goods received notes
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_grn_branch__brnch');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_grn_warehouse__wh');
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete()
                ->name('fk_grn_purchase__purch');
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->name('fk_grn_supplier__supp');
            $table->string('reference_number', 50);
            $table->string('supplier_delivery_note', 100)->nullable();
            $table->string('status', 30)->default('pending'); // pending, completed, cancelled
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->string('received_by_name', 191)->nullable();
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_grn_received_by__usr');
            $table->foreignId('inspected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_grn_inspected_by__usr');
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_grn_branch_ref');
            $table->index('branch_id', 'idx_grn_branch_id');
            $table->index('purchase_id', 'idx_grn_purchase_id');
            $table->index('status', 'idx_grn_status');
            $table->index('received_date', 'idx_grn_received_date');
        });

        // GRN items
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')
                ->constrained('goods_received_notes')
                ->cascadeOnDelete()
                ->name('fk_grni_grn__grn');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_grni_product__prd');
            $table->foreignId('purchase_item_id')
                ->nullable()
                ->constrained('purchase_items')
                ->nullOnDelete()
                ->name('fk_grni_purchase_item__purchi');
            $table->decimal('expected_quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('accepted_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->text('rejection_reason')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('quality_status', 30)->default('pending'); // pending, passed, failed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('grn_id', 'idx_grni_grn_id');
            $table->index('product_id', 'idx_grni_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropForeign('fk_prreq_converted__purch');
        });
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('supplier_quotation_items');
        Schema::dropIfExists('supplier_quotations');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};
