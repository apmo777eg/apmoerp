<?php

namespace App\Services;

use App\Models\DebitNote;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SupplierPerformanceMetric;
use App\Rules\BranchScopedExists;
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
        $branchId = Auth::user()?->branch_id;

        // V58-CRITICAL-02 FIX: Use BranchScopedExists for branch-aware validation
        $validated = validator($data, [
            'purchase_id' => ['required', 'integer', new BranchScopedExists('purchases', 'id', $branchId)],
            'supplier_id' => ['nullable', 'integer', new BranchScopedExists('suppliers', 'id', $branchId, allowNull: true)],
            'branch_id' => 'nullable|integer|exists:branches,id',
            'warehouse_id' => ['nullable', 'integer', new BranchScopedExists('warehouses', 'id', $branchId, allowNull: true)],
            // Branch-aware GRN (optional)
            'grn_id' => ['nullable', 'integer', new BranchScopedExists('goods_received_notes', 'id', $branchId, allowNull: true)],
            'return_type' => 'nullable|in:full,partial,defective,excess',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'return_date' => 'nullable|date',
            'tracking_number' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:100',

            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', new BranchScopedExists('products', 'id', $branchId)],
            // V24-CRIT-04 FIX: Add required validation for purchase_item_id
            'items.*.purchase_item_id' => 'required|integer|exists:purchase_items,id',
            'items.*.qty_returned' => 'required|numeric|min:0.001',
            // Canonical column: item_condition
            'items.*.item_condition' => 'nullable|in:defective,damaged,wrong_item,excess,expired',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.reason' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string',
        ])->validate();

        return DB::transaction(function () use ($validated) {
            // Validate purchase exists and load its items
            $purchase = Purchase::with('items')->findOrFail($validated['purchase_id']);

            // V25-HIGH-07 FIX: Build an indexed map for efficient lookup
            $purchaseItemsById = $purchase->items->keyBy('id');

            $return = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $validated['supplier_id'] ?? $purchase->supplier_id,
                'branch_id' => $validated['branch_id'] ?? $purchase->branch_id,
                'warehouse_id' => $validated['warehouse_id'] ?? $purchase->warehouse_id,
                'grn_id' => $validated['grn_id'] ?? null,
                'return_type' => $validated['return_type'] ?? PurchaseReturn::TYPE_FULL,
                'status' => PurchaseReturn::STATUS_PENDING,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'return_date' => $validated['return_date'] ?? now()->toDateString(),
                'tracking_number' => $validated['tracking_number'] ?? null,
                'courier_name' => $validated['courier_name'] ?? null,
                'currency' => $purchase->currency ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($validated['items'] as $itemData) {
                // Ensure purchase_item belongs to the purchase
                $purchaseItem = $purchaseItemsById->get($itemData['purchase_item_id']);
                if (! $purchaseItem) {
                    throw new \InvalidArgumentException(
                        "Purchase item ID {$itemData['purchase_item_id']} does not belong to purchase ID {$purchase->id}"
                    );
                }

                // Validate product_id matches the purchase item's product
                if ((int) $purchaseItem->product_id !== (int) $itemData['product_id']) {
                    throw new \InvalidArgumentException(
                        "Product ID {$itemData['product_id']} does not match purchase item's product ID {$purchaseItem->product_id}"
                    );
                }

                $qtyReturned = decimal_float($itemData['qty_returned'], 4);
                $purchaseQty = decimal_float($purchaseItem->quantity, 4);

                if ($qtyReturned > $purchaseQty) {
                    throw new \InvalidArgumentException(
                        "Return quantity ({$qtyReturned}) exceeds purchase quantity ({$purchaseQty}) for product ID {$itemData['product_id']}"
                    );
                }

                $unitCost = decimal_float($itemData['unit_cost'] ?? ($purchaseItem->unit_price ?? 0), 4);

                // For now: returns are stored without tax breakdown per line unless explicitly implemented.
                $lineSubtotal = decimal_float($qtyReturned * $unitCost, 4);
                $lineTax = decimal_float(0, 4);
                $lineTotal = decimal_float($lineSubtotal + $lineTax, 4);

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => (int) $itemData['product_id'],
                    'branch_id' => $return->branch_id,
                    'qty_returned' => $qtyReturned,
                    'qty_original' => $purchaseQty,
                    'unit_cost' => $unitCost,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                    'item_condition' => $itemData['item_condition'] ?? null,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'reason' => $itemData['reason'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $return->update([
                'subtotal' => decimal_float($subtotal, 4),
                'tax_amount' => decimal_float($taxTotal, 4),
                'total_amount' => decimal_float($subtotal + $taxTotal, 4),
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

            // Create debit note if return total is greater than zero
            if (decimal_float($return->total_amount, 4) > 0) {
                $this->createDebitNote($return, $data);
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
                'shipped_date' => $data['shipped_date'] ?? now()->toDateString(),
                'tracking_number' => $data['tracking_number'] ?? null,
                'courier_name' => $data['courier_name'] ?? $return->courier_name,
                'extra_attributes' => array_merge($return->extra_attributes ?? [], [
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
            'amount' => $data['amount'] ?? $return->total_amount,
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
            if (decimal_float($item->qty_returned, 4) <= 0) {
                continue;
            }

            // V25-HIGH-06 FIX: Create stock movement to deduct items from inventory
            // Items are being returned to supplier, so stock decreases
            $stockMovementRepo->create([
                'product_id' => $item->product_id,
                'warehouse_id' => $return->warehouse_id,
                'qty' => decimal_float($item->qty_returned, 4),
                'direction' => 'out',
                'movement_type' => 'purchase_return',
                'reference_type' => 'purchase_return_item',
                'reference_id' => $item->id,
                'notes' => "Purchase return #{$return->return_number} to supplier",
                'unit_cost' => decimal_float($item->unit_cost, 4),
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

        $totalReturns = (clone $query)->count();
        $totalAmount = (clone $query)->sum('total_amount');
        $approvedReturns = (clone $query)->where('status', PurchaseReturn::STATUS_APPROVED)->count();

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

        return $query->select('item_condition', DB::raw('COUNT(*) as count'), DB::raw('SUM(qty_returned) as total_qty'))
            ->groupBy('item_condition')
            ->get()
            ->toArray();
    }
}
