<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: sales tables
 * 
 * Sales, sale items, payments, receipts, deliveries.
 * 
 * Classification: BRANCH-OWNED (transactional)
 */
return new class extends Migration
{
    public function up(): void
    {
        // POS sessions
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_possn_branch__brnch');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_possn_user__usr');
            $table->string('session_number', 50);
            $table->decimal('opening_cash', 18, 4)->default(0);
            $table->decimal('closing_cash', 18, 4)->nullable();
            $table->decimal('expected_cash', 18, 4)->nullable();
            $table->decimal('cash_difference', 18, 4)->nullable();
            $table->json('payment_summary')->nullable();
            $table->unsignedInteger('total_transactions')->default(0);
            $table->decimal('total_sales', 18, 4)->default(0);
            $table->decimal('total_refunds', 18, 4)->default(0);
            $table->string('status', 30)->default('open'); // open, closed
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_possn_closed_by__usr');
            $table->timestamps();

            $table->unique(['branch_id', 'session_number'], 'uq_possn_branch_number');
            $table->index('branch_id', 'idx_possn_branch_id');
            $table->index('user_id', 'idx_possn_user_id');
            $table->index('status', 'idx_possn_status');
            $table->index('opened_at', 'idx_possn_opened_at');
        });

        // Sales
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_sale_branch__brnch');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_sale_warehouse__wh');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_sale_customer__cust');
            $table->string('reference_number', 50);
            $table->uuid('client_uuid')->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->string('type', 30)->default('invoice'); // invoice, quote, order
            $table->string('channel', 30)->default('pos'); // pos, online, phone
            $table->string('status', 30)->default('draft'); // draft, confirmed, completed, cancelled
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partial, paid
            // Dates
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->date('delivery_date')->nullable();
            // Amounts
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->string('discount_type', 20)->nullable(); // percentage, fixed
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('shipping_amount', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('change_amount', 18, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            // Shipping
            $table->text('shipping_address')->nullable();
            $table->string('shipping_method', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            // Additional
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('custom_fields')->nullable();
            // References
            $table->foreignId('store_order_id')
                ->nullable()
                ->constrained('store_orders')
                ->nullOnDelete()
                ->name('fk_sale_store_order__stord');
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->foreignId('salesperson_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_sale_salesperson__usr');
            $table->foreignId('pos_session_id')
                ->nullable()
                ->constrained('pos_sessions')
                ->nullOnDelete()
                ->name('fk_sale_session__possn');
            $table->boolean('is_pos_sale')->default(false);
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_sale_journal__je');
            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_sale_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_sale_updated_by__usr');
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_sale_branch_ref');
            $table->index('branch_id', 'idx_sale_branch_id');
            $table->index('customer_id', 'idx_sale_customer_id');
            $table->index('warehouse_id', 'idx_sale_warehouse_id');
            $table->index('status', 'idx_sale_status');
            $table->index('payment_status', 'idx_sale_payment_status');
            $table->index('sale_date', 'idx_sale_date');
            $table->index('type', 'idx_sale_type');
            $table->index('is_pos_sale', 'idx_sale_is_pos');
            $table->index('created_by', 'idx_sale_created_by');
            $table->index(['branch_id', 'id'], 'idx_sale_branch_id_id');
        });

        // Sale items
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->name('fk_salei_sale__sale');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_salei_product__prd');
            $table->foreignId('variation_id')
                ->nullable()
                ->constrained('product_variations')
                ->nullOnDelete()
                ->name('fk_salei_variation__prdvar');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete()
                ->name('fk_salei_warehouse__wh');
            $table->string('product_name', 191)->nullable();
            $table->string('sku', 100)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_salei_unit__uom');
            $table->decimal('unit_price', 18, 4);
            $table->decimal('cost_price', 18, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sale_id', 'idx_salei_sale_id');
            $table->index('product_id', 'idx_salei_product_id');
        });

        // Sale payments
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->name('fk_salep_sale__sale');
            $table->string('reference_number', 50)->nullable();
            $table->decimal('amount', 18, 4);
            $table->string('payment_method', 50); // cash, card, bank_transfer, cheque
            $table->string('status', 30)->default('completed'); // pending, completed, failed, refunded
            $table->date('payment_date');
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('card_last_four', 4)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_salep_received_by__usr');
            $table->timestamps();

            $table->index('sale_id', 'idx_salep_sale_id');
            $table->index('payment_date', 'idx_salep_date');
            $table->index('status', 'idx_salep_status');
            $table->index('payment_method', 'idx_salep_method');
        });

        // Receipts
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_rcpt_sale__sale');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_rcpt_branch__brnch');
            $table->string('code', 50);
            $table->decimal('amount', 18, 4);
            $table->timestamp('receipt_date');
            $table->string('payment_method', 50)->nullable();
            $table->json('extra_attributes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rcpt_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_rcpt_branch_code');
            $table->index('branch_id', 'idx_rcpt_branch_id');
            $table->index('sale_id', 'idx_rcpt_sale_id');
            $table->index('receipt_date', 'idx_rcpt_date');
        });

        // Deliveries
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->name('fk_dlv_sale__sale');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_dlv_branch__brnch');
            $table->string('code', 50);
            $table->string('status', 30)->default('pending'); // pending, in_transit, delivered, failed
            $table->date('scheduled_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('driver_name', 191)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->decimal('delivery_cost', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_dlv_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_dlv_branch_code');
            $table->index('branch_id', 'idx_dlv_branch_id');
            $table->index('sale_id', 'idx_dlv_sale_id');
            $table->index('status', 'idx_dlv_status');
            $table->index('scheduled_date', 'idx_dlv_scheduled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('pos_sessions');
    }
};
