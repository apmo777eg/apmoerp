<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use App\Models\ProductionOrderOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ManufacturingService (DB-first)
 *
 * Aligns all create/update logic with migrations in:
 * - database/migrations/2026_01_05_000004_create_manufacturing_tables.php
 */
class ManufacturingService
{
    public function createProductionOrder(BillOfMaterial $bom, array $data): ProductionOrder
    {
        return DB::transaction(function () use ($bom, $data) {
            $branchId = (int) ($data['branch_id'] ?? $bom->branch_id ?? auth()->user()?->branch_id);

            $plannedQty = (float) ($data['planned_quantity']
                ?? $data['quantity_planned']
                ?? $data['quantity']
                ?? 1);

            $plannedStart = $data['planned_start_date'] ?? now()->toDateString();
            $plannedEnd = $data['planned_end_date']
                ?? $this->calculatePlannedEndDate($bom, $plannedQty);

            $order = ProductionOrder::create([
                'branch_id' => $branchId,
                'bom_id' => $bom->id,
                'product_id' => $bom->product_id,
                'warehouse_id' => $data['warehouse_id'] ?? null,

                'reference_number' => $data['reference_number'] ?? ProductionOrder::generateOrderNumber($branchId),

                'status' => $data['status'] ?? ProductionOrder::STATUS_DRAFT,
                'priority' => $data['priority'] ?? ProductionOrder::PRIORITY_NORMAL,

                'planned_quantity' => $plannedQty,
                'produced_quantity' => 0,

                'planned_start_date' => $plannedStart,
                'planned_end_date' => $plannedEnd,

                'estimated_cost' => (float) ($bom->estimated_cost ?? 0),
                'actual_cost' => 0,

                'sale_id' => $data['sale_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'custom_fields' => $data['custom_fields'] ?? null,

                'created_by' => auth()->id(),
            ]);

            // Material requirements
            foreach ($bom->items as $bomItem) {
                $requiredQty = (float) $bomItem->quantity_required * $plannedQty;
                $unitCost = (float) ($bomItem->rawMaterial?->cost_price ?? 0);

                ProductionOrderItem::create([
                    'production_order_id' => $order->id,
                    'branch_id' => $branchId,
                    'product_id' => $bomItem->raw_material_id,
                    'quantity_required' => $requiredQty,
                    'quantity_consumed' => 0,
                    'unit_id' => $bomItem->rawMaterial?->unit_id,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $requiredQty,
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'is_issued' => false,
                ]);
            }

            // Operations routing
            foreach ($bom->operations as $operation) {
                $plannedMinutes = (float) ($operation->duration_minutes ?? 0) * $plannedQty;

                ProductionOrderOperation::create([
                    'production_order_id' => $order->id,
                    'branch_id' => $branchId,
                    'bom_operation_id' => $operation->id,
                    'work_center_id' => $operation->work_center_id,
                    'operation_name' => $operation->name,
                    'sequence' => (int) ($operation->sequence ?? 0),
                    'status' => 'pending',
                    'planned_duration_minutes' => $plannedMinutes,
                    'operator_id' => null,
                    'notes' => $operation->description,
                ]);
            }

            return $order;
        });
    }

    public function releaseProductionOrder(ProductionOrder $order): ProductionOrder
    {
        // "Release" transitions draft -> pending
        if ($order->status === ProductionOrder::STATUS_DRAFT) {
            $order->status = ProductionOrder::STATUS_PENDING;
            $order->save();
        }

        return $order;
    }

    public function startOperation(ProductionOrderOperation $operation, ?int $operatorId = null): ProductionOrderOperation
    {
        if ($operation->status === 'pending') {
            $operation->status = 'in_progress';
            $operation->started_at = now();
            if ($operatorId) {
                $operation->operator_id = $operatorId;
            }
            $operation->save();

            $order = $operation->productionOrder;
            if ($order && $order->status === ProductionOrder::STATUS_PENDING) {
                $order->status = ProductionOrder::STATUS_IN_PROGRESS;
                $order->actual_start_date = now();
                $order->save();
            }
        }

        return $operation;
    }

    public function completeOperation(ProductionOrderOperation $operation, ?array $qualityResults = null): ProductionOrderOperation
    {
        if ($operation->status === 'in_progress') {
            $operation->status = 'completed';
            $operation->completed_at = now();

            if ($operation->started_at) {
                $operation->actual_duration_minutes = (float) $operation->started_at->diffInMinutes($operation->completed_at);
            }

            if ($qualityResults) {
                $operation->quality_results = $qualityResults;
            }

            $operation->save();

            $order = $operation->productionOrder;
            if ($order && $order->operations()->where('status', '!=', 'completed')->count() === 0) {
                $order->status = ProductionOrder::STATUS_COMPLETED;
                $order->actual_end_date = now();
                $order->save();
            }
        }

        return $operation;
    }

    public function recordMaterialConsumption(ProductionOrder $order, int $productId, float $quantity, ?int $warehouseId = null): bool
    {
        try {
            $item = $order->items()->where('product_id', $productId)->first();
            if (! $item) {
                return false;
            }

            $item->quantity_consumed = (float) $item->quantity_consumed + $quantity;
            if ($item->quantity_consumed >= (float) $item->quantity_required) {
                $item->is_issued = true;
                $item->issued_at = now();
                if ($warehouseId) {
                    $item->warehouse_id = $warehouseId;
                }
            }

            $item->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to record material consumption', [
                'order_id' => $order->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function calculatePlannedEndDate(BillOfMaterial $bom, float $plannedQuantity): string
    {
        $totalMinutes = 0.0;
        foreach ($bom->operations as $operation) {
            $totalMinutes += ((float) ($operation->duration_minutes ?? 0)) * $plannedQuantity;
        }

        // planned_end_date is a DATE column
        return now()->addMinutes((int) round($totalMinutes))->toDateString();
    }
}
