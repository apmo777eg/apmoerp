<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidQuantityException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Services\Contracts\InventoryServiceInterface;
use App\Traits\HandlesServiceErrors;
use App\Traits\HasRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Factory as ValidatorFactory;

class InventoryService implements InventoryServiceInterface
{
    use HandlesServiceErrors;
    use HasRequestContext;

    public function __construct(
        protected ValidatorFactory $validator,
        protected StockMovementRepositoryInterface $movements,
        protected StockLevelRepositoryInterface $stockLevels,
    ) {}

    public function currentQty(int $productId, ?int $warehouseId = null): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $warehouseId) {
                $branchId = $this->currentBranchId();

                if ($branchId === null) {
                    $this->logServiceWarning('currentQty', 'Called without branch context', [
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                    ]);

                    return 0.0;
                }

                if ($warehouseId !== null) {
                    $perWarehouse = $this->movements->currentStockPerWarehouse($branchId, $productId);

                    return (float) ($perWarehouse->get($warehouseId, 0.0));
                }

                return $this->stockLevels->getForProduct($branchId, $productId);
            },
            operation: 'currentQty',
            context: ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            defaultValue: 0.0
        );
    }

    public function adjust(int $productId, float $qty, ?int $warehouseId = null, ?string $note = null): StockMovement
    {
        $direction = $qty > 0 ? 'in' : 'out';

        return $this->handleServiceOperation(
            callback: function () use ($productId, $qty, $warehouseId, $note, $direction) {
                $branchId = $this->currentBranchId();

                if ($branchId === null) {
                    throw new InvalidQuantityException('Branch context is required for inventory adjustments.', 422);
                }

                if ($warehouseId === null) {
                    throw new InvalidQuantityException('Warehouse is required for inventory adjustments.', 422);
                }

                // Check if product is a service type
                $product = Product::findOrFail($productId);
                if ($product->type === 'service' || $product->product_type === 'service') {
                    throw new InvalidQuantityException('Cannot adjust stock for service products.', 422);
                }

                if (abs($qty) < 1e-9) {
                    throw new InvalidQuantityException('Qty cannot be zero.', 422);
                }

                $data = [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'qty' => abs($qty),
                    'direction' => $direction,
                    'reason' => $note,
                    'meta' => [],
                ];

                $validator = $this->validator->make($data, [
                    'product_id' => ['required', 'integer', 'exists:products,id'],
                    'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
                    'qty' => ['required', 'numeric'],
                    'direction' => ['required', 'in:in,out'],
                ]);

                $validator->validate();

                return DB::transaction(function () use ($branchId, $data) {
                    $product = Product::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($data['product_id']);

                    $warehouse = Warehouse::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($data['warehouse_id']);

                    // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                    $qty = decimal_float($data['qty']);
                    $direction = $data['direction'];

                    if ($direction === 'out' && $qty > 0) {
                        $qty = -$qty;
                    }

                    $movementData = [
                        'branch_id' => $branchId,
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $warehouse->getKey(),
                        'qty' => abs($qty),
                        'direction' => $direction,
                        'reason' => $data['reason'],
                        'meta' => $data['meta'] ?? [],
                        'created_by' => $this->currentUser()?->getAuthIdentifier(),
                    ];

                    return $this->movements->create($movementData);
                });
            },
            operation: 'adjust',
            context: [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'qty' => $qty,
                'direction' => $direction,
                'reason' => $note,
            ]
        );
    }

    public function getStockLevel(int $productId, ?int $warehouseId = null): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $warehouseId) {
                $query = StockMovement::query()
                    ->where('product_id', $productId);

                if ($warehouseId !== null) {
                    $query->where('warehouse_id', $warehouseId);
                }

                // Migration uses signed quantity: positive = stock in, negative = stock out
                // Sum gives net stock level
                // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                return decimal_float($query->sum('quantity'));
            },
            operation: 'getStockLevel',
            context: ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            defaultValue: 0.0
        );
    }

    public function recordStockAdjustment(array $data): ?StockMovement
    {
        return $this->handleServiceOperation(
            callback: function () use ($data) {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                    $payload = [
                        'product_id' => $data['product_id'] ?? null,
                        'warehouse_id' => $data['warehouse_id'] ?? null,
                        'direction' => $data['direction'] ?? $data['type'] ?? null,
                        'qty' => $data['qty'] ?? $data['quantity'] ?? null,
                        'movement_type' => $data['movement_type'] ?? $data['reason'] ?? 'adjustment',
                        'reference_type' => $data['reference_type'] ?? $data['source_type'] ?? null,
                        'reference_id' => $data['reference_id'] ?? $data['source_id'] ?? null,
                        'notes' => $data['notes'] ?? $data['reason'] ?? null,
                    ];

                    $validator = $this->validator->make($payload, [
                        'product_id' => ['required', 'integer', 'exists:products,id'],
                        'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
                        'direction' => ['required', 'in:in,out'],
                        'qty' => ['required', 'numeric', 'not_in:0'],
                    ]);

                    $validator->validate();

                    $payload['created_by'] = $this->currentUser()?->getAuthIdentifier();

                    // Lock product and warehouse rows to prevent races
                    Product::whereKey($payload['product_id'])->lockForUpdate()->first();
                    \App\Models\Warehouse::whereKey($payload['warehouse_id'])->lockForUpdate()->first();

                    // Use repository for proper schema mapping
                    return $this->movements->create($payload);
                });
            },
            operation: 'recordStockAdjustment',
            context: ['payload_keys' => array_keys($data)],
            defaultValue: null
        );
    }

    public function isStockAvailable(int $productId, float $qty, ?int $warehouseId = null): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $qty, $warehouseId) {
                if ($qty < 0) {
                    return false;
                }

                $currentStock = $this->getStockLevel($productId, $warehouseId);

                return $currentStock >= $qty;
            },
            operation: 'isStockAvailable',
            context: ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'qty' => $qty],
            defaultValue: false
        );
    }

    public function transfer(int $productId, float $qty, int $fromWarehouse, int $toWarehouse): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $qty, $fromWarehouse, $toWarehouse) {
                $branchId = $this->currentBranchId();

                if ($branchId === null) {
                    throw new InvalidQuantityException('Branch context is required for inventory transfers.', 422);
                }

                // Check if product is a service type
                $product = Product::findOrFail($productId);
                if ($product->type === 'service' || $product->product_type === 'service') {
                    throw new InvalidQuantityException('Cannot transfer stock for service products.', 422);
                }

                if ($fromWarehouse === $toWarehouse) {
                    throw new InvalidQuantityException('Source and destination warehouses must be different.', 422);
                }

                if ($qty <= 0) {
                    throw new InvalidQuantityException('Qty must be positive for transfer.', 422);
                }

                return DB::transaction(function () use ($branchId, $productId, $fromWarehouse, $toWarehouse, $qty) {
                    $product = Product::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($productId);

                    // Source warehouse must belong to current branch
                    $fromWh = Warehouse::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($fromWarehouse);

                    // Destination warehouse can be in any branch to allow inter-branch transfers
                    $toWh = Warehouse::query()
                        ->lockForUpdate()
                        ->findOrFail($toWarehouse);

                    // Check if source warehouse has sufficient stock
                    $availableStock = $this->getStockLevel($productId, $fromWarehouse);
                    if ($availableStock < $qty) {
                        throw new InvalidQuantityException(
                            sprintf('Insufficient stock. Available: %.2f, Requested: %.2f', $availableStock, $qty),
                            422
                        );
                    }

                    $userId = $this->currentUser()?->getAuthIdentifier();

                    $outMovement = $this->movements->create([
                        'branch_id' => $branchId,
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $fromWh->getKey(),
                        'qty' => $qty,
                        'direction' => 'out',
                        'reason' => 'transfer',
                        'meta' => [
                            'type' => 'transfer',
                            'direction_label' => 'from',
                            'to_warehouse_id' => $toWh->getKey(),
                        ],
                        'created_by' => $userId,
                    ]);

                    // Use destination warehouse's branch for the incoming movement
                    $inMovement = $this->movements->create([
                        'branch_id' => $toWh->branch_id,
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $toWh->getKey(),
                        'qty' => $qty,
                        'direction' => 'in',
                        'reason' => 'transfer',
                        'meta' => [
                            'type' => 'transfer',
                            'direction_label' => 'to',
                            'from_warehouse_id' => $fromWh->getKey(),
                        ],
                        'created_by' => $userId,
                    ]);

                    return [$outMovement, $inMovement];
                });
            },
            operation: 'transfer',
            context: [
                'product_id' => $productId,
                'from_warehouse_id' => $fromWarehouse,
                'to_warehouse_id' => $toWarehouse,
                'qty' => $qty,
            ]
        );
    }
}
