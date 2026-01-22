<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alert and monitoring tables
 * 
 * Alert rules, instances, recipients, anomaly baselines, low stock alerts.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Alert rules
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_altrule_branch__brnch');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('alert_type', 50); // threshold, anomaly, schedule
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->json('conditions')->nullable();
            $table->json('thresholds')->nullable();
            $table->string('metric_type', 50)->nullable();
            $table->unsignedSmallInteger('check_frequency_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->boolean('send_email')->default(false);
            $table->boolean('send_notification')->default(true);
            $table->json('recipient_roles')->nullable();
            $table->json('recipient_users')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id', 'idx_altrule_branch_id');
            $table->index('alert_type', 'idx_altrule_type');
            $table->index('is_active', 'idx_altrule_is_active');
            $table->index('category', 'idx_altrule_category');
        });

        // Alert instances
        Schema::create('alert_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')
                ->constrained('alert_rules')
                ->cascadeOnDelete()
                ->name('fk_altinst_rule__altrule');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_altinst_branch__brnch');
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->json('data')->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action_url', 500)->nullable();
            $table->string('status', 30)->default('active'); // active, acknowledged, resolved
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_altinst_ack_by__usr');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_altinst_resolved_by__usr');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('alert_rule_id', 'idx_altinst_rule_id');
            $table->index('branch_id', 'idx_altinst_branch_id');
            $table->index('status', 'idx_altinst_status');
            $table->index('severity', 'idx_altinst_severity');
            $table->index('triggered_at', 'idx_altinst_triggered');
        });

        // Alert recipients
        Schema::create('alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_instance_id')
                ->constrained('alert_instances')
                ->cascadeOnDelete()
                ->name('fk_altrcpt_inst__altinst');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_altrcpt_user__usr');
            $table->boolean('notification_sent')->default(false);
            $table->boolean('email_sent')->default(false);
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['alert_instance_id', 'user_id'], 'uq_altrcpt_inst_user');
            $table->index('user_id', 'idx_altrcpt_user_id');
        });

        // Anomaly baselines
        Schema::create('anomaly_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_anmbase_branch__brnch');
            $table->string('metric_key', 100);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->decimal('mean', 18, 4)->default(0);
            $table->decimal('std_dev', 18, 4)->default(0);
            $table->decimal('min', 18, 4)->default(0);
            $table->decimal('max', 18, 4)->default(0);
            $table->unsignedInteger('sample_count')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'metric_key', 'entity_type', 'entity_id', 'period_start'], 'uq_anmbase_metric');
            $table->index('branch_id', 'idx_anmbase_branch_id');
            $table->index('metric_key', 'idx_anmbase_metric_key');
        });

        // Low stock alerts
        Schema::create('low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_lowstk_product__prd');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_lowstk_branch__brnch');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->cascadeOnDelete()
                ->name('fk_lowstk_warehouse__wh');
            $table->decimal('current_stock', 18, 4)->default(0);
            $table->decimal('alert_threshold', 18, 4)->default(0);
            $table->string('status', 30)->default('active'); // active, acknowledged, resolved
            $table->foreignId('acknowledged_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_lowstk_ack_by__usr');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_lowstk_resolved_by__usr');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'uq_lowstk_prod_wh');
            $table->index('branch_id', 'idx_lowstk_branch_id');
            $table->index('status', 'idx_lowstk_status');
        });

        // Supplier performance metrics
        Schema::create('supplier_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete()
                ->name('fk_supperf_supplier__supp');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_supperf_branch__brnch');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            // Order metrics
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('on_time_deliveries')->default(0);
            $table->unsignedInteger('late_deliveries')->default(0);
            $table->decimal('on_time_delivery_rate', 5, 2)->default(0);
            // Quality metrics
            $table->unsignedInteger('total_items_received')->default(0);
            $table->unsignedInteger('items_accepted')->default(0);
            $table->unsignedInteger('items_rejected')->default(0);
            $table->decimal('quality_rate', 5, 2)->default(0);
            // Value metrics
            $table->decimal('total_order_value', 18, 4)->default(0);
            $table->decimal('total_paid', 18, 4)->default(0);
            $table->decimal('average_order_value', 18, 4)->default(0);
            // Response metrics
            $table->decimal('average_lead_time_days', 8, 2)->default(0);
            $table->decimal('average_response_time_hours', 8, 2)->default(0);
            // Ratings
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'year', 'month'], 'uq_supperf_sup_period');
            $table->index('branch_id', 'idx_supperf_branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_metrics');
        Schema::dropIfExists('low_stock_alerts');
        Schema::dropIfExists('anomaly_baselines');
        Schema::dropIfExists('alert_recipients');
        Schema::dropIfExists('alert_instances');
        Schema::dropIfExists('alert_rules');
    }
};
