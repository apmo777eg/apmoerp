<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add missing softDeletes and branch_id columns
 *
 * P1 FIXES from apmoerp76 audit report:
 * 1. SoftDeletes in BaseModel requires deleted_at column
 * 2. Multi-branch tables need branch_id for BranchScope
 *
 * Tables that extend BaseModel and are missing deleted_at:
 * - alert_instances, alert_recipients, anomaly_baselines
 * - branch_admins, dashboard_widgets, employee_shifts
 * - manufacturing_transactions, product_store_mappings
 * - rental_payments, search_history, search_index
 * - store_integrations, store_sync_logs, store_tokens
 * - user_dashboard_layouts, user_dashboard_widgets
 * - vehicle_payments, widget_data_cache, workflow_rules
 *
 * Tables that need branch_id for multi-branch filtering:
 * - deliveries, receipts
 */
return new class extends Migration
{
    /**
     * Tables that need softDeletes (deleted_at) column.
     */
    protected array $tablesNeedingSoftDeletes = [
        'alert_instances',
        'alert_recipients',
        'anomaly_baselines',
        'branch_admins',
        'dashboard_widgets',
        'employee_shifts',
        'manufacturing_transactions',
        'product_store_mappings',
        'rental_payments',
        'search_history',
        'search_index',
        'store_integrations',
        'store_sync_logs',
        'store_tokens',
        'user_dashboard_layouts',
        'user_dashboard_widgets',
        'vehicle_payments',
        'widget_data_cache',
        'workflow_rules',
    ];

    /**
     * Tables that need branch_id column for multi-branch support.
     */
    protected array $tablesNeedingBranchId = [
        'deliveries' => 'dlv',
        'receipts' => 'rcpt',
    ];

    public function up(): void
    {
        // Add deleted_at to tables missing SoftDeletes support
        foreach ($this->tablesNeedingSoftDeletes as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->softDeletes();
                });
            }
        }

        // Add branch_id to tables needing multi-branch support
        foreach ($this->tablesNeedingBranchId as $table => $shortName) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($shortName): void {
                    $blueprint->foreignId('branch_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('branches')
                        ->nullOnDelete()
                        ->name("fk_{$shortName}_branch__brnch");

                    $blueprint->index('branch_id', "idx_{$shortName}_branch_id");
                });
            }
        }
    }

    public function down(): void
    {
        // Remove branch_id from tables
        foreach ($this->tablesNeedingBranchId as $table => $shortName) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($shortName): void {
                    $blueprint->dropForeign("fk_{$shortName}_branch__brnch");
                    $blueprint->dropIndex("idx_{$shortName}_branch_id");
                    $blueprint->dropColumn('branch_id');
                });
            }
        }

        // Remove deleted_at from tables
        foreach ($this->tablesNeedingSoftDeletes as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
