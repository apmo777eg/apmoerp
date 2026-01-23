<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: vehicle management tables
 * 
 * Vehicles, contracts, payments, warranties.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_veh_branch__brnch');
            $table->string('vin', 50)->nullable();
            $table->string('plate', 50)->nullable();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('status', 30)->default('available'); // available, sold, reserved, leased
            $table->decimal('sale_price', 18, 2)->nullable();
            $table->decimal('cost', 18, 2)->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id', 'idx_veh_branch_id');
            $table->index('status', 'idx_veh_status');
            $table->index('vin', 'idx_veh_vin');
            $table->index('plate', 'idx_veh_plate');
        });

        // Vehicle contracts
        Schema::create('vehicle_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->cascadeOnDelete()
                ->name('fk_vehctr_vehicle__veh');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_vehctr_branch__brnch');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete()
                ->name('fk_vehctr_customer__cust');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('price', 18, 2);
            $table->string('status', 30)->default('active'); // active, completed, cancelled
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('vehicle_id', 'idx_vehctr_vehicle_id');
            $table->index('customer_id', 'idx_vehctr_customer_id');
            $table->index('status', 'idx_vehctr_status');
        });

        // Vehicle payments
        Schema::create('vehicle_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')
                ->constrained('vehicle_contracts')
                ->cascadeOnDelete()
                ->name('fk_vehpay_contract__vehctr');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_vehpay_branch__brnch');
            $table->string('method', 50);
            $table->decimal('amount', 18, 2);
            $table->timestamp('paid_at');
            $table->string('reference', 100)->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->index('contract_id', 'idx_vehpay_contract_id');
            $table->index('paid_at', 'idx_vehpay_paid_at');
        });

        // Warranties
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained('vehicles')
                ->nullOnDelete()
                ->name('fk_warr_vehicle__veh');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_warr_product__prd');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_warr_branch__brnch');
            $table->string('provider', 191);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('vehicle_id', 'idx_warr_vehicle_id');
            $table->index('product_id', 'idx_warr_product_id');
            $table->index(['start_date', 'end_date'], 'idx_warr_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
        Schema::dropIfExists('vehicle_payments');
        Schema::dropIfExists('vehicle_contracts');
        Schema::dropIfExists('vehicles');
    }
};
