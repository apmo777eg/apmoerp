<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PurchaseReceived;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Services\BranchContextManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UpdateStockOnPurchase implements ShouldQueue
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepo
    ) {}

    public function handle(PurchaseReceived $event): void
    {
        $purchase = $event->purchase;
        $warehouseId = $purchase->warehouse_id;

        if (! $warehouseId) {
            Log::warning('PurchaseReceived received without warehouse_id; skipping stock update', [
                'purchase_id' => $purchase->getKey(),
                'branch_id' => $purchase->branch_id,
            ]);

            return;
        }


        // CRIT-BRANCH-QUEUE-01 FIX: Ensure branch context exists in queued workers.
        $didSetBranchContext = false;
        if (app()->runningInConsole() && $purchase->branch_id) {
            BranchContextManager::setBranchContext((int) $purchase->branch_id);
            $didSetBranchContext = true;
        }

        try {

        foreach ($purchase->items as $item) {
            // Validate quantity is positive (use quantity column)
            $itemQty = decimal_float($item->quantity, 4);
            if ($itemQty <= 0) {
                Log::error('Invalid purchase quantity', [
                    'purchase_id' => $purchase->getKey(),
                    'product_id' => $item->product_id,
                    'qty' => $itemQty,
                ]);
                throw new InvalidArgumentException("Purchase quantity must be positive for product {$item->product_id}");
            }

            // STILL-V8-HIGH-U06 FIX: Use purchase_item as reference for uniqueness
            // This ensures each purchase line item gets exactly one stock movement,
            // even if multiple lines have the same product and quantity
            $purchaseItemId = $item->getKey();
            $existing = StockMovement::where('reference_type', 'purchase_item')
                ->where('reference_id', $purchaseItemId)
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseId)
                ->exists();

            if ($existing) {
                Log::info('Stock movement already recorded for purchase item', [
                    'purchase_id' => $purchase->getKey(),
                    'purchase_item_id' => $purchaseItemId,
                    'product_id' => $item->product_id,
                ]);

                continue;
            }

            // STILL-V8-HIGH-U06 FIX: Use purchase_item as reference_type for proper line-item uniqueness
            $this->stockMovementRepo->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $item->product_id,
                'movement_type' => 'purchase',
                'reference_type' => 'purchase_item',
                'reference_id' => $purchaseItemId,
                'qty' => $itemQty,
                'direction' => 'in',
                // stock_movements.unit_cost is NOT NULL (default 0.0000)
                'unit_cost' => decimal_float($item->unit_price ?? 0, 4),
                'notes' => sprintf('Purchase #%s received', $purchase->reference_number ?? $purchase->getKey()),
                'created_by' => $purchase->created_by,
            ]);
        }

        } finally {
            if ($didSetBranchContext) {
                BranchContextManager::clearBranchContext();
            }
        }
    }
}
