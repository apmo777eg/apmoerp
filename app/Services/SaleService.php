<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReturnNote;
use App\Models\ReturnRefund;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\Contracts\SaleServiceInterface;
use App\Traits\HandlesServiceErrors;
use App\Traits\HasRequestContext;
use Illuminate\Support\Facades\DB;

class SaleService implements SaleServiceInterface
{
    use HandlesServiceErrors;
    use HasRequestContext;

    protected function branchIdOrFail(): int
    {
        $branchId = $this->currentBranchId();

        if ($branchId === null) {
            throw new \InvalidArgumentException('Branch context is required for sale operations.');
        }

        return $branchId;
    }

    protected function findBranchSaleOrFail(int $id): Sale
    {
        $branchId = $this->branchIdOrFail();

        return Sale::where('branch_id', $branchId)->findOrFail($id);
    }

    public function show(int $id): Sale
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->findBranchSaleOrFail($id)->load('items'),
            operation: 'show',
            context: ['sale_id' => $id]
        );
    }

    /** Return items (full/partial). Negative movement is handled in listeners. */
    public function handleReturn(int $saleId, array $items, ?string $reason = null): ReturnNote
    {
        return $this->handleServiceOperation(
            callback: function () use ($saleId, $items, $reason) {
                $sale = $this->findBranchSaleOrFail($saleId)->load('items');

                return DB::transaction(function () use ($sale, $items, $reason) {
                    // V22-HIGH-07 FIX: Calculate previously returned quantities per sale_item
                    // This prevents over-returning items across multiple return requests
                    $previouslyReturned = $this->getPreviouslyReturnedQuantities($sale->getKey());

                    // Calculate refund amount first
                    $refund = '0.00';
                    $processedItems = [];
                    // V25-CRIT-03 FIX: Track items for stock movements
                    $returnedItemsData = [];

                    foreach ($items as $it) {
                        // V22-HIGH-07 FIX: Support both sale_item_id and product_id for backwards compatibility
                        // sale_item_id is preferred as it correctly identifies the specific line item
                        $saleItemId = $it['sale_item_id'] ?? null;
                        $productId = $it['product_id'] ?? null;
                        $requestedQty = decimal_float($it['qty'] ?? 0);

                        // Validate required fields
                        if ((! $saleItemId && ! $productId) || $requestedQty <= 0) {
                            continue;
                        }

                        // Prevent negative quantity exploit in returns
                        if ($requestedQty <= 0) {
                            throw new \InvalidArgumentException(__('Return quantity must be positive. Received: :qty', ['qty' => $requestedQty]));
                        }

                        // V22-HIGH-07 FIX: Find sale item by sale_item_id if provided, else by product_id
                        $si = null;
                        if ($saleItemId) {
                            $si = $sale->items->firstWhere('id', $saleItemId);
                        } else {
                            // Legacy: find by product_id - but now we track which items were already processed
                            // to handle multiple items with the same product
                            $si = $sale->items
                                ->filter(fn ($item) => $item->product_id == $productId && ! in_array($item->id, $processedItems))
                                ->first();
                        }

                        if (! $si) {
                            continue;
                        }

                        // Mark this item as processed
                        $processedItems[] = $si->id;

                        // V22-HIGH-07 FIX: Calculate available quantity considering previous returns
                        $alreadyReturned = $previouslyReturned[$si->id] ?? 0.0;
                        $availableToReturn = max(0, decimal_float($si->quantity) - $alreadyReturned);

                        // Cap at available quantity
                        $qty = min($requestedQty, $availableToReturn);

                        // Skip if qty is zero or negative (additional safety check)
                        if ($qty <= 0) {
                            continue;
                        }

                        // Use bcmath for precise money calculation
                        $line = bcmul((string) $qty, (string) $si->unit_price, 2);
                        $refund = bcadd($refund, $line, 2);

                        // V25-CRIT-03 FIX: Track returned item data for stock movements
                        // V26-HIGH-03 FIX: Use cost_price instead of unit_price for unit_cost
                        // unit_price is the selling price, cost_price is the actual inventory cost
                        // Fall back to product's cost if sale item doesn't have cost_price recorded
                        $unitCost = $si->cost_price ?? $si->product?->cost ?? 0;
                        $returnedItemsData[] = [
                            'sale_item_id' => $si->id,
                            'product_id' => $si->product_id,
                            'qty' => $qty,
                            'unit_price' => $si->unit_price,
                            'unit_cost' => $unitCost,
                        ];
                    }

                    // V25-CRIT-03 FIX: Validate that at least one item is being returned
                    // Abort if no valid items were processed to prevent creating empty returns
                    if (empty($returnedItemsData)) {
                        throw new \InvalidArgumentException(__('No valid items to return. Please ensure items exist on the sale and have available quantities.'));
                    }

                    // V33-HIGH-03 FIX: Use retry mechanism with try-catch on duplicate key
                    // The exists() + create() pattern is still race-prone; catch duplicate key errors and retry
                    $referenceNumber = null;
                    $maxRetries = 10;
                    $today = today()->toDateString();
                    $note = null;

                    for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                        // Get the last note for today with lock
                        $lastNote = ReturnNote::whereDate('created_at', $today)
                            ->lockForUpdate()
                            ->orderBy('reference_number', 'desc')
                            ->first();

                        $seq = 1;
                        if ($lastNote && preg_match('/-(\d+)$/', $lastNote->reference_number, $m)) {
                            $seq = ((int) $m[1]) + 1;
                        }

                        // Add attempt number to handle race condition when no lastNote exists
                        $seq += $attempt;

                        $referenceNumber = 'RET-'.date('Ymd').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

                        try {
                            $note = ReturnNote::create([
                                'branch_id' => $sale->branch_id,
                                'sale_id' => $sale->getKey(),
                                'reference_number' => $referenceNumber,
                                'type' => 'sale_return',
                                'warehouse_id' => $sale->warehouse_id,
                                'customer_id' => $sale->customer_id,
                                'status' => 'pending',
                                'return_date' => now()->toDateString(),
                                'reason' => $reason,
                                'total_amount' => decimal_float($refund),
                                'restock_items' => true,
                            ]);
                            break; // Success, exit the retry loop
                        } catch (\Illuminate\Database\QueryException $e) {
                            // V33-HIGH-03 FIX: Check for duplicate key error using error codes and message
                            // MySQL: 1062, PostgreSQL: 23505, SQLite: 19/2067
                            $errorCode = (int) $e->getCode();
                            $isDuplicateKey = in_array($errorCode, [1062, 23505, 19, 2067], true)
                                || str_contains($e->getMessage(), 'Duplicate entry')
                                || str_contains($e->getMessage(), 'UNIQUE constraint failed');

                            if (! $isDuplicateKey || $attempt >= $maxRetries - 1) {
                                throw $e; // Re-throw if not a duplicate key error or max retries reached
                            }
                            // Continue to next attempt
                        }
                    }

                    // V25-CRIT-03 FIX: Create stock movements to track returned quantities
                    // This enables proper over-return protection and inventory accuracy
                    if ($sale->warehouse_id) {
                        $stockMovementRepo = app(\App\Repositories\Contracts\StockMovementRepositoryInterface::class);

                        foreach ($returnedItemsData as $itemData) {
                            // Create stock movement to add items back to inventory
                            // V26-HIGH-03 FIX: Use unit_cost (actual cost) instead of unit_price (selling price)
                            // This ensures inventory valuation and COGS reports are accurate
                            $stockMovementRepo->create([
                                'product_id' => $itemData['product_id'],
                                'warehouse_id' => $sale->warehouse_id,
                                'qty' => $itemData['qty'],
                                'direction' => 'in',
                                'movement_type' => 'return',
                                'reference_type' => 'sale_item_return',
                                'reference_id' => $itemData['sale_item_id'],
                                'notes' => "Sale return for Sale #{$sale->code}: {$reason}",
                                'unit_cost' => $itemData['unit_cost'],
                                // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution
                                'created_by' => actual_user_id(),
                            ]);
                        }
                    }

                    // STILL-V7-CRITICAL-U04 FIX: Create a RefundPayment record instead of directly mutating paid_amount
                    // This maintains proper audit trail and allows accurate financial reporting
                    if (bccomp($refund, '0.00', 2) > 0) {
                        $lastRefund = ReturnRefund::where('branch_id', $sale->branch_id)
                            ->lockForUpdate()
                            ->orderBy('id', 'desc')
                            ->first();

                        $refundSeq = $lastRefund ? ($lastRefund->id % 100000) + 1 : 1;
                        $refundRefNumber = 'REF-'.date('Ymd').'-'.str_pad((string) $refundSeq, 5, '0', STR_PAD_LEFT);

                        // V9-CRITICAL-02 FIX: Use return_note_id instead of sales_return_id
                        // ReturnNote is a different entity from SalesReturn
                        ReturnRefund::create([
                            'return_note_id' => $note->getKey(),  // V9-CRITICAL-02 FIX
                            'branch_id' => $sale->branch_id,
                            'refund_method' => ReturnRefund::METHOD_ORIGINAL,
                            'amount' => decimal_float($refund),
                            'currency' => $sale->currency ?? setting('general.default_currency', 'EGP'),
                            'reference_number' => $refundRefNumber,
                            'status' => ReturnRefund::STATUS_PENDING,
                            'notes' => "Refund for return #{$referenceNumber}: {$reason}",
                            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution
                            'created_by' => actual_user_id(),
                        ]);
                    }

                    // V33-HIGH-02 FIX: Properly track partial vs full returns
                    // Check if all items have been fully returned to determine correct status
                    $isFullyReturned = $this->isFullyReturned($sale, $returnedItemsData, $previouslyReturned);

                    if ($isFullyReturned) {
                        $sale->status = 'returned';
                    } else {
                        // Use 'partially_returned' for partial returns to maintain accurate reporting
                        $sale->status = 'partially_returned';
                    }

                    // V9-HIGH-03 FIX: Do NOT update paid_amount when refund is pending
                    // The paid_amount should only be updated when refund is actually completed
                    // This maintains accurate financial reporting and prevents incorrect balance calculations
                    // Note: paid_amount will be updated by the refund completion workflow
                    $sale->save();

                    $this->logServiceInfo('handleReturn', 'Sale return processed with refund record', [
                        'sale_id' => $sale->getKey(),
                        'return_note_id' => $note->getKey(),
                        'refund_amount' => $refund,
                        'items_returned' => count($returnedItemsData),
                    ]);

                    return $note;
                });
            },
            operation: 'handleReturn',
            context: ['sale_id' => $saleId, 'items_count' => count($items), 'reason' => $reason]
        );
    }

    /**
     * V22-HIGH-07 FIX: Get quantities already returned per sale_item_id
     * This is a simplified implementation - a full solution would track return items in a separate table
     */
    protected function getPreviouslyReturnedQuantities(int $saleId): array
    {
        // In a full implementation, this would query a return_note_items table
        // For now, we check stock movements with reference_type = 'return' and reference to sale items
        $saleItemIds = \App\Models\SaleItem::where('sale_id', $saleId)->pluck('id');

        $returned = [];
        foreach ($saleItemIds as $itemId) {
            // Check for return-related stock movements for this sale item
            // This is an approximation - a complete fix would require a return_items table
            // Use 'quantity' column (the actual column name in stock_movements table)
            // Returns add stock back, so we look for positive quantities (direction='in')
            $returnedQty = StockMovement::where('reference_type', 'sale_item_return')
                ->where('reference_id', $itemId)
                ->where('quantity', '>', 0)  // Positive quantity = stock added back
                ->sum('quantity');

            $returned[$itemId] = abs(decimal_float($returnedQty));
        }

        return $returned;
    }

    /**
     * V33-HIGH-02 FIX: Check if all sale items have been fully returned
     *
     * @param  Sale  $sale  The sale being returned
     * @param  array  $currentReturnItems  Items being returned in this transaction
     * @param  array  $previouslyReturned  Previously returned quantities keyed by sale_item_id
     * @return bool True if all items are fully returned after this return
     */
    protected function isFullyReturned(Sale $sale, array $currentReturnItems, array $previouslyReturned): bool
    {
        // Build a map of items being returned in this transaction
        $currentReturnMap = [];
        foreach ($currentReturnItems as $item) {
            $saleItemId = $item['sale_item_id'];
            $currentReturnMap[$saleItemId] = ($currentReturnMap[$saleItemId] ?? 0) + decimal_float($item['qty']);
        }

        // Check each sale item to see if it's fully returned
        foreach ($sale->items as $saleItem) {
            $soldQty = decimal_float($saleItem->quantity);
            $previouslyReturnedQty = $previouslyReturned[$saleItem->id] ?? 0;
            $currentlyReturningQty = $currentReturnMap[$saleItem->id] ?? 0;
            $totalReturnedQty = $previouslyReturnedQty + $currentlyReturningQty;

            // If any item has unreturned quantity, the sale is not fully returned
            // Use bcmath for precision comparison
            if (bccomp((string) $totalReturnedQty, (string) $soldQty, 4) < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * STILL-V7-HIGH-U07 FIX: Void sale with proper stock and accounting reversal
     */
    public function voidSale(int $saleId, ?string $reason = null): Sale
    {
        return $this->handleServiceOperation(
            callback: function () use ($saleId, $reason) {
                $sale = $this->findBranchSaleOrFail($saleId)->load('items');

                return DB::transaction(function () use ($sale, $reason) {
                    // Update status first
                    $sale->status = 'void';
                    $sale->notes = trim(($sale->notes ?? '')."\nVOID: ".$reason);
                    $sale->save();

                    // CRIT-04 FIX: Find stock movements by sale_item reference (matching UpdateStockOnSale listener)
                    // The UpdateStockOnSale listener creates movements with reference_type='sale_item' and reference_id=sale_item_id
                    $saleItemIds = $sale->items->pluck('id')->toArray();

                    $existingMovements = StockMovement::where('reference_type', 'sale_item')
                        ->whereIn('reference_id', $saleItemIds)
                        ->get();

                    // V26-HIGH-06 FIX: Use StockMovementRepository instead of StockMovement::create()
                    // This ensures proper stock_before/stock_after calculation, locking for concurrency,
                    // and consistent behavior with other stock operations
                    $stockMovementRepo = app(\App\Repositories\Contracts\StockMovementRepositoryInterface::class);

                    foreach ($existingMovements as $movement) {
                        // Check if reversal already exists for this specific sale_item movement
                        $reversalExists = StockMovement::where('reference_type', 'sale_item_void')
                            ->where('reference_id', $movement->reference_id)
                            ->where('product_id', $movement->product_id)
                            ->where('warehouse_id', $movement->warehouse_id)
                            ->exists();

                        if ($reversalExists) {
                            continue;
                        }

                        // V26-HIGH-06 FIX: Create reversal movement using repository
                        // The original sale movement quantity is negative (stock out), so we reverse it (stock in)
                        $stockMovementRepo->create([
                            'warehouse_id' => $movement->warehouse_id,
                            'product_id' => $movement->product_id,
                            'movement_type' => 'sale_void',
                            'reference_type' => 'sale_item_void',
                            'reference_id' => $movement->reference_id, // Keep same reference_id (sale_item_id) for traceability
                            'qty' => abs($movement->quantity), // Repository will handle direction
                            'direction' => 'in', // Void adds stock back
                            'unit_cost' => $movement->unit_cost,
                            'notes' => "Void reversal for Sale #{$sale->code}",
                            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution
                            'created_by' => actual_user_id(),
                        ]);
                    }

                    // STILL-V7-HIGH-U07 FIX: Reverse accounting entries if journal entry exists
                    if ($sale->journal_entry_id) {
                        try {
                            $accountingService = app(AccountingService::class);
                            $journalEntry = \App\Models\JournalEntry::find($sale->journal_entry_id);
                            // Check if journal entry exists, is posted, and is reversible (default to true if null)
                            // NEW-CRITICAL-01 FIX: Use nullsafe operator to prevent crash when journal entry is deleted/missing
                            $isReversible = $journalEntry?->is_reversible ?? true;
                            if ($journalEntry && $journalEntry->status === 'posted' && $isReversible) {
                                // V33-CRIT-02 FIX: Use actual_user_id() instead of auth()->id() ?? 1
                                // This ensures correct audit attribution during impersonation and avoids
                                // hardcoded fallback that corrupts audit history
                                $accountingService->reverseJournalEntry(
                                    $journalEntry,
                                    "Sale voided: {$reason}",
                                    actual_user_id()
                                );
                            }
                        } catch (\Exception $e) {
                            // Log but don't fail the void operation
                            $this->logServiceInfo('voidSale', 'Failed to reverse journal entry', [
                                'sale_id' => $sale->getKey(),
                                'journal_entry_id' => $sale->journal_entry_id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $this->logServiceInfo('voidSale', 'Sale voided with stock and accounting reversal', [
                        'sale_id' => $sale->getKey(),
                        'reason' => $reason,
                        'movements_reversed' => $existingMovements->count(),
                    ]);

                    return $sale;
                });
            },
            operation: 'voidSale',
            context: ['sale_id' => $saleId, 'reason' => $reason]
        );
    }

    /** Return array with printable path (PDF/HTML) using PrintingService */
    public function printInvoice(int $saleId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($saleId) {
                $sale = $this->findBranchSaleOrFail($saleId)->load('items');
                $printer = app(PrintingService::class);

                return $printer->renderPdfOrHtml('prints.sale', ['sale' => $sale], 'sale_'.$sale->id);
            },
            operation: 'printInvoice',
            context: ['sale_id' => $saleId],
            defaultValue: []
        );
    }
}
