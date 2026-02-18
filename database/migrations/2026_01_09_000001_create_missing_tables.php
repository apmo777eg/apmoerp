<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Missing tables identified from code analysis
 *
 * Tables required by the codebase but missing from migrations:
 * - report_schedules: Used by ScheduledReportService and Livewire components (DB::table('report_schedules'))
 * - product_compatibility: Used by SparePartsService for backward compatibility (DB::table('product_compatibility'))
 * - wood_conversions: Used by WoodService for wood conversion tracking
 * - wood_waste: Used by WoodService for wood waste tracking
 * - quotes: Referenced in KPIDashboardService (commented but may be needed)
 *
 * Classification: MIXED (some branch-owned, some global)
 */
return new class extends Migration
{
    public function up(): void
    {

        // Report Schedules (User-owned) - for scheduled report generation
        // Note: The existing 'scheduled_reports' table has different structure
        // This table is used by ScheduledReportService, Form.php, ScheduledReports.php
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')
                ->nullable()
                ->constrained('report_templates')
                ->nullOnDelete()
                ->name('fk_rptsch_template__rpttpl');
            $table->string('name', 191);
            $table->string('frequency', 30); // daily, weekly, monthly, quarterly
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0-6 for weekly
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-31 for monthly
            $table->string('time_of_day', 5)->default('08:00'); // HH:MM format
            $table->string('recipient_emails', 500);
            $table->string('format', 20)->default('pdf'); // pdf, excel, csv
            $table->json('filters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 30)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('runs_count')->default(0);
            $table->unsignedInteger('failures_count')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_rptsch_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active', 'idx_rptsch_is_active');
            $table->index('next_run_at', 'idx_rptsch_next_run');
            $table->index('created_by', 'idx_rptsch_created_by');
        });

        // Product Compatibility (Branch-owned via product) - for spare parts compatibility
        // Note: This is different from product_compatibilities which links to vehicle_models
        // This table is used by SparePartsService for product-to-product compatibility
        Schema::create('product_compatibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdcmp2_product__prd');
            $table->foreignId('compatible_with_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdcmp2_compat__prd');
            $table->text('notes')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'compatible_with_id'], 'uq_prdcmp2_prod_compat');
            $table->index('product_id', 'idx_prdcmp2_product_id');
            $table->index('compatible_with_id', 'idx_prdcmp2_compat_id');
        });

        // Wood Conversions (Branch-owned) - for wood processing tracking
        Schema::create('wood_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_woodcnv_branch__brnch');
            $table->string('input_uom', 50);
            $table->decimal('input_qty', 18, 4);
            $table->string('output_uom', 50);
            $table->decimal('output_qty', 18, 4);
            $table->decimal('efficiency', 8, 4)->default(0); // Percentage
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_woodcnv_created_by__usr');
            $table->timestamps();

            $table->index('branch_id', 'idx_woodcnv_branch_id');
            $table->index('created_at', 'idx_woodcnv_created_at');
        });

        // Wood Waste (Branch-owned) - for wood waste tracking
        Schema::create('wood_waste', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_woodwst_branch__brnch');
            $table->string('type', 50)->default('general');
            $table->decimal('qty', 18, 4);
            $table->string('uom', 50)->default('kg');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_woodwst_created_by__usr');
            $table->timestamps();

            $table->index('branch_id', 'idx_woodwst_branch_id');
            $table->index('type', 'idx_woodwst_type');
            $table->index('created_at', 'idx_woodwst_created_at');
        });

        // Quotes (Branch-owned) - for sales quotations
        // Referenced in KPIDashboardService for conversion metrics
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_quote_branch__brnch');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_quote_customer__cust');
            $table->string('reference_number', 50);
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->string('status', 30)->default('draft'); // draft, sent, accepted, rejected, expired, converted
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->foreignId('converted_to_sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete()
                ->name('fk_quote_converted_to_sale__sale');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_quote_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_quote_updated_by__usr');
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_quote_branch_ref');
            $table->index('branch_id', 'idx_quote_branch_id');
            $table->index('customer_id', 'idx_quote_customer_id');
            $table->index('status', 'idx_quote_status');
            $table->index('quote_date', 'idx_quote_date');
            $table->index('converted_to_sale_id', 'idx_quote_converted_to_sale_id');
        });

        // Quote Items
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')
                ->constrained('quotes')
                ->cascadeOnDelete()
                ->name('fk_quotei_quote__quote');
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->name('fk_quotei_product__prd');
            $table->string('product_name', 191)->nullable();
            $table->string('sku', 100)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 18, 4);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('quote_id', 'idx_quotei_quote_id');
            $table->index('product_id', 'idx_quotei_product_id');
        });

    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign('fk_quote_converted_to_sale__sale');
        });

        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('wood_waste');
        Schema::dropIfExists('wood_conversions');
        Schema::dropIfExists('product_compatibility');
        Schema::dropIfExists('report_schedules');
    }
};
