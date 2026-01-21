<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustRequest;
use App\Http\Requests\StockTransferRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Contracts\InventoryServiceInterface as Inventory;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(protected Inventory $inv) {}

    protected function requireBranchId(Request $request): int
    {
        $branchId = $request->attributes->get('branch_id')
            ?? $request->attributes->get('branch')?->id
            ?? (app()->has('req.branch_id') ? app('req.branch_id') : null);

        abort_if($branchId === null, 400, __('Branch context is required.'));

        $request->attributes->set('branch_id', (int) $branchId);

        return (int) $branchId;
    }

    public function current(Request $request)
    {
        $branchId = $this->requireBranchId($request);
        $pid = (int) $request->integer('product_id');
        $wid = $request->integer('warehouse_id') ?: null;

        Product::query()->where('branch_id', $branchId)->findOrFail($pid);

        if ($wid !== null) {
            Warehouse::query()->where('branch_id', $branchId)->findOrFail($wid);
        }

        $qty = $this->inv->currentQty($pid, $wid);

        return $this->ok(['product_id' => $pid, 'warehouse_id' => $wid, 'qty' => $qty]);
    }

    public function adjust(StockAdjustRequest $request)
    {
        $branchId = $this->requireBranchId($request);
        $data = $request->validated();

        $product = Product::query()
            ->where('branch_id', $branchId)
            ->findOrFail($data['product_id']);

        $warehouseId = $data['warehouse_id'] ?? null;

        if ($warehouseId !== null) {
            Warehouse::query()
                ->where('branch_id', $branchId)
                ->findOrFail($warehouseId);
        }

        $request->attributes->set('branch_id', $branchId);

        // V49-CRIT-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for stock quantities
        $m = $this->inv->adjust($product->id, decimal_float($data['qty'], 4), $warehouseId, $data['note'] ?? null);

        return $this->ok($m, __('Adjusted'));
    }

    public function transfer(StockTransferRequest $request)
    {
        $branchId = $this->requireBranchId($request);
        $data = $request->validated();

        $product = Product::query()
            ->where('branch_id', $branchId)
            ->findOrFail($data['product_id']);

        Warehouse::query()
            ->where('branch_id', $branchId)
            ->findOrFail($data['from_warehouse']);

        Warehouse::query()
            ->where('branch_id', $branchId)
            ->findOrFail($data['to_warehouse']);

        $request->attributes->set('branch_id', $branchId);

        // V49-CRIT-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for stock quantities
        $res = $this->inv->transfer($product->id, decimal_float($data['qty'], 4), $data['from_warehouse'], $data['to_warehouse']);

        return $this->ok(['out' => $res[0], 'in' => $res[1]], __('Transferred'));
    }
}
