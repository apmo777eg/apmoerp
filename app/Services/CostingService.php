<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * CostingService - Inventory costing methods (FIFO, LIFO, Weighted Average, Standard)
 *
 * STATUS: ACTIVE - Production-ready inventory costing service
 * PURPOSE: Calculate inventory costs based on configurable costing methods
 * METHODS: Supports FIFO, LIFO, Weighted Average, and Standard costing
 * USAGE: Called by inventory/stock services for cost calculations
 *
 * This service is fully implemented and provides critical inventory valuation
 * functionality for the ERP system.
 *
 * SECURITY NOTE: All raw SQL expressions in this service use only hardcoded column names.
 * Parameters like $productId and $warehouseId are passed through where() with proper binding.
 * No user input is interpolated into the SQL expressions.
 */
class CostingService
{
    /**
     * Tolerance threshold for stock level comparisons.
     * Stock levels below this value are considered effectively zero.
     */
    public const STOCK_ZERO_TOLERANCE = 0.0001;

    /**
     * Calculate cost for stock movement based on product's costing method
     * Falls back to system-wide default costing method from settings
     */
    public function calculateCost(
        Product $product,
        int $warehouseId,
        float $quantity
    ): array {
        // Use product-specific method if set, otherwise use system default from settings
        $costMethod = $product->cost_method
            ?? strtolower(setting('inventory.costing_method', 'weighted_average'));

        return match ($costMethod) {
            'fifo', 'FIFO' => $this->calculateFifoCost($product->id, $warehouseId, $quantity),
            'lifo', 'LIFO' => $this->calculateLifoCost($product->id, $warehouseId, $quantity),
            'weighted_average', 'AVG' => $this->calculateWeightedAverageCost($product->id, $warehouseId, $quantity),
            'standard' => $this->calculateStandardCost($product, $quantity),
            default => $this->calculateWeightedAverageCost($product->id, $warehouseId, $quantity),
        };
    }

    /**
     * FIFO: First In, First Out
     * Uses the cost of the oldest batches first
     */
    protected function calculateFifoCost(int $productId, int $warehouseId, float $quantity): array
    {
        $batches = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->allocateCostFromBatches($batches, $quantity);
    }

    /**
     * LIFO: Last In, First Out
     * Uses the cost of the newest batches first
     */
    protected function calculateLifoCost(int $productId, int $warehouseId, float $quantity): array
    {
        $batches = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->allocateCostFromBatches($batches, $quantity);
    }

    /**
     * Weighted Average: Calculate average cost across all batches
     */
    protected function calculateWeightedAverageCost(int $productId, int $warehouseId, float $quantity): array
    {
        $result = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->selectRaw('SUM(quantity * unit_cost) as total_value, SUM(quantity) as total_quantity')
            ->first();

        $totalQuantity = (string) ($result->total_quantity ?? 0);
        $totalValue = (string) ($result->total_value ?? 0);

        if (bccomp($totalQuantity, '0', 4) <= 0) {
            return [
                'unit_cost' => 0.0,
                'total_cost' => 0.0,
                'batches_used' => [],
            ];
        }

        $avgCost = bcdiv($totalValue, $totalQuantity, 4);
        $totalCost = bcmul($avgCost, (string) $quantity, 4);

        return [
            'unit_cost' => (float) $avgCost,
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            'total_cost' => (float) bcround($totalCost, 2),
            'batches_used' => [],
        ];
    }

    /**
     * Standard Cost: Use the product's standard cost
     */
    protected function calculateStandardCost(Product $product, float $quantity): array
    {
        $unitCost = (float) $product->standard_cost;

        return [
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost * $quantity,
            'batches_used' => [],
        ];
    }

    /**
     * Allocate cost from batches based on order (FIFO/LIFO)
     */
    protected function allocateCostFromBatches($batches, float $quantityNeeded): array
    {
        $totalCost = '0';
        $remainingQty = (string) $quantityNeeded;
        $batchesUsed = [];

        foreach ($batches as $batch) {
            if (bccomp($remainingQty, '0', 4) <= 0) {
                break;
            }

            $batchQuantity = (string) $batch->quantity;
            $batchQty = bccomp($remainingQty, $batchQuantity, 4) < 0 ? $remainingQty : $batchQuantity;
            $batchCost = bcmul($batchQty, (string) $batch->unit_cost, 4);

            $totalCost = bcadd($totalCost, $batchCost, 4);
            $remainingQty = bcsub($remainingQty, $batchQty, 4);

            $batchesUsed[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'quantity' => (float) $batchQty,
                'unit_cost' => (float) $batch->unit_cost,
                // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
                'total_cost' => (float) bcround($batchCost, 2),
            ];
        }

        $unitCost = $quantityNeeded > 0 ? bcdiv($totalCost, (string) $quantityNeeded, 4) : '0';

        return [
            'unit_cost' => (float) $unitCost,
            // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
            'total_cost' => (float) bcround($totalCost, 2),
            'batches_used' => $batchesUsed,
        ];
    }

    /**
     * Update batch quantities after stock movement
     */
    public function consumeBatches(array $batchesUsed): void
    {
        DB::transaction(function () use ($batchesUsed) {
            foreach ($batchesUsed as $batchInfo) {
                $batch = InventoryBatch::lockForUpdate()->find($batchInfo['batch_id']);
                if ($batch) {
                    $newQuantity = $batch->quantity - $batchInfo['quantity'];
                    $batch->quantity = max(0, $newQuantity);

                    if ($batch->quantity <= 0) {
                        $batch->status = 'depleted';
                    }

                    $batch->save();
                }
            }
        });
    }

    /**
     * Create or update batch for incoming stock
     */
    public function addToBatch(
        int $productId,
        int $warehouseId,
        int $branchId,
        float $quantity,
        float $unitCost,
        ?string $batchNumber = null,
        ?array $batchData = []
    ): InventoryBatch {
        if (! $batchNumber) {
            $batchNumber = 'BATCH-'.date('Ymd').'-'.uniqid();
        }

        $batch = InventoryBatch::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'batch_number' => $batchNumber,
        ]);

        if ($batch->exists) {
            // Update existing batch - recalculate weighted average cost
            // Formula: new_avg_cost = (old_qty * old_cost + new_qty * new_cost) / (old_qty + new_qty)

            // NOTE: We cast to string for bcmath functions to ensure precise decimal calculations
            // BCMath operates on strings to avoid floating-point precision issues with money/inventory
            $oldQty = (string) $batch->quantity;
            $oldCost = (string) $batch->unit_cost;
            $newQty = (string) $quantity;
            $newCost = (string) $unitCost;

            // Calculate old total value
            $oldTotalValue = bcmul($oldQty, $oldCost, 4);

            // Calculate new addition value
            $newAdditionValue = bcmul($newQty, $newCost, 4);

            // Calculate combined total value
            $combinedValue = bcadd($oldTotalValue, $newAdditionValue, 4);

            // Calculate combined quantity
            $combinedQty = bcadd($oldQty, $newQty, 4);

            // Calculate weighted average cost
            $weightedAvgCost = bccomp($combinedQty, '0', 4) > 0
                ? bcdiv($combinedValue, $combinedQty, 4)
                : '0';

            $batch->quantity = (float) $combinedQty;
            $batch->unit_cost = (float) $weightedAvgCost;
        } else {
            // New batch
            $batch->fill(array_merge([
                'branch_id' => $branchId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'status' => 'active',
            ], $batchData));
        }

        $batch->save();

        return $batch;
    }

    /**
     * Get total inventory value including goods in transit.
     *
     * BUG FIX: Addresses the "ghost inventory" issue where goods in transit
     * between warehouses were not included in financial reports, causing
     * temporary drops in asset value during transfers.
     *
     * @param  int|null  $branchId  Branch ID to filter by
     * @param  int|null  $warehouseId  Warehouse ID to filter by (null for all)
     * @return array ['warehouse_value' => float, 'transit_value' => float, 'total_value' => float]
     */
    public function getTotalInventoryValue(?int $branchId = null, ?int $warehouseId = null): array
    {
        // Calculate warehouse inventory value
        $warehouseQuery = InventoryBatch::active();

        if ($branchId !== null) {
            $warehouseQuery->where('branch_id', $branchId);
        }

        if ($warehouseId !== null) {
            $warehouseQuery->where('warehouse_id', $warehouseId);
        }

        $warehouseStats = $warehouseQuery
            ->selectRaw('SUM(quantity * unit_cost) as total_value, SUM(quantity) as total_quantity')
            ->first();

        // V30-HIGH-02 FIX: Keep values as strings from DB to avoid float precision issues
        // The system uses decimal:4 widely, so use scale=4 for internal calculations
        $warehouseValue = (string) ($warehouseStats->total_value ?? '0');
        $warehouseQuantity = (string) ($warehouseStats->total_quantity ?? '0');

        // BUG FIX: Include inventory in transit
        $transitValue = '0';
        $transitQuantity = '0';

        // Check if InventoryTransit model exists (it may be in StockTransferService)
        if (class_exists(\App\Models\InventoryTransit::class)) {
            $transitQuery = \App\Models\InventoryTransit::where('status', 'in_transit');

            if ($branchId !== null) {
                // Include transit records where either from or to warehouse belongs to branch
                $transitQuery->where(function ($q) use ($branchId) {
                    $q->whereHas('fromWarehouse', function ($wq) use ($branchId) {
                        $wq->where('branch_id', $branchId);
                    })->orWhereHas('toWarehouse', function ($wq) use ($branchId) {
                        $wq->where('branch_id', $branchId);
                    });
                });
            }

            $transitStats = $transitQuery
                ->selectRaw('SUM(quantity * unit_cost) as total_value, SUM(quantity) as total_quantity')
                ->first();

            // V30-HIGH-02 FIX: Keep values as strings from DB
            $transitValue = (string) ($transitStats->total_value ?? '0');
            $transitQuantity = (string) ($transitStats->total_quantity ?? '0');
        }

        // V30-HIGH-02 FIX: Use scale=4 to match the project-wide decimal:4 standard
        $totalValue = bcadd($warehouseValue, $transitValue, 4);
        $totalQuantity = bcadd($warehouseQuantity, $transitQuantity, 4);

        return [
            'warehouse_value' => (float) $warehouseValue,
            'warehouse_quantity' => (float) $warehouseQuantity,
            'transit_value' => (float) $transitValue,
            'transit_quantity' => (float) $transitQuantity,
            'total_value' => (float) $totalValue,
            'total_quantity' => (float) $totalQuantity,
            'breakdown' => [
                'in_warehouses' => (float) $warehouseValue,
                'in_transit' => (float) $transitValue,
            ],
        ];
    }

    /**
     * Reset weighted average cost when stock reaches zero.
     *
     * BUG FIX: Prevents cost calculation errors when stock depletes and
     * is then replenished. The old average cost should not be carried
     * forward when there's no stock to average with.
     *
     * @param  int  $productId  Product ID
     * @param  int  $warehouseId  Warehouse ID
     */
    public function resetCostOnZeroStock(int $productId, int $warehouseId): void
    {
        // Check if current stock is zero or effectively zero
        $totalStock = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->sum('quantity');

        if ((float) $totalStock <= self::STOCK_ZERO_TOLERANCE) {
            // Mark all batches as depleted to prevent old costs from affecting new stock
            InventoryBatch::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', 'active')
                ->update([
                    'status' => 'depleted',
                    'quantity' => 0,
                ]);
        }
    }
}
