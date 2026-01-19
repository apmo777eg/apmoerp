<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UpdateStockOnSale implements ShouldQueue
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepo
    ) {}

    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;
        $warehouseId = $sale->warehouse_id;

        foreach ($sale->items as $item) {
            // BUG FIX #2: Apply unit of measure conversion factor
            // Load the unit relation to get conversion factor
            $item->load('unit');
            $conversionFactor = $item->unit?->conversion_factor ?? 1.0;

            // V9-MEDIUM-05 FIX: Validate conversion factor is positive
            // A zero or negative factor would break stock calculations
            if ($conversionFactor <= 0) {
                Log::error('Invalid conversion factor for sale item', [
                    'sale_id' => $sale->getKey(),
                    'product_id' => $item->product_id,
                    'item_id' => $item->getKey(),
                    'conversion_factor' => $conversionFactor,
                    'unit_name' => $item->unit?->name,
                ]);

                throw new InvalidArgumentException(
                    "Invalid unit conversion factor ({$conversionFactor}) for product {$item->product_id}. " .
                    "Conversion factor must be greater than 0."
                );
            }
            
            // Calculate actual quantity to deduct in base units
            $baseQuantity = decimal_float($item->quantity) * decimal_float($conversionFactor);

            // Critical ERP Logic: Check for negative stock
            $allowNegativeStock = (bool) setting('inventory.allow_negative_stock', false);

            if (! $allowNegativeStock) {
                $currentStock = \App\Services\StockService::getCurrentStock(
                    $item->product_id,
                    $warehouseId
                );

                if ($currentStock < $baseQuantity) {
                    Log::warning('Insufficient stock for sale', [
                        'sale_id' => $sale->getKey(),
                        'product_id' => $item->product_id,
                        'requested_base_qty' => $baseQuantity,
                        'item_qty' => $item->quantity,
                        'conversion_factor' => $conversionFactor,
                        'available' => $currentStock,
                    ]);

                    throw new InvalidArgumentException(
                        "Insufficient stock for product {$item->product_id}. Available: {$currentStock}, Required: {$baseQuantity}"
                    );
                }
            }

            // STILL-V8-HIGH-U06 FIX: Use sale_item as reference for uniqueness
            // This ensures each sale line item gets exactly one stock movement,
            // even if multiple lines have the same product and quantity
            $saleItemId = $item->getKey();
            $existing = StockMovement::where('reference_type', 'sale_item')
                ->where('reference_id', $saleItemId)
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseId)
                ->exists();

            if ($existing) {
                Log::info('Stock movement already recorded for sale item', [
                    'sale_id' => $sale->getKey(),
                    'sale_item_id' => $saleItemId,
                    'product_id' => $item->product_id,
                ]);

                continue;
            }

            // STILL-V8-HIGH-U06 FIX: Use sale_item as reference_type for proper line-item uniqueness
            $this->stockMovementRepo->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $item->product_id,
                'movement_type' => 'sale',
                'reference_type' => 'sale_item',
                'reference_id' => $saleItemId,
                'qty' => abs($baseQuantity),
                'direction' => 'out',
                'notes' => sprintf('Sale #%s completed (UoM: %s, Factor: %s)', $sale->reference_number ?? $sale->getKey(), $item->unit?->name ?? 'base', $conversionFactor),
                'created_by' => $sale->created_by,
            ]);
        }
    }
}
