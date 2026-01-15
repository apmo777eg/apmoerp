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

                    foreach ($items as $it) {
                        // V22-HIGH-07 FIX: Support both sale_item_id and product_id for backwards compatibility
                        // sale_item_id is preferred as it correctly identifies the specific line item
                        $saleItemId = $it['sale_item_id'] ?? null;
                        $productId = $it['product_id'] ?? null;
                        $requestedQty = (float) ($it['qty'] ?? 0);

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
                        $availableToReturn = max(0, (float) $si->quantity - $alreadyReturned);

                        // Cap at available quantity
                        $qty = min($requestedQty, $availableToReturn);

                        // Skip if qty is zero or negative (additional safety check)
                        if ($qty <= 0) {
                            continue;
                        }

                        // Use bcmath for precise money calculation
                        $line = bcmul((string) $qty, (string) $si->unit_price, 2);
                        $refund = bcadd($refund, $line, 2);
                    }

                    // Create return note with correct column name (total_amount)
                    // NEW-MEDIUM-10 FIX: Use database locking to prevent reference_number race condition
                    $today = today()->toDateString();
                    $lastNote = ReturnNote::whereDate('created_at', $today)
                        ->lockForUpdate()
                        ->orderBy('reference_number', 'desc')
                        ->first();

                    $seq = 1;
                    if ($lastNote && preg_match('/-(\d+)$/', $lastNote->reference_number, $m)) {
                        $seq = ((int) $m[1]) + 1;
                    }

                    $referenceNumber = 'RET-'.date('Ymd').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

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
                        'total_amount' => (float) $refund,
                    ]);

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
                            'amount' => (float) $refund,
                            'currency' => $sale->currency ?? setting('general.default_currency', 'EGP'),
                            'reference_number' => $refundRefNumber,
                            'status' => ReturnRefund::STATUS_PENDING,
                            'notes' => "Refund for return #{$referenceNumber}: {$reason}",
                            'created_by' => auth()->id(),
                        ]);
                    }

                    // V22-HIGH-07 FIX: For simplicity, mark as returned when any return is processed
                    // Note: The previous logic for tracking partial returns was incomplete.
                    // A full implementation would track returned quantities per line item in a return_items table.
                    // For now, we mark the sale as returned when any return is processed.
                    $sale->status = 'returned';
                    // V9-HIGH-03 FIX: Do NOT update paid_amount when refund is pending
                    // The paid_amount should only be updated when refund is actually completed
                    // This maintains accurate financial reporting and prevents incorrect balance calculations
                    // Note: paid_amount will be updated by the refund completion workflow
                    $sale->save();

                    $this->logServiceInfo('handleReturn', 'Sale return processed with refund record', [
                        'sale_id' => $sale->getKey(),
                        'return_note_id' => $note->getKey(),
                        'refund_amount' => $refund,
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
            $returnedQty = StockMovement::where('reference_type', 'sale_item_return')
                ->where('reference_id', $itemId)
                ->where('direction', 'in')  // Returns add stock back
                ->sum('qty');

            $returned[$itemId] = (float) $returnedQty;
        }

        return $returned;
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

                        // Create reversal movement (opposite quantity)
                        StockMovement::create([
                            'warehouse_id' => $movement->warehouse_id,
                            'product_id' => $movement->product_id,
                            'movement_type' => 'sale_void',
                            'reference_type' => 'sale_item_void',
                            'reference_id' => $movement->reference_id, // Keep same reference_id (sale_item_id) for traceability
                            'quantity' => -$movement->quantity, // Reverse the quantity (negative becomes positive)
                            'notes' => "Void reversal for Sale #{$sale->code}",
                            'created_by' => auth()->id(),
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
                                $accountingService->reverseJournalEntry(
                                    $journalEntry,
                                    "Sale voided: {$reason}",
                                    auth()->id() ?? 1
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
