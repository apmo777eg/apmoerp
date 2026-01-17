<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * Critical ERP: Audit trail for price changes
 *
 * This observer logs all price changes for products to maintain
 * a complete audit trail required for financial compliance and
 * cost analysis in ERP systems.
 */
class PriceAuditObserver
{
    /**
     * Handle the Product "updating" event.
     */
    public function updating(Product $product): void
    {
        // Critical ERP: Log price changes for audit trail
        if ($product->isDirty('default_price') || $product->isDirty('cost')) {
            $oldPrice = $product->getOriginal('default_price');
            $newPrice = $product->default_price;
            $oldCost = $product->getOriginal('cost');
            $newCost = $product->cost;

            $changes = [];

            if ($product->isDirty('default_price')) {
                $changes['selling_price'] = [
                    'old' => $oldPrice,
                    'new' => $newPrice,
                    'change' => $newPrice - $oldPrice,
                    'change_percent' => $oldPrice > 0 ? (($newPrice - $oldPrice) / $oldPrice) * 100 : 0,
                ];
            }

            if ($product->isDirty('cost')) {
                $changes['cost'] = [
                    'old' => $oldCost,
                    'new' => $newCost,
                    'change' => $newCost - $oldCost,
                    'change_percent' => $oldCost > 0 ? (($newCost - $oldCost) / $oldCost) * 100 : 0,
                ];

                // Calculate margin change
                if ($product->default_price > 0) {
                    $oldMargin = $oldPrice > 0 ? (($oldPrice - $oldCost) / $oldPrice) * 100 : 0;
                    $newMargin = $newPrice > 0 ? (($newPrice - $newCost) / $newPrice) * 100 : 0;
                    $changes['margin_impact'] = [
                        'old_margin' => round($oldMargin, 2),
                        'new_margin' => round($newMargin, 2),
                        'margin_change' => round($newMargin - $oldMargin, 2),
                    ];
                }
            }

            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::info('Product price change detected', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'changes' => $changes,
                'user_id' => actual_user_id(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Critical ERP: Validate reorder logic
        if ($product->isDirty('reorder_point') || $product->isDirty('min_stock') || $product->isDirty('max_stock')) {
            $reorderPoint = $product->reorder_point ?? 0;
            $minStock = $product->min_stock ?? 0;
            $maxStock = $product->max_stock;

            // Validate reorder point is between min and max
            if ($reorderPoint < $minStock) {
                Log::warning('Reorder point is below minimum stock', [
                    'product_id' => $product->id,
                    'reorder_point' => $reorderPoint,
                    'min_stock' => $minStock,
                ]);
            }

            if ($maxStock && $reorderPoint > $maxStock) {
                Log::warning('Reorder point is above maximum stock', [
                    'product_id' => $product->id,
                    'reorder_point' => $reorderPoint,
                    'max_stock' => $maxStock,
                ]);
            }

            // Validate max stock is greater than min stock
            if ($maxStock && $maxStock < $minStock) {
                throw new \InvalidArgumentException(
                    "Maximum stock ({$maxStock}) cannot be less than minimum stock ({$minStock})"
                );
            }
        }
    }
}
