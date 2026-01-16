<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get current stock for a product from stock_movements table
     * Compatible with MySQL 8.4, PostgreSQL, and SQLite
     *
     * Migration schema uses signed `quantity` column:
     * - Positive values = stock in (purchases, returns, adjustments+)
     * - Negative values = stock out (sales, adjustments-)
     */
    public static function getCurrentStock(int $productId, ?int $warehouseId = null): float
    {
        $query = DB::table('stock_movements')
            ->where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        // quantity is signed: positive = in, negative = out
        // Simply sum all quantities to get current stock
        return (float) $query->selectRaw('COALESCE(SUM(quantity), 0) as stock')
            ->value('stock');
    }

    /**
     * Get current stock for multiple products
     * Returns array keyed by product_id
     */
    public static function getBulkCurrentStock(array $productIds, ?int $warehouseId = null): array
    {
        $query = DB::table('stock_movements')
            ->whereIn('product_id', $productIds);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        // quantity is signed: positive = in, negative = out
        $results = $query
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(quantity), 0) as stock')
            ->groupBy('product_id')
            ->get();

        return $results->pluck('stock', 'product_id')->toArray();
    }

    /**
     * Get current stock for multiple products filtered by branch
     * Aggregates stock_movements through warehouses.branch_id
     *
     * STILL-V14-CRITICAL-01 FIX: Add branch-scoped bulk stock calculation
     *
     * @param  array  $productIds  Array of product IDs
     * @param  int  $branchId  The branch ID to filter by
     * @return array Keyed by product_id
     */
    public static function getBulkCurrentStockForBranch(array $productIds, int $branchId): array
    {
        if (empty($productIds)) {
            return [];
        }

        $results = DB::table('stock_movements')
            ->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
            ->whereIn('stock_movements.product_id', $productIds)
            ->where('warehouses.branch_id', $branchId)
            ->select('stock_movements.product_id')
            ->selectRaw('COALESCE(SUM(stock_movements.quantity), 0) as stock')
            ->groupBy('stock_movements.product_id')
            ->get();

        return $results->pluck('stock', 'product_id')->toArray();
    }

    /**
     * Get stock value for a product from stock_movements table
     * Calculates value based on quantity * unit_cost
     */
    public static function getStockValue(int $productId, ?int $warehouseId = null): float
    {
        $query = DB::table('stock_movements')
            ->where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Calculate value: SUM(quantity * unit_cost)
        // COALESCE handles NULL unit_cost values (cross-database compatible)
        return (float) ($query->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')
            ->value('value') ?? 0);
    }

    /**
     * Get current stock for a product, automatically handling branch scoping
     * This is a convenience method that chooses the appropriate stock calculation
     * based on whether a branch ID is provided.
     *
     * V10-CRITICAL-01 FIX: Helper method to reduce code duplication across callers
     *
     * @param  int  $productId  The product ID
     * @param  int|null  $branchId  The branch ID (optional, uses global stock if null)
     * @return float The current stock level
     */
    public static function getStock(int $productId, ?int $branchId = null): float
    {
        if ($branchId !== null) {
            return self::getCurrentStockForBranch($productId, $branchId);
        }

        return self::getCurrentStock($productId);
    }

    /**
     * Get current stock for a product filtered by branch
     * Aggregates stock_movements through warehouses.branch_id
     *
     * V10-CRITICAL-01 FIX: Add branch-scoped stock calculation
     *
     * @param  int  $productId  The product ID
     * @param  int  $branchId  The branch ID to filter by
     * @return float The current stock level for the product in the branch
     */
    public static function getCurrentStockForBranch(int $productId, int $branchId): float
    {
        return (float) DB::table('stock_movements')
            ->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
            ->where('stock_movements.product_id', $productId)
            ->where('warehouses.branch_id', $branchId)
            ->sum('stock_movements.quantity');
    }

    /**
     * Get SQL expression for calculating current stock
     * Use this for SELECT queries that need to calculate stock on the fly
     *
     * @param  string  $productIdColumn  Table.column reference (e.g., 'products.id')
     *
     * @throws \InvalidArgumentException if column name contains invalid characters
     */
    public static function getStockCalculationExpression(string $productIdColumn = 'products.id'): string
    {
        // Validate column name to prevent SQL injection
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $productIdColumn)) {
            throw new \InvalidArgumentException('Invalid column name format');
        }

        // quantity is signed: positive = in, negative = out
        return "COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE stock_movements.product_id = {$productIdColumn}), 0)";
    }

    /**
     * Get SQL expression for calculating stock in a specific warehouse
     *
     * @param  string  $productIdColumn  Table.column reference (e.g., 'products.id')
     * @param  string  $warehouseIdColumn  Table.column reference (e.g., 'warehouses.id')
     *
     * @throws \InvalidArgumentException if column names contain invalid characters
     */
    public static function getWarehouseStockCalculationExpression(string $productIdColumn = 'products.id', string $warehouseIdColumn = 'warehouses.id'): string
    {
        // Validate column names to prevent SQL injection
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $productIdColumn)) {
            throw new \InvalidArgumentException('Invalid product column name format');
        }
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $warehouseIdColumn)) {
            throw new \InvalidArgumentException('Invalid warehouse column name format');
        }

        // quantity is signed: positive = in, negative = out
        return "COALESCE((SELECT SUM(quantity) FROM stock_movements WHERE stock_movements.product_id = {$productIdColumn} AND stock_movements.warehouse_id = {$warehouseIdColumn}), 0)";
    }

    /**
     * Get SQL expression for calculating stock scoped to a specific branch
     * Joins stock_movements through warehouses.branch_id
     *
     * V10-CRITICAL-01 FIX: Add branch-scoped stock calculation expression
     *
     * @param  string  $productIdColumn  Table.column reference (e.g., 'products.id')
     * @param  int|string  $branchIdValueOrColumn  Either an integer branch ID or a column reference (e.g., 'products.branch_id')
     *
     * @throws \InvalidArgumentException if column names contain invalid characters
     */
    public static function getBranchStockCalculationExpression(string $productIdColumn = 'products.id', int|string $branchIdValueOrColumn = 'products.branch_id'): string
    {
        // Validate product column name to prevent SQL injection
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $productIdColumn)) {
            throw new \InvalidArgumentException('Invalid product column name format');
        }

        // Handle branch ID - either a numeric value or a column reference
        if (is_int($branchIdValueOrColumn)) {
            // V27-SEC FIX: Validate and cast for defense in depth against SQL injection
            // Branch IDs should always be positive integers
            if ($branchIdValueOrColumn < 1) {
                throw new \InvalidArgumentException('Branch ID must be a positive integer');
            }
            $branchIdValue = (int) $branchIdValueOrColumn;
            $branchCondition = "w.branch_id = {$branchIdValue}";
        } else {
            // Validate branch column name to prevent SQL injection
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $branchIdValueOrColumn)) {
                throw new \InvalidArgumentException('Invalid branch column name format');
            }
            $branchCondition = "w.branch_id = {$branchIdValueOrColumn}";
        }

        // quantity is signed: positive = in, negative = out
        // Join through warehouses to filter by branch
        return "COALESCE((SELECT SUM(sm.quantity) FROM stock_movements sm INNER JOIN warehouses w ON sm.warehouse_id = w.id WHERE sm.product_id = {$productIdColumn} AND {$branchCondition}), 0)";
    }

    /**
     * Adjust stock for a product in a specific warehouse
     *
     * STILL-V7-HIGH-N07 FIX: Uses SELECT FOR UPDATE locking to prevent race conditions
     * V27-HIGH-02 FIX: Added unit_cost parameter to support inventory valuation
     * V27-MED-05 FIX: Added userId parameter to support CLI/queue contexts
     *
     * Creates a stock movement record with the specified quantity change.
     * Positive quantity adds stock, negative quantity removes stock.
     *
     * @param  int  $productId  The product ID
     * @param  int  $warehouseId  The warehouse ID (required for stock movements)
     * @param  float  $quantity  The quantity to adjust (positive = add, negative = remove)
     * @param  string  $type  The movement type (use StockMovement::TYPE_* constants)
     * @param  string|null  $reference  Reference description for the movement
     * @param  string|null  $notes  Additional notes for the movement
     * @param  int|null  $referenceId  Reference ID for polymorphic relation
     * @param  string|null  $referenceType  Reference type for polymorphic relation
     * @param  float|null  $unitCost  Unit cost for inventory valuation. Null is acceptable for non-valued movements
     *                                (e.g., service items, adjustments without cost tracking). Inventory valuation
     *                                reports should handle null unit_cost by excluding those records or using fallbacks.
     * @param  int|null  $userId  User ID for created_by field (V27-MED-05 FIX: supports CLI/queue contexts)
     * @return StockMovement The created stock movement record
     *
     * @throws \InvalidArgumentException If warehouseId is null
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        string $type,
        ?string $reference = null,
        ?string $notes = null,
        ?int $referenceId = null,
        ?string $referenceType = null,
        ?float $unitCost = null,
        ?int $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $reference, $notes, $referenceId, $referenceType, $unitCost, $userId) {
            // STILL-V7-HIGH-N07 FIX: Lock the rows for this product+warehouse combination
            // and calculate stock at database level for efficiency
            $stockBefore = (float) DB::table('stock_movements')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->sum('quantity');

            $stockAfter = $stockBefore + $quantity;

            // V27-MED-05 FIX: Use provided userId or fall back to auth()->id()
            // This allows CLI/queue contexts to specify a user ID explicitly
            $createdBy = $userId ?? auth()->id();

            // Create the stock movement record
            // V27-HIGH-02 FIX: Include unit_cost in the movement record
            $movement = StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes ?? $reference,
                'created_by' => $createdBy,
            ]);

            return $movement;
        });
    }
}
