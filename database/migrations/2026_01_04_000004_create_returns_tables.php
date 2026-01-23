<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: sales and purchase returns
 *
 * Return notes, sales returns, purchase returns, credit/debit notes.
 *
 * Classification: BRANCH-OWNED (transactional)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Return notes (generic)
        Schema::create('return_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_rtn_branch__brnch');
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_rtn_sale__sale');
            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->nullOnDelete()
                ->name('fk_rtn_purchase__purch');
            $table->string('code', 50);
            $table->string('type', 30); // sale_return, purchase_return
            $table->string('status', 30)->default('pending');
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->boolean('restock')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rtn_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_rtn_branch_code');
            $table->index('branch_id', 'idx_rtn_branch_id');
            $table->index('sale_id', 'idx_rtn_sale_id');
            $table->index('purchase_id', 'idx_rtn_purchase_id');
            $table->index('type', 'idx_rtn_type');
            $table->index('status', 'idx_rtn_status');
        });

        // Sales returns
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50);
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_slrtn_sale__sale');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_slrtn_branch__brnch');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_slrtn_warehouse__wh');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_slrtn_customer__cust');
            $table->string('return_type', 30)->default('full'); // full, partial
            $table->string('status', 30)->default('pending'); // pending, approved, completed, rejected
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('refund_amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('refund_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_slrtn_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_slrtn_processed_by__usr');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_slrtn_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_slrtn_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'return_number'], 'uq_slrtn_branch_number');
            $table->index('branch_id', 'idx_slrtn_branch_id');
            $table->index('sale_id', 'idx_slrtn_sale_id');
            $table->index('status', 'idx_slrtn_status');
            $table->index('created_at', 'idx_slrtn_created_at');
        });

        // Sales return items
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')
                ->constrained('sales_returns')
                ->cascadeOnDelete()
                ->name('fk_slrtni_return__slrtn');
            $table->foreignId('sale_item_id')
                ->nullable()
                ->constrained('sale_items')
                ->nullOnDelete()
                ->name('fk_slrtni_sale_item__salei');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_slrtni_product__prd');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_slrtni_branch__brnch');
            $table->decimal('qty_returned', 18, 4);
            $table->decimal('qty_original', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('discount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4)->default(0);
            $table->string('condition', 30)->nullable(); // good, damaged, defective
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('restock')->default(true);
            $table->foreignId('restocked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_slrtni_restocked_by__usr');
            $table->timestamp('restocked_at')->nullable();
            $table->timestamps();

            $table->index('sales_return_id', 'idx_slrtni_return_id');
            $table->index('product_id', 'idx_slrtni_product_id');
        });

        // Purchase returns
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50);
            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->nullOnDelete()
                ->name('fk_prrtn_purchase__purch');
            $table->foreignId('grn_id')
                ->nullable()
                ->constrained('goods_received_notes')
                ->nullOnDelete()
                ->name('fk_prrtn_grn__grn');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prrtn_branch__brnch');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_prrtn_warehouse__wh');
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->name('fk_prrtn_supplier__supp');
            $table->string('return_type', 30)->default('full');
            $table->string('status', 30)->default('pending');
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('expected_credit', 18, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->date('return_date')->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('courier_name', 100)->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_by_supplier_date')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prrtn_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prrtn_shipped_by__usr');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prrtn_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prrtn_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'return_number'], 'uq_prrtn_branch_number');
            $table->index('branch_id', 'idx_prrtn_branch_id');
            $table->index('purchase_id', 'idx_prrtn_purchase_id');
            $table->index('status', 'idx_prrtn_status');
        });

        // Purchase return items
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')
                ->constrained('purchase_returns')
                ->cascadeOnDelete()
                ->name('fk_prrtni_return__prrtn');
            $table->foreignId('purchase_item_id')
                ->nullable()
                ->constrained('purchase_items')
                ->nullOnDelete()
                ->name('fk_prrtni_purch_item__purchi');
            $table->foreignId('grn_item_id')
                ->nullable()
                ->constrained('grn_items')
                ->nullOnDelete()
                ->name('fk_prrtni_grn_item__grni');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_prrtni_product__prd');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_prrtni_branch__brnch');
            $table->decimal('qty_returned', 18, 3);
            $table->decimal('qty_original', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4)->default(0);
            $table->string('condition', 30)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('deduct_from_stock')->default(true);
            $table->foreignId('deducted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_prrtni_deducted_by__usr');
            $table->timestamp('deducted_at')->nullable();
            $table->timestamps();

            $table->index('purchase_return_id', 'idx_prrtni_return_id');
            $table->index('product_id', 'idx_prrtni_product_id');
        });

        // Return refunds
        Schema::create('return_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_rtnrfnd_branch__brnch');
            $table->string('refund_number', 50);
            $table->string('refundable_type', 100);
            $table->unsignedBigInteger('refundable_id');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_rtnrfnd_customer__cust');
            $table->decimal('amount', 18, 2);
            $table->string('method', 50); // cash, card, bank_transfer, credit_note
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rtnrfnd_processed_by__usr');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rtnrfnd_created_by__usr');
            $table->timestamps();

            $table->unique(['branch_id', 'refund_number'], 'uq_rtnrfnd_branch_number');
            $table->index('branch_id', 'idx_rtnrfnd_branch_id');
            $table->index(['refundable_type', 'refundable_id'], 'idx_rtnrfnd_refundable');
            $table->index('status', 'idx_rtnrfnd_status');
        });

        // Credit notes
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_crnt_branch__brnch');
            $table->string('credit_note_number', 50);
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_crnt_customer__cust');
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_crnt_sale__sale');
            $table->foreignId('sales_return_id')
                ->nullable()
                ->constrained('sales_returns')
                ->nullOnDelete()
                ->name('fk_crnt_return__slrtn');
            $table->string('type', 30)->default('return'); // return, adjustment, goodwill
            $table->decimal('amount', 18, 2)->default(0); // NEW-005 FIX
            $table->date('issue_date');
            $table->date('applied_date')->nullable(); // NEW-005 FIX
            $table->date('expiry_date')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('remaining_amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('draft'); // draft, issued, partially_applied, fully_applied, cancelled
            $table->boolean('is_refundable')->default(true);
            $table->boolean('is_refunded')->default(false);
            $table->boolean('auto_apply')->default(false); // NEW-005 FIX
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            // NEW-005 FIX: Added accounting integration columns
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_crnt_journal__je');
            $table->boolean('posted_to_accounting')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_crnt_created_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_crnt_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('updated_by') // NEW-005 FIX
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_crnt_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'credit_note_number'], 'uq_crnt_branch_number');
            $table->index('branch_id', 'idx_crnt_branch_id');
            $table->index('customer_id', 'idx_crnt_customer_id');
            $table->index('status', 'idx_crnt_status');
            $table->index('issue_date', 'idx_crnt_issue_date');
        });

        // Credit note applications
        Schema::create('credit_note_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')
                ->constrained('credit_notes')
                ->cascadeOnDelete()
                ->name('fk_crnta_credit_note__crnt');
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->name('fk_crnta_sale__sale');
            $table->decimal('amount', 18, 2);
            $table->decimal('applied_amount', 18, 2)->default(0); // NEW-005 FIX: Model uses applied_amount
            $table->date('application_date');
            $table->text('notes')->nullable();
            $table->foreignId('applied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_crnta_applied_by__usr');
            $table->timestamps();

            $table->index('credit_note_id', 'idx_crnta_credit_note');
            $table->index('sale_id', 'idx_crnta_sale_id');
        });

        // Debit notes
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_dbnt_branch__brnch');
            $table->string('debit_note_number', 50);
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->name('fk_dbnt_supplier__supp');
            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->nullOnDelete()
                ->name('fk_dbnt_purchase__purch');
            $table->foreignId('purchase_return_id')
                ->nullable()
                ->constrained('purchase_returns')
                ->nullOnDelete()
                ->name('fk_dbnt_return__prrtn');
            $table->string('type', 30)->default('return');
            $table->decimal('amount', 18, 2)->default(0); // NEW-005 FIX
            $table->date('issue_date');
            $table->date('applied_date')->nullable(); // NEW-005 FIX
            $table->date('expiry_date')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->decimal('remaining_amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('draft');
            $table->boolean('is_refundable')->default(true);
            $table->boolean('is_refunded')->default(false);
            $table->boolean('auto_apply')->default(false); // NEW-005 FIX
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            // NEW-005 FIX: Added accounting integration columns
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_dbnt_journal__je');
            $table->boolean('posted_to_accounting')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_dbnt_created_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_dbnt_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('updated_by') // NEW-005 FIX
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_dbnt_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'debit_note_number'], 'uq_dbnt_branch_number');
            $table->index('branch_id', 'idx_dbnt_branch_id');
            $table->index('supplier_id', 'idx_dbnt_supplier_id');
            $table->index('status', 'idx_dbnt_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('credit_note_applications');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('return_refunds');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('return_notes');
    }
};
