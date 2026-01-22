<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: rental property management tables
 * 
 * Properties, units, tenants, contracts, invoices.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Properties
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_prop_branch__brnch');
            $table->string('name', 191);
            $table->string('code', 50)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_prop_branch_code');
            $table->index('branch_id', 'idx_prop_branch_id');
        });

        // Rental units
        Schema::create('rental_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_rntunt_branch__brnch');
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete()
                ->name('fk_rntunt_property__prop');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('type', 30)->default('apartment'); // apartment, villa, office, shop
            $table->string('floor', 20)->nullable();
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->decimal('daily_rate', 18, 4)->nullable();
            $table->decimal('weekly_rate', 18, 4)->nullable();
            $table->decimal('monthly_rate', 18, 4)->nullable();
            $table->decimal('yearly_rate', 18, 4)->nullable();
            $table->decimal('deposit_amount', 18, 4)->default(0);
            $table->boolean('utilities_included')->default(false);
            $table->decimal('electricity_meter', 18, 2)->nullable();
            $table->decimal('water_meter', 18, 2)->nullable();
            $table->string('status', 30)->default('available'); // available, occupied, maintenance, reserved
            $table->boolean('is_active')->default(true);
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'code'], 'uq_rntunt_property_code');
            $table->index('branch_id', 'idx_rntunt_branch_id');
            $table->index('property_id', 'idx_rntunt_property_id');
            $table->index('status', 'idx_rntunt_status');
            $table->index('is_active', 'idx_rntunt_is_active');
        });

        // Tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_tnt_branch__brnch');
            $table->string('name', 191);
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id', 'idx_tnt_branch_id');
            $table->index('is_active', 'idx_tnt_is_active');
        });

        // Rental contracts
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_rntctr_branch__brnch');
            $table->foreignId('unit_id')
                ->constrained('rental_units')
                ->cascadeOnDelete()
                ->name('fk_rntctr_unit__rntunt');
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete()
                ->name('fk_rntctr_tenant__tnt');
            $table->string('contract_number', 50);
            $table->string('type', 30)->default('lease'); // lease, sublease, short_term
            $table->string('status', 30)->default('draft'); // draft, active, expired, terminated
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_end_date')->nullable();
            $table->timestamp('expiration_notified_at')->nullable();
            $table->decimal('rent_amount', 18, 4);
            $table->string('rent_frequency', 30)->default('monthly'); // daily, weekly, monthly, yearly
            $table->decimal('deposit_amount', 18, 4)->default(0);
            $table->decimal('deposit_paid', 18, 4)->default(0);
            $table->unsignedTinyInteger('payment_day')->default(1);
            $table->decimal('late_fee_amount', 18, 4)->nullable();
            $table->decimal('late_fee_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->boolean('utilities_included')->default(false);
            $table->decimal('electricity_opening', 18, 2)->nullable();
            $table->decimal('water_opening', 18, 2)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('special_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->json('documents')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rntctr_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'contract_number'], 'uq_rntctr_branch_number');
            $table->index('branch_id', 'idx_rntctr_branch_id');
            $table->index('unit_id', 'idx_rntctr_unit_id');
            $table->index('tenant_id', 'idx_rntctr_tenant_id');
            $table->index('status', 'idx_rntctr_status');
            $table->index(['start_date', 'end_date'], 'idx_rntctr_dates');
        });

        // Rental periods
        Schema::create('rental_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')
                ->constrained('rental_contracts')
                ->cascadeOnDelete()
                ->name('fk_rntprd_contract__rntctr');
            $table->unsignedSmallInteger('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date');
            $table->decimal('rent_amount', 18, 4);
            $table->string('status', 30)->default('pending'); // pending, invoiced, paid, overdue
            $table->boolean('is_prorated')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->unique(['contract_id', 'period_number'], 'uq_rntprd_contract_period');
            $table->index('contract_id', 'idx_rntprd_contract_id');
            $table->index('status', 'idx_rntprd_status');
            $table->index('due_date', 'idx_rntprd_due_date');
        });

        // Rental invoices
        Schema::create('rental_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')
                ->constrained('rental_contracts')
                ->cascadeOnDelete()
                ->name('fk_rntinv_contract__rntctr');
            $table->string('code', 50);
            $table->string('period', 50)->nullable();
            $table->date('due_date');
            $table->decimal('amount', 18, 2);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->string('status', 30)->default('pending'); // pending, partial, paid, overdue
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contract_id', 'code'], 'uq_rntinv_contract_code');
            $table->index('contract_id', 'idx_rntinv_contract_id');
            $table->index('status', 'idx_rntinv_status');
            $table->index('due_date', 'idx_rntinv_due_date');
        });

        // Rental payments
        Schema::create('rental_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_rntpay_branch__brnch');
            $table->foreignId('contract_id')
                ->constrained('rental_contracts')
                ->cascadeOnDelete()
                ->name('fk_rntpay_contract__rntctr');
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('rental_invoices')
                ->nullOnDelete()
                ->name('fk_rntpay_invoice__rntinv');
            $table->string('method', 50);
            $table->decimal('amount', 18, 2);
            $table->timestamp('paid_at');
            $table->string('reference', 100)->nullable();
            $table->json('extra_attributes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rntpay_created_by__usr');
            $table->timestamps();

            $table->index('branch_id', 'idx_rntpay_branch_id');
            $table->index('contract_id', 'idx_rntpay_contract_id');
            $table->index('invoice_id', 'idx_rntpay_invoice_id');
            $table->index('paid_at', 'idx_rntpay_paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_payments');
        Schema::dropIfExists('rental_invoices');
        Schema::dropIfExists('rental_periods');
        Schema::dropIfExists('rental_contracts');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('rental_units');
        Schema::dropIfExists('properties');
    }
};
