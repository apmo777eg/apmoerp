<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleReturnRequest;
use App\Http\Requests\SaleUpdateRequest;
use App\Http\Requests\SaleVoidRequest;
use App\Models\Sale;
use App\Services\Contracts\SaleServiceInterface as Sales;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(protected Sales $sales) {}

    protected function requireBranchId(Request $request): int
    {
        $branchId = $request->attributes->get('branch_id');

        abort_if($branchId === null, 400, __('Branch context is required.'));

        return (int) $branchId;
    }

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $branchId = $this->requireBranchId($request);
        $rows = Sale::query()
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate($per);

        return $this->ok($rows);
    }

    public function store()
    {
        return $this->ok([], __('Use POS /checkout'));
    }

    public function show(Request $request, Sale $sale)
    {
        // Defense-in-depth: Verify sale belongs to current branch
        $branchId = $this->requireBranchId($request);
        abort_if($sale->branch_id !== $branchId, 404, 'Sale not found in this branch');

        return $this->ok($sale->load('items'));
    }

    public function update(SaleUpdateRequest $request, Sale $sale)
    {
        // Defense-in-depth: Verify sale belongs to current branch
        $branchId = $this->requireBranchId($request);
        abort_if($sale->branch_id !== $branchId, 404, 'Sale not found in this branch');

        $sale->fill($request->validated())->save();

        return $this->ok($sale);
    }

    public function handleReturn(SaleReturnRequest $request, int $sale)
    {
        $data = $request->validated();
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify sale belongs to current branch
        Sale::where('branch_id', $branchId)->findOrFail($sale);

        return $this->ok($this->sales->handleReturn($sale, $data['items'], $request->input('reason')), __('Return processed'));
    }

    public function voidSale(SaleVoidRequest $request, int $sale)
    {
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify sale belongs to current branch
        Sale::where('branch_id', $branchId)->findOrFail($sale);

        return $this->ok($this->sales->voidSale($sale, $request->input('reason')), __('Voided'));
    }

    public function printInvoice(Request $request, int $sale)
    {
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify sale belongs to current branch
        Sale::where('branch_id', $branchId)->findOrFail($sale);

        return $this->ok($this->sales->printInvoice($sale));
    }
}
