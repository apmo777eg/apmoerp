<?php

namespace App\Services;

use App\Models\InventoryTransit;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransferService
{
    use HandlesServiceErrors;

    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create a new stock transfer request
     */
    public function createTransfer(array $data): StockTransfer
    {
        // Input validation
        $validated = validator($data, [
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id|different:from_warehouse_id',
            'from_branch_id' => 'nullable|integer|exists:branches,id',
            'to_branch_id' => 'nullable|integer|exists:branches,id',
            'transfer_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:transfer_date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'insurance_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.condition' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',
        ])->validate();

        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($validated) {
                // Validate different warehouses
                abort_if(
                    $validated['from_warehouse_id'] === $validated['to_warehouse_id'],
                    422,
                    'Cannot transfer to the same warehouse'
                );

                // Create transfer
                // NEW-HIGH-08 FIX: Set branch_id from from_branch_id or from_warehouse's branch
                $branchId = $validated['from_branch_id'] ?? null;
                if (! $branchId) {
                    // Try to get branch from the source warehouse
                    $fromWarehouse = \App\Models\Warehouse::find($validated['from_warehouse_id']);
                    $branchId = $fromWarehouse?->branch_id;
                }

                $transfer = StockTransfer::create([
                    'branch_id' => $branchId,
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'from_branch_id' => $validated['from_branch_id'] ?? null,
                    'to_branch_id' => $validated['to_branch_id'] ?? null,
                    'transfer_type' => $this->determineTransferType($validated),
                    'status' => StockTransfer::STATUS_PENDING,
                    'transfer_date' => $validated['transfer_date'] ?? now()->toDateString(),
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'priority' => $validated['priority'] ?? StockTransfer::PRIORITY_MEDIUM,
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'shipping_cost' => $validated['shipping_cost'] ?? 0,
                    'insurance_cost' => $validated['insurance_cost'] ?? 0,
                    'total_cost' => ($validated['shipping_cost'] ?? 0) + ($validated['insurance_cost'] ?? 0),
                    'currency' => $validated['currency'] ?? 'EGP',
                    // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                    'requested_by' => actual_user_id(),
                    'created_by' => actual_user_id(),
                ]);

                // V27-HIGH-02 FIX: Pre-load products to avoid N+1 query issue
                $productIds = array_column($validated['items'], 'product_id');
                $products = \App\Models\Product::whereIn('id', $productIds)
                    ->select('id', 'cost', 'standard_cost')
                    ->get()
                    ->keyBy('id');

                // Add items
                foreach ($validated['items'] as $itemData) {
                    // Validate stock availability
                    $availableStock = $this->stockService->getCurrentStock(
                        $itemData['product_id'],
                        $validated['from_warehouse_id']
                    );

                    $requestedQty = (float) ($itemData['qty'] ?? 0);

                    abort_if(
                        $availableStock < $requestedQty,
                        422,
                        "Insufficient stock for product ID {$itemData['product_id']}. Available: {$availableStock}, Requested: {$requestedQty}"
                    );

                    // V27-HIGH-02 FIX: Use provided unit_cost, or fetch from pre-loaded product's cost if not provided
                    // Do NOT default to 0 as that breaks inventory valuation
                    // Fallback order: explicit unit_cost > product.cost (actual cost) > product.standard_cost (standard cost)
                    // If both are null, inventory valuation will be skipped for this item (acceptable for non-valued transfers)
                    $unitCost = $itemData['unit_cost'] ?? null;
                    if ($unitCost === null) {
                        $product = $products->get($itemData['product_id']);
                        $unitCost = $product?->cost ?? $product?->standard_cost ?? null;
                    }

                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $itemData['product_id'],
                        'qty_requested' => $requestedQty,
                        'qty_approved' => $requestedQty, // Auto-approve quantity initially
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                        'unit_cost' => $unitCost,
                        'condition_on_shipping' => $itemData['condition'] ?? 'good',
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                // Calculate totals
                $transfer->calculateTotals();

                Log::info('Stock transfer created', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'from_warehouse' => $validated['from_warehouse_id'],
                    'to_warehouse' => $validated['to_warehouse_id'],
                ]);

                return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);
            }),
            operation: 'create_transfer',
            context: $validated
        );
    }

    /**
     * Approve a stock transfer
     */
    public function approveTransfer(int $transferId, ?int $userId = null): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($transferId, $userId) {
                $transfer = StockTransfer::with(['items.product'])->findOrFail($transferId);
                $userId = $userId ?? auth()->id();

                abort_if(
                    ! $transfer->canBeApproved(),
                    422,
                    "Transfer {$transfer->transfer_number} cannot be approved in {$transfer->status} status"
                );

                // Re-validate stock availability before approval
                foreach ($transfer->items as $item) {
                    $availableStock = $this->stockService->getCurrentStock(
                        $item->product_id,
                        $transfer->from_warehouse_id
                    );

                    abort_if(
                        $availableStock < $item->qty_approved,
                        422,
                        "Insufficient stock for {$item->product->name}. Available: {$availableStock}, Required: {$item->qty_approved}"
                    );
                }

                $transfer->approve($userId);

                Log::info('Stock transfer approved', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'approved_by' => $userId,
                ]);

                return $transfer->refresh();
            }),
            operation: 'approve_transfer',
            context: ['transfer_id' => $transferId]
        );
    }

    /**
     * Ship/dispatch the transfer
     * V27-MED-05 FIX: Added optional userId parameter for CLI/queue context support
     */
    public function shipTransfer(int $transferId, array $shippingData, ?int $userId = null): StockTransfer
    {
        // V6-MEDIUM-04 FIX: Explicit validation of payload shape with item IDs
        $validated = validator($shippingData, [
            'tracking_number' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|integer|exists:stock_transfer_items,id',
            'items.*.qty_shipped' => 'nullable|numeric|min:0',
        ])->validate();

        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($transferId, $validated, $userId) {
                $transfer = StockTransfer::with(['items.product'])->findOrFail($transferId);
                // V27-MED-05 FIX: Use provided userId or fall back to auth()->id()
                $userId = $userId ?? auth()->id();

                abort_if(
                    ! $transfer->canBeShipped(),
                    422,
                    "Transfer {$transfer->transfer_number} cannot be shipped in {$transfer->status} status"
                );

                // V6-MEDIUM-04 FIX: Build a map from item ID to qty_shipped for proper lookup
                $itemQuantities = [];
                if (isset($validated['items']) && is_array($validated['items'])) {
                    foreach ($validated['items'] as $key => $itemData) {
                        // Support both indexed array with 'id' field and associative array keyed by item ID
                        $itemId = $itemData['id'] ?? $key;
                        if (is_numeric($itemId) && isset($itemData['qty_shipped'])) {
                            $itemQuantities[(int) $itemId] = (float) $itemData['qty_shipped'];
                        }
                    }
                }

                // Move stock from source warehouse to transit table
                foreach ($transfer->items as $item) {
                    $qtyToShip = $itemQuantities[$item->id] ?? $item->qty_approved;

                    // V6-MEDIUM-04 FIX: Enforce qty_shipped <= qty_approved
                    if ($qtyToShip > $item->qty_approved) {
                        abort(
                            422,
                            "Cannot ship {$qtyToShip} units for item {$item->id}. Maximum approved: {$item->qty_approved}"
                        );
                    }

                    // Skip if nothing to ship
                    if ($qtyToShip <= 0) {
                        continue;
                    }

                    // Update item shipped quantity
                    $item->update(['qty_shipped' => $qtyToShip]);

                    // V27-HIGH-02 FIX: Get unit_cost from transfer item for inventory valuation
                    // Use ?? to preserve zero values (0 is a valid unit_cost)
                    $unitCost = $item->unit_cost ?? null;

                    // Deduct from source warehouse
                    // V27-HIGH-02 FIX: Pass unit_cost for inventory valuation
                    // V27-MED-05 FIX: Pass userId for CLI/queue context support
                    // V28-HIGH-03 FIX: Add reference_type and reference_id for traceability
                    $this->stockService->adjustStock(
                        productId: $item->product_id,
                        warehouseId: $transfer->from_warehouse_id,
                        quantity: -$qtyToShip, // Negative for deduction
                        type: StockMovement::TYPE_TRANSFER_OUT,
                        reference: "Transfer Out: {$transfer->transfer_number}",
                        notes: 'In transit to '.$transfer->toWarehouse->name,
                        referenceId: $transfer->id,
                        referenceType: StockTransfer::class,
                        unitCost: $unitCost,
                        userId: $userId
                    );

                    // Add to transit table (inventory is now "in-flight")
                    InventoryTransit::create([
                        'product_id' => $item->product_id,
                        'from_warehouse_id' => $transfer->from_warehouse_id,
                        'to_warehouse_id' => $transfer->to_warehouse_id,
                        'stock_transfer_id' => $transfer->id,
                        'quantity' => $qtyToShip,
                        'unit_cost' => $item->unit_cost,
                        'batch_number' => $item->batch_number,
                        'expiry_date' => $item->expiry_date,
                        'status' => InventoryTransit::STATUS_IN_TRANSIT,
                        'shipped_at' => now(),
                        'expected_arrival' => $transfer->expected_delivery_date,
                        'notes' => "Transfer: {$transfer->transfer_number}",
                        'created_by' => $userId,
                    ]);
                }

                // Update transfer totals
                $transfer->calculateTotals();

                // Mark as shipped
                $transfer->markAsShipped($userId, $validated);

                Log::info('Stock transfer shipped', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'tracking_number' => $validated['tracking_number'] ?? null,
                ]);

                return $transfer->refresh();
            }),
            operation: 'ship_transfer',
            context: ['transfer_id' => $transferId, 'shipping_data' => $validated]
        );
    }

    /**
     * Receive the transfer at destination
     * V27-MED-05 FIX: Added optional userId parameter for CLI/queue context support
     */
    public function receiveTransfer(int $transferId, array $receivingData, ?int $userId = null): StockTransfer
    {
        // V6-MEDIUM-04 FIX: Explicit validation of payload shape with item IDs
        // V28-HIGH-02 FIX: Added custom validation to ensure qty_damaged <= qty_received
        $validated = validator($receivingData, [
            'items' => 'required|array',
            'items.*.id' => 'nullable|integer|exists:stock_transfer_items,id',
            'items.*.qty_received' => 'nullable|numeric|min:0',
            'items.*.qty_damaged' => 'nullable|numeric|min:0',
            'items.*.condition' => 'nullable|string|max:50',
            'items.*.damage_report' => 'nullable|string',
        ])->after(function ($validator) use ($receivingData) {
            // V28-HIGH-02 FIX: Validate qty_damaged does not exceed qty_received for each item
            if (isset($receivingData['items']) && is_array($receivingData['items'])) {
                foreach ($receivingData['items'] as $index => $itemData) {
                    $qtyReceived = (float) ($itemData['qty_received'] ?? 0);
                    $qtyDamaged = (float) ($itemData['qty_damaged'] ?? 0);
                    if ($qtyDamaged > $qtyReceived) {
                        $validator->errors()->add(
                            "items.{$index}.qty_damaged",
                            "Damaged quantity ({$qtyDamaged}) cannot exceed received quantity ({$qtyReceived})."
                        );
                    }
                }
            }
        })->validate();

        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($transferId, $validated, $userId) {
                $transfer = StockTransfer::with(['items.product'])->findOrFail($transferId);
                // V27-MED-05 FIX: Use provided userId or fall back to auth()->id()
                $userId = $userId ?? auth()->id();

                abort_if(
                    ! $transfer->canBeReceived(),
                    422,
                    "Transfer {$transfer->transfer_number} cannot be received in {$transfer->status} status"
                );

                // V6-MEDIUM-04 FIX: Build a map from item ID to receiving data for proper lookup
                $itemReceivingMap = [];
                if (isset($validated['items']) && is_array($validated['items'])) {
                    foreach ($validated['items'] as $key => $itemData) {
                        // Support both indexed array with 'id' field and associative array keyed by item ID
                        $itemId = $itemData['id'] ?? $key;
                        if (is_numeric($itemId)) {
                            $itemReceivingMap[(int) $itemId] = $itemData;
                        }
                    }
                }

                // Process received items and move from transit to destination
                foreach ($transfer->items as $item) {
                    $itemReceivingData = $itemReceivingMap[$item->id] ?? [];

                    $qtyReceived = (float) ($itemReceivingData['qty_received'] ?? $item->qty_shipped);
                    $qtyDamaged = (float) ($itemReceivingData['qty_damaged'] ?? 0);

                    // V6-MEDIUM-04 FIX: Enforce qty_received <= qty_shipped
                    if ($qtyReceived > $item->qty_shipped) {
                        abort(
                            422,
                            "Cannot receive {$qtyReceived} units for item {$item->id}. Maximum shipped: {$item->qty_shipped}"
                        );
                    }

                    // V28-HIGH-02 FIX: Enforce qty_damaged <= qty_received at processing level
                    // (Also validated at input level, but double-check here for safety)
                    if ($qtyDamaged > $qtyReceived) {
                        abort(
                            422,
                            "Damaged quantity ({$qtyDamaged}) cannot exceed received quantity ({$qtyReceived}) for item {$item->id}."
                        );
                    }

                    $qtyGood = $qtyReceived - $qtyDamaged;

                    // Update item
                    $item->update([
                        'qty_received' => $qtyReceived,
                        'qty_damaged' => $qtyDamaged,
                        'condition_on_receiving' => $itemReceivingData['condition'] ?? 'good',
                        'damage_report' => $itemReceivingData['damage_report'] ?? null,
                    ]);

                    // V28-CRITICAL-01 FIX: Handle transit records quantity-aware for partial receipts
                    // Process transit records in FIFO order (oldest first by shipped_at)
                    // Only mark as received the quantity that was actually received
                    $transitRecords = InventoryTransit::where('stock_transfer_id', $transfer->id)
                        ->where('product_id', $item->product_id)
                        ->where('status', InventoryTransit::STATUS_IN_TRANSIT)
                        ->orderBy('shipped_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    // V28-CRITICAL-01 FIX: Use string-based arithmetic to maintain precision
                    $remainingToReceive = (string) $qtyReceived;
                    foreach ($transitRecords as $transitRecord) {
                        if (bccomp($remainingToReceive, '0', 4) <= 0) {
                            // No more to receive - this transit record stays in transit
                            break;
                        }

                        $transitQty = (string) $transitRecord->quantity;
                        if (bccomp($transitQty, $remainingToReceive, 4) <= 0) {
                            // Fully receive this transit record
                            $transitRecord->markAsReceived();
                            $remainingToReceive = bcsub($remainingToReceive, $transitQty, 4);
                        } else {
                            // Partial receive: split the transit record
                            // Create a new record for the remaining in-transit quantity
                            $splitQty = bcsub($transitQty, $remainingToReceive, 4);
                            InventoryTransit::create([
                                'product_id' => $transitRecord->product_id,
                                'from_warehouse_id' => $transitRecord->from_warehouse_id,
                                'to_warehouse_id' => $transitRecord->to_warehouse_id,
                                'stock_transfer_id' => $transitRecord->stock_transfer_id,
                                'quantity' => $splitQty,
                                'unit_cost' => $transitRecord->unit_cost,
                                'batch_number' => $transitRecord->batch_number,
                                'expiry_date' => $transitRecord->expiry_date,
                                'status' => InventoryTransit::STATUS_IN_TRANSIT,
                                'shipped_at' => $transitRecord->shipped_at,
                                'expected_arrival' => $transitRecord->expected_arrival,
                                'notes' => $transitRecord->notes.' (split from partial receipt)',
                                'created_by' => $transitRecord->created_by,
                            ]);

                            // Update the original record with received quantity and mark as received
                            $transitRecord->update(['quantity' => $remainingToReceive]);
                            $transitRecord->markAsReceived();
                            $remainingToReceive = '0';
                        }
                    }

                    // V27-HIGH-02 FIX: Get unit_cost from transfer item for inventory valuation
                    // Use ?? to preserve zero values (0 is a valid unit_cost)
                    $unitCost = $item->unit_cost ?? null;

                    // Add good stock to destination warehouse
                    // V27-HIGH-02 FIX: Pass unit_cost for inventory valuation
                    // V27-MED-05 FIX: Pass userId for CLI/queue context support
                    // V28-HIGH-03 FIX: Add reference_type and reference_id for traceability
                    if ($qtyGood > 0) {
                        $this->stockService->adjustStock(
                            productId: $item->product_id,
                            warehouseId: $transfer->to_warehouse_id,
                            quantity: $qtyGood,
                            type: StockMovement::TYPE_TRANSFER_IN,
                            reference: "Transfer In: {$transfer->transfer_number}",
                            notes: 'Received from '.$transfer->fromWarehouse->name,
                            referenceId: $transfer->id,
                            referenceType: StockTransfer::class,
                            unitCost: $unitCost,
                            userId: $userId
                        );
                    }

                    // Record damaged items separately if any
                    // V27-HIGH-02 FIX: Pass unit_cost for inventory valuation
                    // V27-MED-05 FIX: Pass userId for CLI/queue context support
                    // V28-HIGH-03 FIX: Add reference_type and reference_id for traceability
                    if ($qtyDamaged > 0) {
                        $this->stockService->adjustStock(
                            productId: $item->product_id,
                            warehouseId: $transfer->to_warehouse_id,
                            quantity: $qtyDamaged,
                            type: StockMovement::TYPE_ADJUSTMENT,
                            reference: "Transfer Damage: {$transfer->transfer_number}",
                            notes: 'Damaged during transfer - '.($itemReceivingData['damage_report'] ?? 'No details'),
                            referenceId: $transfer->id,
                            referenceType: StockTransfer::class,
                            unitCost: $unitCost,
                            userId: $userId
                        );
                    }
                }

                // Update transfer totals
                $transfer->calculateTotals();

                // Mark as received
                $transfer->markAsReceived($userId);

                // Auto-complete if all items fully received
                if ($this->isFullyReceived($transfer)) {
                    $transfer->complete();
                }

                Log::info('Stock transfer received', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'received_by' => $userId,
                    'total_received' => $transfer->total_qty_received,
                    'total_damaged' => $transfer->total_qty_damaged,
                ]);

                return $transfer->refresh();
            }),
            operation: 'receive_transfer',
            context: ['transfer_id' => $transferId, 'receiving_data' => $validated]
        );
    }

    /**
     * Reject a transfer
     */
    public function rejectTransfer(int $transferId, ?string $reason = null, ?int $userId = null): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($transferId, $reason, $userId) {
                $transfer = StockTransfer::findOrFail($transferId);
                $userId = $userId ?? auth()->id();

                abort_if(
                    $transfer->status !== StockTransfer::STATUS_PENDING,
                    422,
                    "Transfer {$transfer->transfer_number} cannot be rejected in {$transfer->status} status"
                );

                $transfer->reject($userId, $reason);

                Log::info('Stock transfer rejected', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'reason' => $reason,
                    'rejected_by' => $userId,
                ]);

                return $transfer->refresh();
            }),
            operation: 'reject_transfer',
            context: ['transfer_id' => $transferId, 'reason' => $reason]
        );
    }

    /**
     * Cancel a transfer
     * V27-HIGH-02 FIX: Pass unit_cost to adjustStock for inventory valuation
     * V27-MED-05 FIX: Pass userId to adjustStock for CLI/queue context support
     */
    public function cancelTransfer(int $transferId, ?string $reason = null, ?int $userId = null): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($transferId, $reason, $userId) {
                $transfer = StockTransfer::with(['items'])->findOrFail($transferId);
                $userId = $userId ?? auth()->id();

                $oldStatus = $transfer->status;

                // If already shipped, need to return stock from transit to source
                if ($transfer->status === StockTransfer::STATUS_IN_TRANSIT) {
                    // Find all transit records for this transfer
                    $transitRecords = InventoryTransit::where('stock_transfer_id', $transfer->id)
                        ->where('status', InventoryTransit::STATUS_IN_TRANSIT)
                        ->get();

                    foreach ($transitRecords as $transitRecord) {
                        // V27-HIGH-02 FIX: Get unit_cost from transit record for inventory valuation
                        // Use ?? to preserve zero values (0 is a valid unit_cost)
                        $unitCost = $transitRecord->unit_cost ?? null;

                        // Return stock to source warehouse
                        // V27-HIGH-02 FIX: Pass unit_cost for inventory valuation
                        // V27-MED-05 FIX: Pass userId for CLI/queue context support
                        // V28-HIGH-03 FIX: Add reference_type and reference_id for traceability
                        $this->stockService->adjustStock(
                            productId: $transitRecord->product_id,
                            warehouseId: $transfer->from_warehouse_id,
                            quantity: $transitRecord->quantity,
                            type: StockMovement::TYPE_ADJUSTMENT,
                            reference: "Transfer Cancelled: {$transfer->transfer_number}",
                            notes: 'Stock returned from transit due to cancellation',
                            referenceId: $transfer->id,
                            referenceType: StockTransfer::class,
                            unitCost: $unitCost,
                            userId: $userId
                        );

                        // Mark transit record as cancelled
                        $transitRecord->markAsCancelled();
                    }
                }

                $transfer->cancel($userId, $reason);

                Log::info('Stock transfer cancelled', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'old_status' => $oldStatus,
                    'reason' => $reason,
                    'cancelled_by' => $userId,
                ]);

                return $transfer->refresh();
            }),
            operation: 'cancel_transfer',
            context: ['transfer_id' => $transferId, 'reason' => $reason]
        );
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics(?int $warehouseId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = StockTransfer::query();

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('from_warehouse_id', $warehouseId)
                    ->orWhere('to_warehouse_id', $warehouseId);
            });
        }

        if ($startDate) {
            $query->whereDate('transfer_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transfer_date', '<=', $endDate);
        }

        return [
            'total_transfers' => $query->count(),
            'pending_transfers' => (clone $query)->where('status', StockTransfer::STATUS_PENDING)->count(),
            'approved_transfers' => (clone $query)->where('status', StockTransfer::STATUS_APPROVED)->count(),
            'in_transit_transfers' => (clone $query)->where('status', StockTransfer::STATUS_IN_TRANSIT)->count(),
            'completed_transfers' => (clone $query)->where('status', StockTransfer::STATUS_COMPLETED)->count(),
            'overdue_transfers' => (clone $query)->where('expected_delivery_date', '<', now())->whereNotIn('status', [StockTransfer::STATUS_COMPLETED, StockTransfer::STATUS_CANCELLED])->count(),
            'total_qty_transferred' => (clone $query)->where('status', StockTransfer::STATUS_COMPLETED)->sum('total_qty_received'),
            'total_cost' => (clone $query)->sum('total_cost'),
        ];
    }

    /**
     * Helper methods
     */
    protected function determineTransferType(array $data): string
    {
        if (isset($data['from_branch_id']) && isset($data['to_branch_id']) && $data['from_branch_id'] !== $data['to_branch_id']) {
            return StockTransfer::TYPE_INTER_BRANCH;
        }

        return StockTransfer::TYPE_INTER_WAREHOUSE;
    }

    protected function isFullyReceived(StockTransfer $transfer): bool
    {
        foreach ($transfer->items as $item) {
            if (! $item->isFullyReceived()) {
                return false;
            }
        }

        return true;
    }
}
