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
                    // Calculate refund amount first
                    $refund = '0.00';
                    foreach ($items as $it) {
                        // Validate required fields
                        if (! isset($it['product_id']) || ! isset($it['qty'])) {
                            continue;
                        }

                        // Prevent negative quantity exploit in returns
                        $requestedQty = (float) $it['qty'];
                        if ($requestedQty <= 0) {
                            throw new \InvalidArgumentException(__('Return quantity must be positive. Received: :qty', ['qty' => $requestedQty]));
                        }

                        $si = $sale->items->firstWhere('product_id', $it['product_id']);
                        if (! $si) {
                            continue;
                        }
                        // Use quantity column (not qty) - cap at original sale quantity
                        $qty = min($requestedQty, (float) $si->quantity);

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

                    $sale->status = 'returned';
                    // V9-HIGH-03 FIX: Do NOT update paid_amount when refund is pending
                    // The paid_amount should only be updated when refund is actually completed
                    // This maintains accurate financial reporting and prevents incorrect balance calculations
                    // Note: paid_amount will be updated by the refund completion workflow
                    // OLD CODE (removed):
                    // $newPaidAmount = bcsub((string) $sale->paid_amount, $refund, 2);
                    // $sale->paid_amount = max(0.0, (float) $newPaidAmount);
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
