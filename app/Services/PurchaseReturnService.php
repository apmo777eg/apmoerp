<?php

namespace App\Services;

use App\Models\DebitNote;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SupplierPerformanceMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service class for managing purchase returns and supplier accountability.
 *
 * Handles the complete workflow of returning items to suppliers including:
 * - Creating and managing purchase returns
 * - Quality control and inspection
 * - Debit note generation
 * - Supplier performance tracking
 * - Inventory adjustments
 */
class PurchaseReturnService
{
    /**
     * Create a new purchase return with validation
     *
     * @param  array  $data  Purchase return data including items
     * @return PurchaseReturn Created purchase return
     *
     * @throws \Exception If validation fails
     */
    public function createReturn(array $data): PurchaseReturn
    {
        // Input validation
        $validated = validator($data, [
            'purchase_id' => 'required|integer|exists:purchases,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'grn_id' => 'nullable|integer|exists:goods_received_notes,id',
            'return_type' => 'nullable|in:full,partial,defective,excess',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'return_date' => 'nullable|date',
            'tracking_number' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            // V24-CRIT-04 FIX: Add required validation for purchase_item_id
            'items.*.purchase_item_id' => 'required|integer|exists:purchase_items,id',
            'items.*.qty_returned' => 'required|numeric|min:0.001',
            'items.*.condition' => 'nullable|in:defective,damaged,wrong_item,excess,expired',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string',
        ])->validate();

        return DB::transaction(function () use ($validated) {
            // Validate purchase exists and load its items
            $purchase = Purchase::with('items')->findOrFail($validated['purchase_id']);

            // V25-HIGH-07 FIX: Build an indexed map for efficient lookup
            $purchaseItemsById = $purchase->items->keyBy('id');

            // Create purchase return
            $return = PurchaseReturn::create([
                'purchase_id' => $validated['purchase_id'],
                'supplier_id' => $validated['supplier_id'] ?? $purchase->supplier_id,
                'branch_id' => $validated['branch_id'] ?? $purchase->branch_id,
                'warehouse_id' => $validated['warehouse_id'] ?? $purchase->warehouse_id,
                'return_type' => $validated['return_type'] ?? PurchaseReturn::TYPE_FULL,
                'reason' => $validated['reason'],
                'status' => PurchaseReturn::STATUS_PENDING,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
                'expected_debit_note_amount' => 0,
            ]);

            // Add return items
            $totalAmount = 0;
            foreach ($validated['items'] as $itemData) {
                // V25-HIGH-07 FIX: Validate purchase_item belongs to the purchase (using efficient lookup)
                $purchaseItem = $purchaseItemsById->get($itemData['purchase_item_id']);
                if (! $purchaseItem) {
                    throw new \InvalidArgumentException(
                        "Purchase item ID {$itemData['purchase_item_id']} does not belong to purchase ID {$validated['purchase_id']}"
                    );
                }

                // V25-HIGH-07 FIX: Validate product_id matches the purchase item's product
                if ($purchaseItem->product_id != $itemData['product_id']) {
                    throw new \InvalidArgumentException(
                        "Product ID {$itemData['product_id']} does not match purchase item's product ID {$purchaseItem->product_id}"
                    );
                }

                // V25-HIGH-07 FIX: Validate qty_returned does not exceed purchase item quantity
                $qtyReturned = (float) $itemData['qty_returned'];
                $purchaseQty = (float) $purchaseItem->quantity;
                if ($qtyReturned > $purchaseQty) {
                    throw new \InvalidArgumentException(
                        "Return quantity ({$qtyReturned}) exceeds purchase quantity ({$purchaseQty}) for product ID {$itemData['product_id']}"
                    );
                }

                // V24-CRIT-04 FIX: Use null coalescing for nullable fields to prevent undefined index
                // V25-HIGH-07 FIX: Default unit_cost from purchase item if not provided
                $unitCost = $itemData['unit_cost'] ?? $purchaseItem->unit_price ?? 0;
                $condition = $itemData['condition'] ?? null;

                $item = PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $itemData['purchase_item_id'],
                    'product_id' => $itemData['product_id'],
                    'qty_returned' => $qtyReturned,
                    'qty_original' => $purchaseQty, // V25-HIGH-07 FIX: Track original qty
                    'unit_cost' => $unitCost,
                    'condition' => $condition,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $totalAmount += $item->qty_returned * $unitCost;
            }

            // Update expected debit note amount
            $return->update([
                'expected_debit_note_amount' => $totalAmount,
            ]);

            return $return->fresh('items');
        });
    }

    /**
     * Approve a purchase return and create debit note
     *
     * @param  int  $returnId  Purchase return ID
     * @param  array  $data  Additional approval data
     * @return PurchaseReturn Approved purchase return
     */
    public function approveReturn(int $returnId, array $data = []): PurchaseReturn
    {
        return DB::transaction(function () use ($returnId, $data) {
            $return = PurchaseReturn::with(['items', 'supplier'])->findOrFail($returnId);

            if (! $return->canBeApproved()) {
                throw new \Exception('Purchase return cannot be approved in current status');
            }

            // Update status
            $return->update([
                'status' => PurchaseReturn::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Create debit note if amount is greater than zero
            if ($return->expected_debit_note_amount > 0) {
                $debitNote = $this->createDebitNote($return, $data);
                $return->update(['debit_note_id' => $debitNote->id]);
            }

            // Update supplier performance metrics
            // V24-HIGH-07 FIX: Pass branch_id from the return
            $this->updateSupplierPerformance($return->supplier_id, 'return', $return->branch_id);

            return $return->fresh(['items', 'debitNote']);
        });
    }

    /**
     * Complete a purchase return (items shipped back to supplier)
     *
     * @param  int  $returnId  Purchase return ID
     * @param  array  $data  Shipping data (tracking number, carrier, etc.)
     * @return PurchaseReturn Completed purchase return
     */
    public function completeReturn(int $returnId, array $data = []): PurchaseReturn
    {
        return DB::transaction(function () use ($returnId, $data) {
            $return = PurchaseReturn::findOrFail($returnId);

            if (! $return->canBeCompleted()) {
                throw new \Exception('Purchase return cannot be completed in current status');
            }

            // Update return with shipping details
            $return->update([
                'status' => PurchaseReturn::STATUS_COMPLETED,
                'completed_by' => Auth::id(),
                'completed_at' => now(),
                'tracking_number' => $data['tracking_number'] ?? null,
                'carrier' => $data['carrier'] ?? null,
                'metadata' => array_merge($return->metadata ?? [], [
                    'shipping_details' => $data,
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);

            // Adjust inventory for returned items
            $this->adjustInventoryForReturn($return);

            return $return->fresh();
        });
    }

    /**
     * Reject a purchase return
     *
     * @param  int  $returnId  Purchase return ID
     * @param  string  $reason  Rejection reason
     * @return PurchaseReturn Rejected purchase return
     */
    public function rejectReturn(int $returnId, string $reason): PurchaseReturn
    {
        return DB::transaction(function () use ($returnId, $reason) {
            $return = PurchaseReturn::findOrFail($returnId);

            if (! $return->canBeRejected()) {
                throw new \Exception('Purchase return cannot be rejected in current status');
            }

            $return->update([
                'status' => PurchaseReturn::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $return->fresh();
        });
    }

    /**
     * Cancel a purchase return
     *
     * @param  int  $returnId  Purchase return ID
     * @param  string  $reason  Cancellation reason
     * @return PurchaseReturn Cancelled purchase return
     */
    public function cancelReturn(int $returnId, string $reason): PurchaseReturn
    {
        return DB::transaction(function () use ($returnId, $reason) {
            $return = PurchaseReturn::findOrFail($returnId);

            $return->update([
                'status' => PurchaseReturn::STATUS_CANCELLED,
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $return->fresh();
        });
    }

    /**
     * Create a debit note for approved return
     *
     * @param  PurchaseReturn  $return  Purchase return
     * @param  array  $data  Additional debit note data
     * @return DebitNote Created debit note
     */
    protected function createDebitNote(PurchaseReturn $return, array $data = []): DebitNote
    {
        return DebitNote::create([
            'purchase_return_id' => $return->id,
            'supplier_id' => $return->supplier_id,
            'branch_id' => $return->branch_id,
            'amount' => $data['amount'] ?? $return->expected_debit_note_amount,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'status' => DebitNote::STATUS_PENDING,
            'notes' => $data['notes'] ?? "Debit note for purchase return {$return->return_number}",
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Adjust inventory for completed return
     *
     * V25-HIGH-06 FIX: Implement stock deduction for returned items
     *
     * @param  PurchaseReturn  $return  Purchase return
     */
    protected function adjustInventoryForReturn(PurchaseReturn $return): void
    {
        // V25-HIGH-06 FIX: Load items if not already loaded
        if (! $return->relationLoaded('items')) {
            $return->load('items');
        }

        // Skip if no warehouse specified
        if (! $return->warehouse_id) {
            return;
        }

        $stockMovementRepo = app(\App\Repositories\Contracts\StockMovementRepositoryInterface::class);

        foreach ($return->items as $item) {
            // V25-HIGH-06 FIX: Skip if already deducted
            if ($item->isDeducted()) {
                continue;
            }

            // Skip items with zero quantity
            if ((float) $item->qty_returned <= 0) {
                continue;
            }

            // V25-HIGH-06 FIX: Create stock movement to deduct items from inventory
            // Items are being returned to supplier, so stock decreases
            $stockMovementRepo->create([
                'product_id' => $item->product_id,
                'warehouse_id' => $return->warehouse_id,
                'qty' => (float) $item->qty_returned,
                'direction' => 'out',
                'movement_type' => 'purchase_return',
                'reference_type' => 'purchase_return_item',
                'reference_id' => $item->id,
                'notes' => "Purchase return #{$return->return_number} to supplier",
                'unit_cost' => (float) $item->unit_cost,
                'created_by' => Auth::id(),
            ]);

            // V25-HIGH-06 FIX: Mark item as deducted to prevent duplicate deductions
            $item->update([
                'deduct_from_stock' => true,
                'deducted_by' => Auth::id(),
                'deducted_at' => now(),
            ]);
        }
    }

    /**
     * Update supplier performance metrics
     *
     * @param  int  $supplierId  Supplier ID
     * @param  string  $type  Metric type (return, delivery, quality)
     * @param  int|null  $branchId  Branch ID for the metric
     */
    protected function updateSupplierPerformance(int $supplierId, string $type, ?int $branchId = null): void
    {
        $currentPeriod = Carbon::now()->format('Y-m');

        // V24-HIGH-07 FIX: Use correct field names per SupplierPerformanceMetric model
        // and include branch_id to comply with HasBranch trait
        // Ensure we have a valid branch_id - if not provided, try to get from authenticated user
        $effectiveBranchId = $branchId ?? (Auth::check() ? Auth::user()->branch_id : null);

        // If no branch_id available, we cannot create the metric (HasBranch scope would filter it out)
        if ($effectiveBranchId === null) {
            return;
        }

        $metric = SupplierPerformanceMetric::firstOrCreate([
            'supplier_id' => $supplierId,
            'period' => $currentPeriod,
            'branch_id' => $effectiveBranchId,
        ], [
            'total_orders' => 0,
            'on_time_deliveries' => 0,
            'total_ordered_qty' => 0,
            'total_received_qty' => 0,
            'total_rejected_qty' => 0,
            'quality_acceptance_rate' => 100,
            'total_returns' => 0,
            'return_rate' => 0,
        ]);

        if ($type === 'return') {
            $metric->increment('total_returns');

            // Calculate return rate
            $totalReturns = PurchaseReturn::where('supplier_id', $supplierId)
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->withSum('items', 'qty_returned')
                ->get()
                ->sum('items_sum_qty_returned');

            $totalOrders = Purchase::where('supplier_id', $supplierId)
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity');

            if ($totalOrders > 0) {
                // V24-HIGH-07 FIX: Use correct field names per model
                $metric->update([
                    'total_rejected_qty' => $totalReturns,
                    'total_ordered_qty' => $totalOrders,
                    'return_rate' => ($totalReturns / $totalOrders) * 100,
                ]);
            }
        }
    }

    /**
     * Get return statistics for a supplier
     *
     * @param  int  $supplierId  Supplier ID
     * @param  array  $filters  Date filters
     * @return array Statistics
     */
    public function getSupplierReturnStatistics(int $supplierId, array $filters = []): array
    {
        $query = PurchaseReturn::where('supplier_id', $supplierId);

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $totalReturns = $query->count();
        $totalAmount = $query->sum('expected_debit_note_amount');
        $approvedReturns = $query->where('status', PurchaseReturn::STATUS_APPROVED)->count();

        return [
            'total_returns' => $totalReturns,
            'total_amount' => $totalAmount,
            'approved_returns' => $approvedReturns,
            'approval_rate' => $totalReturns > 0 ? ($approvedReturns / $totalReturns) * 100 : 0,
        ];
    }

    /**
     * Get return statistics by condition
     *
     * @param  array  $filters  Optional filters
     * @return array Statistics grouped by condition
     */
    public function getReturnStatisticsByCondition(array $filters = []): array
    {
        $query = PurchaseReturnItem::query();

        if (isset($filters['from_date'])) {
            $query->whereHas('purchaseReturn', function ($q) use ($filters) {
                $q->where('created_at', '>=', $filters['from_date']);
            });
        }

        if (isset($filters['to_date'])) {
            $query->whereHas('purchaseReturn', function ($q) use ($filters) {
                $q->where('created_at', '<=', $filters['to_date']);
            });
        }

        return $query->select('condition', DB::raw('COUNT(*) as count'), DB::raw('SUM(qty_returned) as total_qty'))
            ->groupBy('condition')
            ->get()
            ->toArray();
    }
}
