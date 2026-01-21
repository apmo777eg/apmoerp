<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockMovementRepository extends EloquentBaseRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    protected function baseQuery(): Builder
    {
        return $this->query();
    }

    public function paginateForBranch(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        // Note: stock_movements table doesn't have branch_id - filter via warehouse
        $query = $this->baseQuery();

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Filter by branch through warehouse relationship
        $query->whereHas('warehouse', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function movementsForProduct(int $branchId, int $productId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $filters['product_id'] = $productId;

        return $this->paginateForBranch($branchId, $filters, $perPage);
    }

    public function summaryForProduct(int $branchId, int $productId): array
    {
        $baseQuery = $this->baseQuery()
            ->where('product_id', $productId)
            ->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId));

        // quantity > 0 = in, quantity < 0 = out
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $in = decimal_float((clone $baseQuery)->where('quantity', '>', 0)->sum('quantity'), 4);
        $out = decimal_float(abs((clone $baseQuery)->where('quantity', '<', 0)->sum('quantity')), 4);

        return [
            'in' => $in,
            'out' => $out,
            'net' => $in - $out,
        ];
    }

    public function currentStockForBranch(int $branchId, int $productId): float
    {
        $baseQuery = $this->baseQuery()
            ->where('product_id', $productId)
            ->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId));

        // Sum all quantities (positive = in, negative = out)
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return decimal_float($baseQuery->sum('quantity'), 4);
    }

    public function currentStockPerWarehouse(int $branchId, int $productId): Collection
    {
        $movements = $this->baseQuery()
            ->where('product_id', $productId)
            ->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId))
            ->get(['warehouse_id', 'quantity']);

        $map = $movements->groupBy('warehouse_id')
            ->map(function ($group) {
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                return decimal_float($group->sum('quantity'), 4);
            });

        return $map;
    }

    /**
     * Create a stock movement with proper column mapping
     * Uses pessimistic locking to prevent race conditions in high-concurrency scenarios
     *
     * V10-HIGH-02 FIX: Lock the warehouse row when no stock movements exist to ensure
     * proper serialization of concurrent writes for the first movement
     *
     * NEW-V14-MEDIUM-04 FIX: Fail fast if warehouse_id is invalid (row doesn't exist)
     *
     * V22-HIGH-08 FIX: Update product.stock_quantity cache after creating movement
     *
     * V32-CRIT-02 FIX: Validate warehouse belongs to same branch as product
     */
    public function create(array $data): StockMovement
    {
        // Use transaction with pessimistic locking to prevent race conditions
        return DB::transaction(function () use ($data) {
            // Map legacy field names to new schema
            $mappedData = [
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'movement_type' => $data['movement_type'] ?? $data['reason'] ?? 'adjustment',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? $data['reason'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ];

            // Handle quantity: direction 'out' should be negative
            // V49-CRIT-01 FIX: Use precision 4 to match decimal:4 schema for stock quantities
            $qty = abs(decimal_float($data['qty'] ?? $data['quantity'] ?? 0, 4));
            $direction = $data['direction'] ?? 'in';

            if ($direction === 'out') {
                $qty = -$qty;
            }
            $mappedData['quantity'] = $qty;

            // V10-HIGH-02 FIX: Lock the warehouse row first to provide a deterministic lock anchor
            // This ensures that even when no stock movements exist for this product+warehouse,
            // concurrent transactions will be serialized. The warehouse row always exists when
            // creating stock movements, so this lock is always effective.
            // NEW-V14-MEDIUM-04 FIX: Check if warehouse exists and throw immediately if not
            $warehouse = DB::table('warehouses')
                ->where('id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if ($warehouse === null) {
                throw new DomainException("Invalid warehouse_id: {$data['warehouse_id']}");
            }

            // V32-CRIT-02 FIX: Validate warehouse belongs to the same branch as the product
            // In a multi-branch ERP, allowing stock movements across branches corrupts
            // stock totals, aging reports, and audit trails.
            // V50-HIGH-11 FIX: Exclude soft-deleted products
            $product = DB::table('products')
                ->where('id', $data['product_id'])
                ->whereNull('deleted_at')
                ->first();

            if ($product === null) {
                throw new DomainException("Invalid product_id: {$data['product_id']}");
            }

            // Only validate branch match if product has a branch_id assigned (non-global product).
            // Products with branch_id = null are considered "global" products that can have
            // stock movements in any warehouse across all branches. This is an intentional
            // business rule for shared inventory items (e.g., central warehouse products).
            if ($product->branch_id !== null && $warehouse->branch_id !== $product->branch_id) {
                throw new DomainException(
                    "Branch mismatch: Product (branch_id: {$product->branch_id}) cannot have stock movement in warehouse (branch_id: {$warehouse->branch_id})"
                );
            }

            // Then also lock any existing stock movement rows for this product+warehouse
            // This provides additional safety for the case where movements already exist
            StockMovement::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            // Calculate current stock from all movements
            // V49-CRIT-01 FIX: Use precision 4 to match decimal:4 schema for stock quantities
            $currentStock = decimal_float(StockMovement::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->sum('quantity'), 4);

            $mappedData['stock_before'] = $currentStock;
            $mappedData['stock_after'] = decimal_float($currentStock + $qty, 4);
            $mappedData['unit_cost'] = $data['unit_cost'] ?? null;

            $movement = StockMovement::create($mappedData);

            // V22-HIGH-08 FIX: Update the product's stock_quantity cache
            // This keeps the denormalized stock_quantity field in sync with stock_movements
            // The cache is used for quick reads and low_stock alerts
            $this->updateProductStockCache($data['product_id']);

            return $movement;
        });
    }

    /**
     * V22-HIGH-08 FIX: Update the product's stock_quantity cache field
     * Calculates total stock across all warehouses from stock_movements
     *
     * Note: This includes stock from all warehouses for this product.
     * Branch-specific stock filtering is handled at query time using the BranchScope
     * and warehouse relationships. The cached value represents the global stock level
     * for reporting and low-stock alerts across the entire system.
     */
    protected function updateProductStockCache(int $productId): void
    {
        // Calculate total stock from all warehouses for this product
        // This represents the global stock level, not branch-specific
        // Note: The quantity column already accounts for direction:
        // - Positive values = stock added (in)
        // - Negative values = stock removed (out)
        // So sum(quantity) gives the correct net stock level
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $totalStock = decimal_float(StockMovement::where('product_id', $productId)
            ->sum('quantity'));

        // Update the product's stock_quantity field (cached/denormalized value)
        // V50-MED-02 FIX: Exclude soft-deleted products
        DB::table('products')
            ->where('id', $productId)
            ->whereNull('deleted_at')
            ->update([
                'stock_quantity' => $totalStock,
                'updated_at' => now(),
            ]);
    }
}
