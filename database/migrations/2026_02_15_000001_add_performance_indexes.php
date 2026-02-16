<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes
 *
 * This migration adds a small set of composite indexes for the most common
 * dashboard + reporting queries (branch/date/status) and stock calculations.
 *
 * It is written defensively (checks tables/columns + try/catch) so it can be
 * applied safely across environments where some indexes may already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        // =========================
        // Sales / Purchases
        // =========================
        $this->safeIndex('sales', ['branch_id', 'sale_date'], 'idx_sales_branch_sale_date');
        $this->safeIndex('sales', ['branch_id', 'status'], 'idx_sales_branch_status');

        $this->safeIndex('purchases', ['branch_id', 'purchase_date'], 'idx_purchases_branch_purchase_date');
        $this->safeIndex('purchases', ['branch_id', 'status'], 'idx_purchases_branch_status');

        // =========================
        // Stock movements
        // =========================
        // Optimizes stock aggregation queries (product + warehouse + deleted_at)
        $this->safeIndex('stock_movements', ['product_id', 'warehouse_id', 'deleted_at'], 'idx_stkmov_prod_wh_deleted');
        $this->safeIndex('stock_movements', ['branch_id', 'product_id', 'deleted_at'], 'idx_stkmov_branch_prod_deleted');

        // =========================
        // Deliveries / Receipts
        // =========================
        $this->safeIndex('deliveries', ['branch_id', 'status'], 'idx_deliveries_branch_status');
        $this->safeIndex('receipts', ['branch_id', 'created_at'], 'idx_receipts_branch_created_at');
    }

    public function down(): void
    {
        $this->safeDropIndex('sales', 'idx_sales_branch_sale_date');
        $this->safeDropIndex('sales', 'idx_sales_branch_status');

        $this->safeDropIndex('purchases', 'idx_purchases_branch_purchase_date');
        $this->safeDropIndex('purchases', 'idx_purchases_branch_status');

        $this->safeDropIndex('stock_movements', 'idx_stkmov_prod_wh_deleted');
        $this->safeDropIndex('stock_movements', 'idx_stkmov_branch_prod_deleted');

        $this->safeDropIndex('deliveries', 'idx_deliveries_branch_status');
        $this->safeDropIndex('receipts', 'idx_receipts_branch_created_at');
    }

    private function safeIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                $blueprint->index($columns, $name);
            });
        } catch (Throwable $e) {
            // Ignore if already exists or the driver does not support it
        }
    }

    private function safeDropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropIndex($name);
            });
        } catch (Throwable $e) {
            // Ignore if already dropped / doesn't exist
        }
    }
};
