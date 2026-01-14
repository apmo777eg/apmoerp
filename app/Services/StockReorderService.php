<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * StockReorderService - Automated stock reorder management
 *
 * NEW FEATURE: Automated stock monitoring and reorder suggestions
 *
 * FEATURES:
 * - Identify products that need reordering
 * - Calculate optimal reorder quantities
 * - Generate purchase requisitions automatically
 * - Consider lead times and sales velocity
 * - Support for min/max inventory levels
 */
class StockReorderService
{
    /**
     * Get all products that need reordering.
     *
     * V9-CRITICAL-01 FIX: Use stock_movements as source of truth instead of stock_quantity
     */
    public function getProductsNeedingReorder(?int $branchId = null): Collection
    {
        $stockSubquery = \App\Services\StockService::getStockCalculationExpression('products.id');

        $query = Product::query()
            ->whereNotNull('reorder_point')
            ->whereRaw("({$stockSubquery}) <= reorder_point")
            ->where('status', 'active')
            ->where('type', '!=', 'service')
            ->with(['branch', 'module', 'unit']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Get products with low stock (above reorder point but below alert threshold).
     *
     * V9-CRITICAL-01 FIX: Use stock_movements as source of truth instead of stock_quantity
     */
    public function getLowStockProducts(?int $branchId = null): Collection
    {
        $stockSubquery = \App\Services\StockService::getStockCalculationExpression('products.id');

        $query = Product::query()
            ->whereNotNull('stock_alert_threshold')
            ->whereRaw("({$stockSubquery}) <= stock_alert_threshold")
            ->whereRaw("({$stockSubquery}) > COALESCE(reorder_point, 0)")
            ->where('status', 'active')
            ->where('type', '!=', 'service')
            ->with(['branch', 'module', 'unit']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Calculate optimal reorder quantity for a product.
     */
    public function calculateReorderQuantity(Product $product): float
    {
        // Use predefined reorder quantity if available
        if ($product->reorder_qty && $product->reorder_qty > 0) {
            return (float) $product->reorder_qty;
        }

        // Calculate based on sales velocity
        $salesVelocity = $this->calculateSalesVelocity($product->id, 30);

        if ($salesVelocity > 0) {
            // Order enough for 30 days plus buffer
            $optimalQty = $salesVelocity * 30 * 1.2; // 20% buffer

            // Respect minimum order quantity
            if ($product->minimum_order_quantity && $optimalQty < $product->minimum_order_quantity) {
                return (float) $product->minimum_order_quantity;
            }

            // Respect maximum order quantity
            if ($product->maximum_order_quantity && $optimalQty > $product->maximum_order_quantity) {
                return (float) $product->maximum_order_quantity;
            }

            // Use bcmath for precise quantity calculation
            return (float) bcdiv((string) $optimalQty, '1', 2);
        }

        // Fallback: reorder to bring stock to 2x reorder point
        return $product->reorder_point ? ((float) $product->reorder_point * 2) : 50;
    }

    /**
     * Calculate average daily sales velocity.
     */
    private function calculateSalesVelocity(int $productId, int $days = 30): float
    {
        $totalSold = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $productId)
            ->where('sales.status', '!=', 'cancelled')
            ->where('sales.created_at', '>=', now()->subDays($days))
            ->sum('sale_items.quantity');

        return $totalSold ? ((float) $totalSold / $days) : 0;
    }

    /**
     * Generate reorder suggestions with details.
     *
     * V9-CRITICAL-01 FIX: Use stock_movements as source of truth for stock values
     */
    public function generateReorderSuggestions(?int $branchId = null): array
    {
        $products = $this->getProductsNeedingReorder($branchId);

        return $products->map(function (Product $product) {
            $reorderQty = $this->calculateReorderQuantity($product);
            $salesVelocity = $this->calculateSalesVelocity($product->id);
            // V9-CRITICAL-01 FIX: Use stock_movements to get current stock
            $currentStock = \App\Services\StockService::getCurrentStock($product->id);
            $daysUntilStockout = $salesVelocity > 0
                ? ceil($currentStock / $salesVelocity)
                : null;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_code' => $product->code,
                'sku' => $product->sku,
                'current_stock' => $currentStock,
                'reserved_stock' => $product->reserved_quantity,
                'available_stock' => $product->getAvailableQuantity(),
                'reorder_point' => $product->reorder_point,
                'suggested_quantity' => $reorderQty,
                'estimated_cost' => $reorderQty * ($product->standard_cost ?? 0),
                'sales_velocity' => (float) bcdiv((string) $salesVelocity, '1', 2),
                'days_until_stockout' => $daysUntilStockout,
                'priority' => $this->calculatePriority($product, $daysUntilStockout),
                'branch_id' => $product->branch_id,
                'branch_name' => $product->branch->name ?? null,
            ];
        })->sortByDesc('priority')->values()->toArray();
    }

    /**
     * Calculate priority for reordering (1-5, 5 being highest).
     *
     * V9-CRITICAL-01 FIX: Use stock_movements as source of truth for stock levels
     */
    private function calculatePriority(Product $product, ?int $daysUntilStockout): int
    {
        // V9-CRITICAL-01 FIX: Use stock_movements to get current stock
        $currentStock = \App\Services\StockService::getCurrentStock($product->id);

        // Out of stock = highest priority
        if ($currentStock <= 0) {
            return 5;
        }

        // Stock running out soon
        if ($daysUntilStockout !== null) {
            if ($daysUntilStockout <= 3) {
                return 5;
            } elseif ($daysUntilStockout <= 7) {
                return 4;
            } elseif ($daysUntilStockout <= 14) {
                return 3;
            }
        }

        // Below reorder point but not critical
        if ($product->reorder_point && $currentStock <= $product->reorder_point) {
            return 2;
        }

        return 1;
    }

    /**
     * Auto-generate purchase requisitions for products needing reorder.
     */
    public function autoGenerateRequisitions(?int $branchId = null, ?int $userId = null): array
    {
        $suggestions = $this->generateReorderSuggestions($branchId);

        if (empty($suggestions)) {
            return ['success' => true, 'message' => 'No products need reordering', 'requisitions' => []];
        }

        $requisitionsByBranch = collect($suggestions)
            ->filter(fn ($s) => $s['priority'] >= 3) // Only auto-generate for priority 3+
            ->groupBy('branch_id');

        $createdRequisitions = [];

        DB::transaction(function () use ($requisitionsByBranch, $userId, &$createdRequisitions) {
            foreach ($requisitionsByBranch as $branchId => $items) {
                $requisition = PurchaseRequisition::create([
                    'code' => 'REQ-AUTO-'.strtoupper(uniqid()),
                    'branch_id' => $branchId,
                    'status' => 'pending',
                    'priority' => 'high',
                    'requisition_date' => now(),
                    'required_by_date' => now()->addDays(7),
                    'notes' => 'Auto-generated requisition for low stock items',
                    'created_by' => $userId,
                ]);

                foreach ($items as $item) {
                    PurchaseRequisitionItem::create([
                        'requisition_id' => $requisition->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['suggested_quantity'],
                        'estimated_price' => $item['estimated_cost'] / $item['suggested_quantity'],
                        'specifications' => "Current stock: {$item['current_stock']}, Reorder point: {$item['reorder_point']}",
                        'created_by' => $userId,
                    ]);
                }

                $createdRequisitions[] = [
                    'id' => $requisition->id,
                    'code' => $requisition->code,
                    'branch_id' => $branchId,
                    'items_count' => count($items),
                ];
            }
        });

        return [
            'success' => true,
            'message' => count($createdRequisitions).' requisitions created',
            'requisitions' => $createdRequisitions,
        ];
    }

    /**
     * Get reorder report statistics.
     */
    public function getReorderStatistics(?int $branchId = null): array
    {
        $needsReorder = $this->getProductsNeedingReorder($branchId)->count();
        $lowStock = $this->getLowStockProducts($branchId)->count();
        $outOfStock = Product::outOfStock()
            ->where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $suggestions = $this->generateReorderSuggestions($branchId);
        $totalEstimatedCost = collect($suggestions)->sum('estimated_cost');

        return [
            'products_needing_reorder' => $needsReorder,
            'products_low_stock' => $lowStock,
            'products_out_of_stock' => $outOfStock,
            'total_estimated_cost' => (float) bcdiv((string) $totalEstimatedCost, '1', 2),
            'high_priority_count' => collect($suggestions)->where('priority', '>=', 4)->count(),
        ];
    }
}
