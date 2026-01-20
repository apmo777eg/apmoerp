<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Inventory\BulkUpdateStockRequest;
use App\Http\Requests\Api\Inventory\GetMovementsRequest;
use App\Http\Requests\Api\Inventory\GetStockRequest;
use App\Http\Requests\Api\Inventory\UpdateStockRequest;
use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends BaseApiController
{
    /**
     * V32-MED-01 FIX: Tolerance for "set" mode to avoid micro stock movements
     * from float rounding drift (e.g., decimal:4 columns with float math).
     * Movements with difference below this threshold are treated as no-op.
     */
    private const STOCK_SET_TOLERANCE = 0.0001;

    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepo
    ) {}

    /**
     * Get stock levels for products.
     *
     * SECURITY NOTE: Raw SQL expressions in this method use:
     * - Parameter binding for warehouse_id (line with selectRaw('? as warehouse_id', [$warehouseId]))
     * - Hardcoded column comparisons in havingRaw (products.min_stock is a column, not user input)
     * No user input is directly interpolated into SQL.
     */
    public function getStock(GetStockRequest $request): JsonResponse
    {
        $store = $this->getStore($request);
        $validated = $request->validated();

        // NEW-HIGH-04 FIX: Move warehouse filter into the join constraint to preserve LEFT JOIN behavior
        // This ensures products with zero movements in the specified warehouse still appear with quantity 0
        $warehouseId = $validated['warehouse_id'] ?? null;

        // quantity is signed: positive = in, negative = out
        // V46-CRIT-02 FIX: Add soft-delete filter to exclude deleted stock_movements
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.min_stock',
                'products.branch_id',
                DB::raw('COALESCE(SUM(sm.quantity), 0) as current_quantity'),
            ])
            ->leftJoin('stock_movements as sm', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'sm.product_id')
                    // V46-CRIT-02 FIX: Exclude soft-deleted stock movements
                    ->whereNull('sm.deleted_at');
                // Apply warehouse filter inside the join to keep LEFT JOIN semantics
                if ($warehouseId !== null) {
                    $join->where('sm.warehouse_id', '=', $warehouseId);
                }
            })
            ->when($store?->branch_id, fn ($q) => $q->where('products.branch_id', $store->branch_id))
            ->when($request->filled('sku'), fn ($q) => $q->where('products.sku', $validated['sku']))
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.min_stock', 'products.branch_id');

        // Optionally include warehouse_id in select when filtered
        // A.6 FIX: Use parameter binding instead of string interpolation for SQL safety
        if ($warehouseId !== null) {
            $query->selectRaw('? as warehouse_id', [$warehouseId]);
        }

        // For low stock filter
        // SECURITY: This compares computed quantity against a table column (not user input)
        // V45-NEW-03 FIX: Use expression instead of alias in HAVING for PostgreSQL compatibility
        if ($request->boolean('low_stock')) {
            $query->havingRaw('COALESCE(SUM(sm.quantity), 0) <= products.min_stock');
        }

        $products = $query->paginate($validated['per_page'] ?? 100);

        return $this->paginatedResponse($products, __('Stock levels retrieved successfully'));
    }

    public function updateStock(UpdateStockRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $store = $this->getStore($request);

        $product = null;

        if ($request->filled('product_id')) {
            $product = Product::query()
                ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
                ->find($validated['product_id']);
        } elseif ($request->filled('external_id') && $store) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $validated['external_id'])
                ->first();

            if ($mapping) {
                $product = $mapping->product;
            }
        }

        if (! $product) {
            return $this->errorResponse(__('Product not found'), 404);
        }

        $warehouseId = $this->resolveWarehouseId(
            $request->input('warehouse_id'),
            $product->branch_id,
            $store?->branch_id
        );

        if ($warehouseId === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('No warehouse available for stock movement')],
            ]);
        }

        $oldQuantity = $this->calculateCurrentStock($product->id, $warehouseId, $product->branch_id);

        // Calculate new quantity and direction
        if ($validated['direction'] === 'set') {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $newQuantity = decimal_float($validated['qty']);
            $difference = $newQuantity - $oldQuantity;
            $actualDirection = $difference >= 0 ? 'in' : 'out';
            $actualQty = abs($difference);

            // V32-MED-01 FIX: Check tolerance to avoid micro stock movements from float rounding
            if ($actualQty < self::STOCK_SET_TOLERANCE) {
                return $this->successResponse([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $oldQuantity, // No change
                ], __('Stock unchanged (difference below threshold)'));
            }
        } else {
            $actualDirection = $validated['direction'];
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $actualQty = abs(decimal_float($validated['qty']));
            $newQuantity = $actualDirection === 'in'
                ? $oldQuantity + $actualQty
                : $oldQuantity - $actualQty;
        }

        $newQuantityPersisted = DB::transaction(function () use ($product, $actualDirection, $actualQty, $validated, $warehouseId) {
            if ($actualQty > 0) {
                // Use repository for proper schema mapping
                // V31-CRIT-01 FIX: The repository's create() method already updates products.stock_quantity
                // with the total stock across ALL warehouses via updateProductStockCache().
                // We no longer manually update stock_quantity here to avoid overwriting
                // the correct total with a warehouse-specific value.
                $this->stockMovementRepo->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'direction' => $actualDirection,
                    'qty' => $actualQty,
                    'movement_type' => 'api_sync',
                    'notes' => $validated['reason'] ?? 'API stock update',
                    'reference_type' => 'api_sync',
                ]);
            }

            // V31-CRIT-01 FIX: Return the warehouse-specific quantity for the API response
            // without storing it in products.stock_quantity. The stock_quantity field
            // represents total stock across ALL warehouses (managed by the repository).
            return $this->calculateCurrentStock($product->id, $warehouseId, $product->branch_id);
        });

        return $this->successResponse([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantityPersisted,
        ], __('Stock updated successfully'));
    }

    public function bulkUpdateStock(BulkUpdateStockRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $store = $this->getStore($request);
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($validated['updates'] as $item) {
            $product = null;

            if (isset($item['product_id'])) {
                $product = Product::query()
                    ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
                    ->find($item['product_id']);
            } elseif (isset($item['external_id']) && $store) {
                $mapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', $item['external_id'])
                    ->first();

                if ($mapping) {
                    $product = $mapping->product;
                }
            }

            if (! $product) {
                $results['failed'][] = [
                    'identifier' => $item['product_id'] ?? $item['external_id'],
                    'error' => __('Product not found'),
                ];

                continue;
            }

            try {
                $warehouseId = $this->resolveWarehouseId(
                    $item['warehouse_id'] ?? $request->input('warehouse_id'),
                    $product->branch_id,
                    $store?->branch_id
                );

                // If warehouse cannot be resolved, record failure and continue
                if ($warehouseId === null) {
                    $results['failed'][] = [
                        'identifier' => $item['product_id'] ?? $item['external_id'],
                        'error' => __('No warehouse available for stock movement'),
                    ];

                    continue;
                }

                // Get current quantity using helper method with warehouse and branch scoping
                $oldQuantity = $this->calculateCurrentStock($product->id, $warehouseId, $product->branch_id);

                // Calculate new quantity and direction
                if ($item['direction'] === 'set') {
                    // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                    $newQuantity = decimal_float($item['qty']);
                    $difference = $newQuantity - $oldQuantity;
                    $actualDirection = $difference >= 0 ? 'in' : 'out';
                    $actualQty = abs($difference);
                } else {
                    $actualDirection = $item['direction'];
                    // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                    $actualQty = abs(decimal_float($item['qty']));
                    $newQuantity = $actualDirection === 'in'
                        ? $oldQuantity + $actualQty
                        : $oldQuantity - $actualQty;
                }

                $persistedQuantity = DB::transaction(function () use ($product, $actualDirection, $actualQty, $warehouseId) {
                    if ($actualQty > 0) {
                        // Use repository for proper schema mapping
                        // V31-CRIT-01 FIX: The repository's create() method already updates products.stock_quantity
                        // with the total stock across ALL warehouses via updateProductStockCache().
                        // We no longer manually update stock_quantity here to avoid overwriting
                        // the correct total with a warehouse-specific value.
                        $this->stockMovementRepo->create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'direction' => $actualDirection,
                            'qty' => $actualQty,
                            'movement_type' => 'api_sync',
                            'notes' => 'API bulk stock update',
                            'reference_type' => 'api_sync',
                        ]);
                    }

                    // V31-CRIT-01 FIX: Return the warehouse-specific quantity for the API response
                    // without storing it in products.stock_quantity. The stock_quantity field
                    // represents total stock across ALL warehouses (managed by the repository).
                    return $this->calculateCurrentStock($product->id, $warehouseId, $product->branch_id);
                });

                $results['success'][] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $persistedQuantity,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'identifier' => $item['product_id'] ?? $item['external_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->successResponse($results, __('Bulk stock update completed'));
    }

    public function getMovements(GetMovementsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $store = $this->getStore($request);

        $query = StockMovement::query()
            ->with(['product:id,name,sku'])
            ->when($store?->branch_id, fn ($q) => $q->whereHas('warehouse', fn ($wq) => $wq->where('branch_id', $store->branch_id)))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $validated['product_id']))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $validated['warehouse_id']))
            // Filter by direction using signed quantity (positive = in, negative = out)
            ->when($request->filled('direction'), function ($q) use ($validated) {
                if ($validated['direction'] === 'in') {
                    $q->where('quantity', '>', 0);
                } elseif ($validated['direction'] === 'out') {
                    $q->where('quantity', '<', 0);
                }
            })
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $validated['start_date']))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $validated['end_date']))
            ->orderBy('created_at', 'desc');

        $movements = $query->paginate($validated['per_page'] ?? 50);

        return $this->paginatedResponse($movements, __('Stock movements retrieved successfully'));
    }

    /**
     * Calculate current stock quantity for a product
     *
     * @param  int  $productId  Product ID
     * @param  int|null  $warehouseId  Optional warehouse ID filter
     * @param  int|null  $branchId  Optional branch ID filter
     * @return float Current stock balance
     */
    protected function calculateCurrentStock(int $productId, ?int $warehouseId = null, ?int $branchId = null): float
    {
        $query = StockMovement::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($branchId !== null) {
            $query->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId));
        }

        // quantity is signed: positive = in, negative = out
        // SECURITY: Uses parameter binding for SUM calculation (no user input in SQL)
        return decimal_float($query->selectRaw('SUM(quantity) as balance')
            ->value('balance') ?? 0);
    }

    /**
     * Resolve warehouse ID with branch-safe fallback logic.
     * Priority: preferred ID → default setting → branch warehouse.
     *
     * @param  int|null  $preferredId  Preferred warehouse ID from request
     * @param  int|null  $branchId  Branch ID to filter warehouses
     * @param  int|null  $tokenBranchId  Branch ID from token/store context
     * @return int|null Resolved warehouse ID or null if none available
     */
    protected function resolveWarehouseId(?int $preferredId, ?int $branchId = null, ?int $tokenBranchId = null): ?int
    {
        $branchContext = $branchId ?? $tokenBranchId;

        if ($preferredId !== null) {
            $warehouse = Warehouse::query()
                ->where('id', $preferredId)
                ->when($branchContext, fn ($q, $branch) => $q->where('branch_id', $branch))
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => [__('Invalid warehouse for this branch')],
                ]);
            }

            return $warehouse->id;
        }

        // Try default warehouse from settings scoped to branch if provided
        $defaultWarehouseId = setting('default_warehouse_id');
        if ($defaultWarehouseId !== null) {
            $defaultWarehouse = Warehouse::query()
                ->where('id', $defaultWarehouseId)
                ->where('is_active', true)
                ->when($branchContext, fn ($q, $branch) => $q->where('branch_id', $branch))
                ->first();

            if ($defaultWarehouse) {
                return (int) $defaultWarehouse->id;
            }
        }

        // Try to get warehouse from branch context
        if ($branchContext !== null) {
            $branchWarehouse = \App\Models\Warehouse::where('branch_id', $branchContext)
                ->where('is_active', true)
                ->first();

            if ($branchWarehouse) {
                return $branchWarehouse->id;
            }

            // Do not fall back to a warehouse from another branch
            return null;
        }

        // Fall back to first available active warehouse when no branch context is provided
        $firstWarehouse = \App\Models\Warehouse::where('is_active', true)->first();

        return $firstWarehouse?->id;
    }
}
