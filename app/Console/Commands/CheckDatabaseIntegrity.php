<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database Integrity Check Command
 *
 * Validates database schema, indexes, foreign keys, and data integrity.
 *
 * SECURITY (V40-SQL-03): SQL Expression Safety - REVIEWED
 * ========================================================
 * V40-HIGH-14 FIX: Refactored to use explicit validation instead of raw SQL interpolation.
 *
 * 1. checkDuplicates(): Now uses whitelist-based filter types instead of raw SQL
 *    - Validates column names via regex pattern
 *    - Only accepts predefined filter types from DUPLICATE_CHECK_FILTERS constant
 *
 * 2. applyFixes(): Validates fix statements against strict regex pattern
 *    - Only allows ALTER TABLE ADD INDEX with validated identifiers
 *
 * 3. getTableIndexes(): Validates table names via regex pattern
 *
 * This command runs via artisan CLI and is not exposed to web requests.
 *
 * @security-reviewed V40 - SQL injection protection via whitelist and regex validation
 */
class CheckDatabaseIntegrity extends Command
{
    protected $signature = 'db:check-integrity {--fix : Attempt to fix issues automatically}';

    protected $description = 'Check database integrity including indexes, foreign keys, and data consistency';

    private array $issues = [];

    private array $warnings = [];

    private array $fixes = [];

    /**
     * V40-HIGH-14 FIX: Whitelist of allowed filter types for checkDuplicates
     * This ensures no arbitrary SQL can be passed to whereRaw.
     * Only filter types listed here are accepted by checkDuplicates().
     */
    private const ALLOWED_DUPLICATE_FILTERS = [
        'non_empty_string', // filter for non-null, non-empty string columns
    ];

    public function handle(): int
    {
        $this->info('Starting database integrity check...');
        $this->newLine();

        $this->checkTables();
        $this->checkIndexes();
        $this->checkForeignKeys();
        $this->checkDataIntegrity();

        $this->displayResults();

        if ($this->option('fix') && ! empty($this->fixes)) {
            $this->applyFixes();
        }

        return empty($this->issues) ? Command::SUCCESS : Command::FAILURE;
    }

    private function checkTables(): void
    {
        $this->info('🔍 Checking tables...');

        $requiredTables = [
            'users', 'branches', 'products', 'customers', 'suppliers',
            'sales', 'purchases', 'stock_movements', 'audit_logs',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->issues[] = "Missing required table: {$table}";
            }
        }

        $this->info('✓ Tables check completed');
    }

    private function checkIndexes(): void
    {
        $this->info('🔍 Checking indexes...');

        $indexChecks = [
            'sales' => ['customer_id', 'branch_id', 'sale_date', 'status'],
            'purchases' => ['supplier_id', 'branch_id', 'purchase_date', 'status'],
            'products' => ['branch_id', 'sku', 'status', 'category_id'],
            'stock_movements' => ['product_id', 'warehouse_id', 'created_at'],
            'customers' => ['branch_id', 'email', 'phone'],
            'suppliers' => ['branch_id', 'email'],
        ];

        foreach ($indexChecks as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $tableIndexes = $this->getTableIndexes($table);

            foreach ($columns as $column) {
                if (! $this->hasIndexOnColumn($tableIndexes, $column)) {
                    $this->warnings[] = "Missing index on {$table}.{$column}";
                    $this->fixes[] = "ALTER TABLE {$table} ADD INDEX idx_{$column} ({$column})";
                }
            }
        }

        $this->info('✓ Indexes check completed');
    }

    private function checkForeignKeys(): void
    {
        $this->info('🔍 Checking foreign keys...');

        $foreignKeyChecks = [
            'sales' => [
                'customer_id' => 'customers',
                'branch_id' => 'branches',
            ],
            'purchases' => [
                'supplier_id' => 'suppliers',
                'branch_id' => 'branches',
            ],
            'products' => [
                'branch_id' => 'branches',
                'category_id' => 'product_categories',
            ],
            'sale_items' => [
                'sale_id' => 'sales',
                'product_id' => 'products',
            ],
            'purchase_items' => [
                'purchase_id' => 'purchases',
                'product_id' => 'products',
            ],
        ];

        foreach ($foreignKeyChecks as $table => $foreignKeys) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($foreignKeys as $column => $referencedTable) {
                if (! Schema::hasTable($referencedTable)) {
                    continue;
                }

                $orphans = $this->findOrphanedRecords($table, $column, $referencedTable);

                if ($orphans > 0) {
                    $this->issues[] = "Found {$orphans} orphaned records in {$table}.{$column}";
                }
            }
        }

        $this->info('✓ Foreign keys check completed');
    }

    private function checkDataIntegrity(): void
    {
        $this->info('🔍 Checking data integrity...');

        // Check for duplicate emails in customers (filter non-empty values only)
        $this->checkDuplicates('customers', 'email', 'non_empty_string');

        // Check for duplicate SKUs in products (filter non-empty values only)
        $this->checkDuplicates('products', 'sku', 'non_empty_string');

        // STILL-V14-CRITICAL-01 FIX: Check for negative stock using stock_movements as source of truth
        // instead of products.stock_quantity (cached value)
        $this->checkNegativeStockFromMovements();

        // STILL-V14-CRITICAL-01 FIX: Check for stock inconsistency between
        // products.stock_quantity (cached) and stock_movements (source of truth)
        $this->checkStockConsistency();

        // Check for sales with no items
        // V46-MED-04 FIX: Filter soft-deleted sale_items in the join to correctly identify
        // sales that have no non-deleted items (soft-deleted items are treated as "removed")
        if (Schema::hasTable('sales') && Schema::hasTable('sale_items')) {
            $salesWithoutItems = DB::table('sales')
                ->leftJoin('sale_items', function ($join) {
                    $join->on('sales.id', '=', 'sale_items.sale_id')
                        ->whereNull('sale_items.deleted_at');
                })
                ->whereNull('sale_items.id')
                ->count();

            if ($salesWithoutItems > 0) {
                $this->issues[] = "Found {$salesWithoutItems} sales with no items";
            }
        }

        // Check for inconsistent totals
        $this->checkSaleTotals();

        // Check core seeded data (roles, branches, warehouses, currencies)
        $this->checkSeedData();

        // Branch isolation correctness: warn if branch_id is missing on branch-aware tables.
        // This can happen after adding branch_id columns to existing installs.
        $this->checkBranchIdBackfill();

        $this->info('✓ Data integrity check completed');
    }

    /**
     * Warn when branch-aware tables have NULL branch_id values.
     *
     * This is a common post-migration issue: once branch_id is introduced, old records
     * may remain NULL which then makes them invisible under BranchScope.
     */
    private function checkBranchIdBackfill(): void
    {
        $this->info('🔍 Checking branch_id backfill...');

        $tables = [
            // Frequently used as "source of truth" for inventory reports/history
            'stock_movements',
            // Sales/purchase items may get branch_id added later in the lifecycle
            'sale_items',
            'purchase_items',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            $query = DB::table($table)->whereNull('branch_id');

            // If the table is soft-deletable, ignore deleted rows.
            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $nullCount = (int) $query->count();

            if ($nullCount > 0) {
                $this->warnings[] = "{$table} has {$nullCount} record(s) with NULL branch_id. Run: php artisan db:check-integrity --fix (will backfill where possible).";
            }
        }

        $this->info('✓ branch_id backfill check completed');
    }

    /**
     * STILL-V14-CRITICAL-01 FIX: Check for negative stock using stock_movements table
     * (the single source of truth for inventory)
     * V46-MED-03 FIX: Exclude soft-deleted stock_movements to avoid false positives
     */
    private function checkNegativeStockFromMovements(): void
    {
        if (! Schema::hasTable('stock_movements') || ! Schema::hasTable('products')) {
            return;
        }

        // Count products with negative stock from stock_movements (source of truth)
        // V46-MED-03 FIX: Exclude soft-deleted movements
        $negativeStock = DB::table('stock_movements')
            ->select('product_id')
            ->whereNull('deleted_at')
            ->selectRaw('SUM(quantity) as total_stock')
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) < 0')
            ->count();

        if ($negativeStock > 0) {
            $this->warnings[] = "Found {$negativeStock} products with negative stock (from stock_movements)";
        }
    }

    /**
     * STILL-V14-CRITICAL-01 FIX: Check for stock inconsistency between cached value
     * (products.stock_quantity) and source of truth (stock_movements)
     * V46-MED-03 FIX: Exclude soft-deleted stock_movements to avoid false positives
     */
    private function checkStockConsistency(): void
    {
        if (! Schema::hasTable('stock_movements') || ! Schema::hasTable('products')) {
            return;
        }

        // Get products with stock_quantity that doesn't match stock_movements sum
        // Allow for small floating-point differences (0.0001)
        // V46-MED-03 FIX: Exclude soft-deleted stock_movements
        $inconsistentProducts = DB::table('products')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as calculated_stock FROM stock_movements WHERE deleted_at IS NULL GROUP BY product_id) as sm'), 'products.id', '=', 'sm.product_id')
            ->whereRaw('ABS(COALESCE(products.stock_quantity, 0) - COALESCE(sm.calculated_stock, 0)) > 0.0001')
            ->count();

        if ($inconsistentProducts > 0) {
            $this->warnings[] = "Found {$inconsistentProducts} products where stock_quantity doesn't match stock_movements (use StockService for accurate stock)";
        }
    }

    /**
     * Check for duplicate values in a column.
     *
     * V40-HIGH-14 FIX: Use whitelist-based filter instead of raw SQL parameter.
     * The $filterType parameter must be one of the keys in DUPLICATE_CHECK_FILTERS constant.
     * This prevents any possibility of SQL injection via this method.
     *
     * @param  string  $table  Table name to check
     * @param  string  $column  Column name to check for duplicates
     * @param  string  $filterType  Filter type from DUPLICATE_CHECK_FILTERS whitelist (e.g., 'non_empty_string')
     */
    private function checkDuplicates(string $table, string $column, string $filterType = ''): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        // V40-HIGH-14 FIX: Validate column name to prevent SQL injection
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            return;
        }

        $query = DB::table($table)
            ->select($column, DB::raw('COUNT(*) as count'))
            ->groupBy($column)
            ->having('count', '>', 1);

        // V40-HIGH-14 FIX: Apply filter based on whitelist - no raw SQL interpolation
        if ($filterType === 'non_empty_string') {
            $query->whereNotNull($column)->where($column, '!=', '');
        } elseif ($filterType !== '' && ! in_array($filterType, self::ALLOWED_DUPLICATE_FILTERS, true)) {
            // Unknown filter type - log warning for debugging
            $this->warnings[] = "Unknown filter type '{$filterType}' for duplicate check on {$table}.{$column}";
            return;
        }

        $duplicates = $query->get();

        if ($duplicates->isNotEmpty()) {
            $this->warnings[] = "Found {$duplicates->count()} duplicate {$column} values in {$table}";
        }
    }

    private function checkSaleTotals(): void
    {
        if (! Schema::hasTable('sales') || ! Schema::hasTable('sale_items')) {
            return;
        }

        $inconsistentSales = DB::table('sales')
            ->select('sales.id', 'sales.total_amount', DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as calculated_total'))
            ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->groupBy('sales.id', 'sales.total_amount')
            ->havingRaw('ABS(sales.total_amount - COALESCE(SUM(sale_items.quantity * sale_items.unit_price), 0)) > 0.01')
            ->count();

        if ($inconsistentSales > 0) {
            $this->warnings[] = "Found {$inconsistentSales} sales with inconsistent totals";
        }
    }

    /**
     * Get indexes for a table.
     *
     * V40-SEC: Table name is validated to prevent SQL injection.
     * Only table names matching alphanumeric/underscore pattern are accepted.
     */
    private function getTableIndexes(string $table): array
    {
        // V40-HIGH-14 FIX: Validate table name to prevent SQL injection
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            return [];
        }

        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");

            return array_map(function ($index) {
                return [
                    'name' => $index->Key_name,
                    'column' => $index->Column_name,
                ];
            }, $indexes);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if ($index['column'] === $column) {
                return true;
            }
        }

        return false;
    }

    private function findOrphanedRecords(string $table, string $column, string $referencedTable): int
    {
        try {
            return DB::table($table)
                ->leftJoin($referencedTable, "{$table}.{$column}", '=', "{$referencedTable}.id")
                ->whereNotNull("{$table}.{$column}")
                ->whereNull("{$referencedTable}.id")
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════');
        $this->info('📊 DATABASE INTEGRITY CHECK RESULTS');
        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();

        if (empty($this->issues) && empty($this->warnings)) {
            $this->info('✅ No issues found! Database integrity is good.');
        } else {
            if (! empty($this->issues)) {
                $this->error('❌ ISSUES FOUND:');
                foreach ($this->issues as $issue) {
                    $this->line("  • {$issue}");
                }
                $this->newLine();
            }

            if (! empty($this->warnings)) {
                $this->warn('⚠️  WARNINGS:');
                foreach ($this->warnings as $warning) {
                    $this->line("  • {$warning}");
                }
                $this->newLine();
            }

            if (! empty($this->fixes) && ! $this->option('fix')) {
                $this->info('💡 TIP: Run with --fix option to attempt automatic fixes');
            }
        }

        $this->line('═══════════════════════════════════════════════════════');
    }


    /**
     * Check essential seeded data so environments don't boot into "empty system" state.
     *
     * This doesn't try to mutate data. It only provides actionable warnings
     * (e.g., if permissions/roles/branches were not seeded).
     */
    private function checkSeedData(): void
    {
        $this->info('🔍 Checking core seeded data...');

        try {
            // Branches
            if (Schema::hasTable('branches')) {
                $branchesCount = DB::table('branches')->count();
                if ($branchesCount === 0) {
                    $this->warnings[] = 'No branches found. Run: php artisan db:seed (or BranchSeeder).';
                }

                // Warehouses should exist per branch in most ERP setups
                if ($branchesCount > 0 && Schema::hasTable('warehouses')) {
                    $branchesWithoutWh = DB::table('branches')
                        ->leftJoin('warehouses', 'branches.id', '=', 'warehouses.branch_id')
                        ->whereNull('warehouses.id')
                        ->count();

                    if ($branchesWithoutWh > 0) {
                        $this->warnings[] = "{$branchesWithoutWh} branch(es) have no warehouse. Run WarehouseSeeder or create warehouses.";
                    }
                }
            }

            // Currencies: ensure there is a base currency
            if (Schema::hasTable('currencies')) {
                $baseCount = DB::table('currencies')->where('is_base', true)->count();
                if ($baseCount === 0) {
                    $this->warnings[] = 'No base currency is set. Run CurrencySeeder and ensure one currency has is_base=1.';
                }
            }

            // Roles & permissions (Spatie)
            if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
                $rolesCount = DB::table('roles')->count();
                $permCount = DB::table('permissions')->count();

                if ($rolesCount === 0) {
                    $this->warnings[] = 'No roles found. Run RolesSeeder.';
                } else {
                    $hasSuperAdmin = DB::table('roles')->whereIn('name', ['Super Admin', 'super-admin'])->exists();
                    if (! $hasSuperAdmin) {
                        $this->warnings[] = 'Super Admin role is missing. Run RolesSeeder (expects "Super Admin").';
                    }
                }

                if ($permCount === 0) {
                    $this->warnings[] = 'No permissions found. Run PermissionsSeeder.';
                }
            }

            // Users: ensure at least one active user exists
            if (Schema::hasTable('users')) {
                $activeUsers = DB::table('users')->where('is_active', true)->count();
                if ($activeUsers === 0) {
                    $this->warnings[] = 'No active users found. Run UserSeeder or create an admin user.';
                }
            }

            // Modules (if present)
            if (Schema::hasTable('modules')) {
                $modulesCount = DB::table('modules')->count();
                if ($modulesCount === 0) {
                    $this->warnings[] = 'No modules found. Run ModuleSeeder.';
                }
            }
        } catch (\Throwable $e) {
            // Never crash the integrity check because of a seed-data check failure
            $this->warnings[] = 'Seed data check failed: '.$e->getMessage();
        }

        $this->info('✓ Core seeded data check completed');
    }

    /**
     * Apply auto-generated fixes for missing indexes.
     *
     * V40-HIGH-14 FIX: Validate the fix statement format before execution.
     * Only ALTER TABLE ADD INDEX statements with valid identifiers are executed.
     */
    private function applyFixes(): void
    {
        $this->newLine();
        $this->info('🔧 Attempting to fix issues...');

        // V40-HIGH-14 FIX: Pattern to validate ALTER TABLE ADD INDEX statements
        // Only allows alphanumeric/underscore identifiers for table, index, and column names
        $validFixPattern = '/^ALTER TABLE ([a-zA-Z_][a-zA-Z0-9_]*) ADD INDEX (idx_[a-zA-Z_][a-zA-Z0-9_]*) \(([a-zA-Z_][a-zA-Z0-9_]*)\)$/';

        $fixed = 0;
        foreach ($this->fixes as $fix) {
            // V40-HIGH-14 FIX: Validate fix statement matches expected pattern
            if (! preg_match($validFixPattern, $fix, $matches)) {
                $this->error("✗ Skipped (invalid format): {$fix}");
                continue;
            }

            try {
                DB::statement($fix);
                $fixed++;
                $this->info("✓ Applied: {$fix}");
            } catch (\Exception $e) {
                $this->error("✗ Failed: {$fix}");
                $this->error('  Error: '.$e->getMessage());
            }
        }

        $this->newLine();

        $this->info("Fixed {$fixed} out of ".count($this->fixes).' index issue(s)');

        // Apply safe, DB-agnostic data fixes (no raw SQL) when possible.
        $dataFixed = $this->applyBranchIdBackfills();
        if ($dataFixed > 0) {
            $this->info("✓ Backfilled branch_id for {$dataFixed} record(s)");
        } else {
            $this->info('✓ No branch_id backfill changes were needed');
        }
    }

    /**
     * Attempt to backfill branch_id values for common branch-aware tables.
     *
     * This intentionally uses query builder + chunking (no UPDATE JOIN raw SQL)
     * to remain compatible across database drivers.
     */
    private function applyBranchIdBackfills(): int
    {
        $updated = 0;

        try {
            // stock_movements.branch_id from warehouses.branch_id
            if (
                Schema::hasTable('stock_movements') &&
                Schema::hasTable('warehouses') &&
                Schema::hasColumn('stock_movements', 'branch_id')
            ) {
                $warehouseBranch = DB::table('warehouses')->pluck('branch_id', 'id')->toArray();

                if (! empty($warehouseBranch)) {
                    $q = DB::table('stock_movements')
                        ->select('id', 'warehouse_id')
                        ->whereNull('branch_id');

                    if (Schema::hasColumn('stock_movements', 'deleted_at')) {
                        $q->whereNull('deleted_at');
                    }

                    $q->orderBy('id')->chunkById(1000, function ($rows) use (&$updated, $warehouseBranch) {
                        $idsByBranch = [];
                        foreach ($rows as $row) {
                            $branchId = $warehouseBranch[$row->warehouse_id] ?? null;
                            if ($branchId) {
                                $idsByBranch[$branchId][] = $row->id;
                            }
                        }

                        foreach ($idsByBranch as $branchId => $ids) {
                            $affected = DB::table('stock_movements')
                                ->whereIn('id', $ids)
                                ->whereNull('branch_id')
                                ->update(['branch_id' => (int) $branchId]);
                            $updated += (int) $affected;
                        }
                    });
                }
            }

            // sale_items.branch_id from sales.branch_id
            if (
                Schema::hasTable('sale_items') &&
                Schema::hasTable('sales') &&
                Schema::hasColumn('sale_items', 'branch_id')
            ) {
                $q = DB::table('sale_items')
                    ->select('id', 'sale_id')
                    ->whereNull('branch_id');

                if (Schema::hasColumn('sale_items', 'deleted_at')) {
                    $q->whereNull('deleted_at');
                }

                $q->orderBy('id')->chunkById(1000, function ($rows) use (&$updated) {
                    $saleIds = collect($rows)->pluck('sale_id')->filter()->unique()->values()->all();
                    if (empty($saleIds)) {
                        return;
                    }

                    $saleBranch = DB::table('sales')->whereIn('id', $saleIds)->pluck('branch_id', 'id')->toArray();

                    $idsByBranch = [];
                    foreach ($rows as $row) {
                        $branchId = $saleBranch[$row->sale_id] ?? null;
                        if ($branchId) {
                            $idsByBranch[$branchId][] = $row->id;
                        }
                    }

                    foreach ($idsByBranch as $branchId => $ids) {
                        $affected = DB::table('sale_items')
                            ->whereIn('id', $ids)
                            ->whereNull('branch_id')
                            ->update(['branch_id' => (int) $branchId]);
                        $updated += (int) $affected;
                    }
                });
            }

            // purchase_items.branch_id from purchases.branch_id
            if (
                Schema::hasTable('purchase_items') &&
                Schema::hasTable('purchases') &&
                Schema::hasColumn('purchase_items', 'branch_id')
            ) {
                $q = DB::table('purchase_items')
                    ->select('id', 'purchase_id')
                    ->whereNull('branch_id');

                if (Schema::hasColumn('purchase_items', 'deleted_at')) {
                    $q->whereNull('deleted_at');
                }

                $q->orderBy('id')->chunkById(1000, function ($rows) use (&$updated) {
                    $purchaseIds = collect($rows)->pluck('purchase_id')->filter()->unique()->values()->all();
                    if (empty($purchaseIds)) {
                        return;
                    }

                    $purchaseBranch = DB::table('purchases')->whereIn('id', $purchaseIds)->pluck('branch_id', 'id')->toArray();

                    $idsByBranch = [];
                    foreach ($rows as $row) {
                        $branchId = $purchaseBranch[$row->purchase_id] ?? null;
                        if ($branchId) {
                            $idsByBranch[$branchId][] = $row->id;
                        }
                    }

                    foreach ($idsByBranch as $branchId => $ids) {
                        $affected = DB::table('purchase_items')
                            ->whereIn('id', $ids)
                            ->whereNull('branch_id')
                            ->update(['branch_id' => (int) $branchId]);
                        $updated += (int) $affected;
                    }
                });
            }
        } catch (\Throwable $e) {
            // Never crash a maintenance command; report as warning.
            $this->warnings[] = 'Branch backfill failed: '.$e->getMessage();
        }

        return $updated;
    }
}
