<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add branch_id to branch-aware tables
 *
 * CRITICAL FIX: Addresses CRITICAL-001 through CRITICAL-031 from apmoerp64_bug_report.md
 *
 * Models that extend BaseModel have HasBranch trait applied, which uses BranchScope
 * for multi-tenancy filtering. These tables need branch_id column for the scope to work.
 *
 * Classification:
 * - Standalone tables: Need branch_id for direct branch filtering
 * - Child item tables: Need branch_id for query efficiency (inherited from parent)
 *
 * Note: Some models (Branch, User, Module, etc.) are excluded from BranchScope.
 */
return new class extends Migration
{
    /**
     * Tables that need branch_id column.
     * Format: [table_name => [parent_table, parent_fk_column] or null for standalone]
     */
    protected array $tablesToUpdate = [
        // Standalone tables - directly branch-aware
        'stock_movements' => null,
        'leave_requests' => null,
        'dashboard_widgets' => null,
        'workflow_rules' => null,
        'ticket_sla_policies' => null,

        // Child item tables - inherit branch from parent for query efficiency
        'adjustment_items' => ['stock_adjustments', 'adjustment_id'],
        'bom_items' => ['bill_of_materials', 'bom_id'],
        'bom_operations' => ['bill_of_materials', 'bom_id'],
        'grn_items' => ['goods_received_notes', 'grn_id'],
        'production_order_items' => ['production_orders', 'production_order_id'],
        'production_order_operations' => ['production_orders', 'production_order_id'],
        'purchase_items' => ['purchases', 'purchase_id'],
        'purchase_requisition_items' => ['purchase_requisitions', 'requisition_id'],
        'sale_items' => ['sales', 'sale_id'],
        'supplier_quotation_items' => ['supplier_quotations', 'quotation_id'],
        'transfer_items' => ['stock_transfers', 'transfer_id'],
        'manufacturing_transactions' => ['production_orders', 'production_order_id'],

        // Additional tables that need branch_id based on model analysis
        'employee_shifts' => ['hr_employees', 'employee_id'],
        'vehicle_contracts' => ['vehicles', 'vehicle_id'],
        'vehicle_payments' => ['vehicle_contracts', 'contract_id'],
        'warranties' => null,
        'rental_invoices' => ['rental_contracts', 'contract_id'],
        'rental_periods' => null,
        'alert_recipients' => ['alert_instances', 'alert_instance_id'],
        'store_integrations' => ['stores', 'store_id'],
        'store_sync_logs' => ['stores', 'store_id'],
        'store_tokens' => ['stores', 'store_id'],
        'product_store_mappings' => ['products', 'product_id'],
        'user_dashboard_widgets' => null,
        'search_history' => null,

        // V65-BUG-FIX: Stock transfer related tables for branch scoping
        'inventory_transits' => ['stock_transfers', 'stock_transfer_id'],
        'stock_transfer_items' => ['stock_transfers', 'stock_transfer_id'],
        'stock_transfer_approvals' => ['stock_transfers', 'stock_transfer_id'],
        'stock_transfer_documents' => ['stock_transfers', 'stock_transfer_id'],
        'stock_transfer_history' => ['stock_transfers', 'stock_transfer_id'],

        // V65-BUG-FIX: Ticket related tables for branch scoping
        // NOTE: ticket_categories and ticket_priorities are intentionally global (V58-IDOR-01)
        'ticket_replies' => ['tickets', 'ticket_id'],

        // V65-BUG-FIX: Workflow related tables for branch scoping
        'workflow_approvals' => ['workflow_instances', 'workflow_instance_id'],
        'workflow_audit_logs' => null,
        'workflow_notifications' => null,

        // V65-BUG-FIX: Journal entry lines for branch scoping
        'journal_entry_lines' => ['journal_entries', 'journal_entry_id'],
    ];

    public function up(): void
    {
        foreach ($this->tablesToUpdate as $table => $parentInfo) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->foreignId('branch_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('branches')
                        ->nullOnDelete()
                        ->name("fk_{$this->shortenTableName($table)}_branch__brnch");

                    $blueprint->index('branch_id', "idx_{$this->shortenTableName($table)}_branch_id");
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tablesToUpdate) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropForeign("fk_{$this->shortenTableName($table)}_branch__brnch");
                    $blueprint->dropIndex("idx_{$this->shortenTableName($table)}_branch_id");
                    $blueprint->dropColumn('branch_id');
                });
            }
        }
    }

    /**
     * Shorten table name for constraint naming (MySQL 64 char limit)
     */
    protected function shortenTableName(string $table): string
    {
        $shortNames = [
            'adjustment_items' => 'adjitm',
            'bom_items' => 'bomitm',
            'bom_operations' => 'bomop',
            'grn_items' => 'grnitm',
            'production_order_items' => 'poitm',
            'production_order_operations' => 'poop',
            'purchase_items' => 'purchitm',
            'purchase_requisition_items' => 'prreqitm',
            'sale_items' => 'saleitm',
            'supplier_quotation_items' => 'sqitm',
            'transfer_items' => 'trfitm',
            'manufacturing_transactions' => 'mfgtx',
            'stock_movements' => 'stkmv',
            'leave_requests' => 'lvreq',
            'dashboard_widgets' => 'dashwdgt',
            'workflow_rules' => 'wfrule',
            'ticket_sla_policies' => 'tktslp',
            'employee_shifts' => 'empshf',
            'vehicle_contracts' => 'vehctr',
            'vehicle_payments' => 'vehpay',
            'warranties' => 'warr',
            'rental_invoices' => 'rntinv',
            'rental_periods' => 'rntprd',
            'alert_recipients' => 'altrcpt',
            'store_integrations' => 'strint',
            'store_sync_logs' => 'strsync',
            'store_tokens' => 'strtkn',
            'product_store_mappings' => 'prdstrmap',
            'user_dashboard_widgets' => 'usrdshwdgt',
            'search_history' => 'srchist',
            // V65-BUG-FIX: Stock transfer related tables
            'inventory_transits' => 'invtrs',
            'stock_transfer_items' => 'stktrfitm',
            'stock_transfer_approvals' => 'stktrfapr',
            'stock_transfer_documents' => 'stktrfdoc',
            'stock_transfer_history' => 'stktrfhst',
            // V65-BUG-FIX: Ticket related tables (categories/priorities are intentionally global)
            'ticket_replies' => 'tktrpl',
            // V65-BUG-FIX: Workflow related tables
            'workflow_approvals' => 'wflapr',
            'workflow_audit_logs' => 'wflaudit',
            'workflow_notifications' => 'wflnotif',
            // V65-BUG-FIX: Journal entry lines
            'journal_entry_lines' => 'jrnlln',
        ];

        // Throw exception for unmapped tables to ensure explicit naming
        if (!isset($shortNames[$table])) {
            throw new \RuntimeException("No short name mapping defined for table: {$table}. Add an explicit mapping to prevent constraint name collisions.");
        }

        return $shortNames[$table];
    }
};
