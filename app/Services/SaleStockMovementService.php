<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * SaleStockMovementService
 *
 * Centralizes the logic for creating stock movements for sales.
 *
 * Why this exists:
 * - Sale completion can happen from multiple entry points (POS, API orders, Livewire forms, store sync).
 * - Relying on queued listeners for inventory deduction is race-prone when negative stock is disallowed.
 * - We want one canonical rule: stock movements are recorded per sale_item (reference_type='sale_item').
 *
 * This service is intentionally conservative:
 * - It skips service products.
 * - It is idempotent (won't duplicate movements for the same sale_item).
 * - It throws DomainException from StockMovementRepository when stock would go negative.
 */
final class SaleStockMovementService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovements,
    ) {}

    /**
     * Create stock movements for all items of a sale.
     *
     * @throws \DomainException When negative stock is disallowed and stock is insufficient
     */
    public function createForSale(Sale $sale, ?int $createdBy = null): void
    {
        // A sale without warehouse cannot affect stock.
        if (! $sale->warehouse_id) {
            return;
        }

        // Ensure we have items and products.
        if (! $sale->relationLoaded('items')) {
            $sale->load(['items.product', 'items.unit']);
        }

        $warehouseId = (int) $sale->warehouse_id;

        foreach ($sale->items as $item) {
            $this->createForSaleItem($sale, $item, $warehouseId, $createdBy);
        }
    }

    /**
     * Create a stock movement for one sale item.
     *
     * Idempotent by (reference_type, reference_id, movement_type).
     */
    public function createForSaleItem(Sale $sale, SaleItem $item, int $warehouseId, ?int $createdBy = null): void
    {
        $product = $item->relationLoaded('product') ? $item->product : Product::find($item->product_id);
        if (! $product) {
            return;
        }

        // Service products do not impact inventory.
        if (($product->type ?? null) === 'service' || ($product->product_type ?? null) === 'service') {
            return;
        }

        // Idempotency: If a movement already exists for this sale item, do nothing.
        $alreadyExists = DB::table('stock_movements')
            ->whereNull('deleted_at')
            ->where('movement_type', 'sale')
            ->where('reference_type', 'sale_item')
            ->where('reference_id', $item->getKey())
            ->exists();

        if ($alreadyExists) {
            return;
        }

        // Unit conversion: if sale item has a unit with conversion_factor, apply it.
        $conversionFactor = 1.0;
        try {
            $unit = $item->relationLoaded('unit') ? $item->unit : null;
            if ($unit && isset($unit->conversion_factor)) {
                $conversionFactor = (float) $unit->conversion_factor;
            }
        } catch (\Throwable) {
            // Safe fallback
            $conversionFactor = 1.0;
        }

        // Stock quantities use scale 4.
        $baseQty = decimal_float($item->quantity ?? 0, 4) * decimal_float($conversionFactor, 4);

        if ($baseQty <= 0) {
            return;
        }

        // Prefer recorded cost on the sale item; fall back to product cost.
        $unitCost = $item->cost_price ?? ($product->cost ?? 0);

        $this->stockMovements->create([
            'product_id' => (int) $item->product_id,
            'warehouse_id' => $warehouseId,
            'qty' => abs($baseQty),
            'direction' => 'out',
            'movement_type' => 'sale',
            'reference_type' => 'sale_item',
            'reference_id' => $item->getKey(),
            'notes' => 'Sale #'.$sale->reference_number,
            'unit_cost' => $unitCost,
            'created_by' => $createdBy ?? actual_user_id(),
        ]);
    }
}
