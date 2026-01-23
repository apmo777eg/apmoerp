<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: loyalty and installment tables
 *
 * Loyalty settings, transactions, installment plans, payments.
 *
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Loyalty settings
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_loyalset_branch__brnch');
            $table->string('setting_key', 100);
            $table->decimal('points_per_unit', 10, 2)->default(1);
            $table->decimal('unit_amount', 18, 4)->default(1);
            $table->decimal('redemption_value', 18, 4)->default(0);
            $table->unsignedSmallInteger('expiry_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'setting_key'], 'uq_loyalset_branch_key');
        });

        // Loyalty transactions
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->name('fk_loyaltxn_customer__cust');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_loyaltxn_branch__brnch');
            $table->string('transaction_type', 30); // earn, redeem, expire, adjustment
            $table->integer('points');
            $table->integer('balance_after')->default(0);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_loyaltxn_created_by__usr');
            $table->timestamps();

            $table->index('customer_id', 'idx_loyaltxn_customer_id');
            $table->index('branch_id', 'idx_loyaltxn_branch_id');
            $table->index('transaction_type', 'idx_loyaltxn_type');
            $table->index(['reference_type', 'reference_id'], 'idx_loyaltxn_reference');
        });

        // Installment plans
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_instpln_branch__brnch');
            $table->string('plan_number', 50);
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->name('fk_instpln_customer__cust');
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_instpln_sale__sale');
            $table->decimal('total_amount', 18, 4);
            $table->decimal('down_payment', 18, 4)->default(0);
            $table->decimal('remaining_amount', 18, 4)->default(0); // NEW-005 FIX
            $table->decimal('financed_amount', 18, 4);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('total_interest', 18, 4)->default(0);
            $table->unsignedSmallInteger('num_installments')->default(0); // NEW-005 FIX
            $table->decimal('installment_amount', 18, 4)->default(0); // NEW-005 FIX
            $table->unsignedSmallInteger('installments_count');
            $table->string('frequency', 20)->default('monthly'); // weekly, bi_weekly, monthly
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('active'); // active, completed, defaulted, cancelled
            $table->text('notes')->nullable(); // NEW-005 FIX
            $table->foreignId('created_by') // NEW-005 FIX
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_instpln_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'plan_number'], 'uq_instpln_branch_number');
            $table->index('customer_id', 'idx_instpln_customer_id');
            $table->index('status', 'idx_instpln_status');
        });

        // Installment payments
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')
                ->constrained('installment_plans')
                ->cascadeOnDelete()
                ->name('fk_instpay_plan__instpln');
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount_due', 18, 4);
            $table->decimal('amount_paid', 18, 4)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('status', 30)->default('pending'); // pending, paid, partial, overdue
            $table->decimal('late_fee', 18, 4)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable(); // NEW-005 FIX
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by') // NEW-005 FIX
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_instpay_paid_by__usr');
            $table->timestamps();

            $table->unique(['installment_plan_id', 'installment_number'], 'uq_instpay_plan_number');
            $table->index('due_date', 'idx_instpay_due_date');
            $table->index('status', 'idx_instpay_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_settings');
    }
};
