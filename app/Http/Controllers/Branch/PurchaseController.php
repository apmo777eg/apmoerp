<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseApproveRequest;
use App\Http\Requests\PurchaseCancelRequest;
use App\Http\Requests\PurchasePayRequest;
use App\Http\Requests\PurchaseReceiveRequest;
use App\Http\Requests\PurchaseReturnRequest;
use App\Http\Requests\PurchaseStoreRequest;
use App\Http\Requests\PurchaseUpdateRequest;
use App\Models\Purchase;
use App\Services\Contracts\PurchaseServiceInterface as Purchases;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected Purchases $purchases,
        protected PurchaseReturnService $purchaseReturnService
    ) {}

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
        $rows = Purchase::query()
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate($per);

        return $this->ok($rows);
    }

    public function store(PurchaseStoreRequest $request)
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->requireBranchId($request);

        $p = $this->purchases->create($payload);

        return $this->ok($p, __('Created'), 201);
    }

    public function show(Request $request, Purchase $purchase)
    {
        // Defense-in-depth: Verify purchase belongs to current branch
        $branchId = $this->requireBranchId($request);
        abort_if($purchase->branch_id !== $branchId, 404, 'Purchase not found in this branch');

        return $this->ok($purchase->load('items'));
    }

    public function update(PurchaseUpdateRequest $request, Purchase $purchase)
    {
        // Defense-in-depth: Verify purchase belongs to current branch
        $branchId = $this->requireBranchId($request);
        abort_if($purchase->branch_id !== $branchId, 404, 'Purchase not found in this branch');

        $purchase->fill($request->validated())->save();

        return $this->ok($purchase);
    }

    public function approve(PurchaseApproveRequest $request, int $purchase)
    {
        $branchId = $this->requireBranchId($request);

        // V57-HIGH-01 FIX: Verify purchase belongs to current branch before approval
        $purchaseModel = Purchase::where('branch_id', $branchId)->findOrFail($purchase);
        
        // Prevent self-approval: verify purchase was not created by the current user
        if ($purchaseModel->created_by === auth()->id()) {
            abort(403, __('You cannot approve your own request.'));
        }

        return $this->ok($this->purchases->approve($purchase), __('Approved'));
    }

    public function receive(PurchaseReceiveRequest $request, int $purchase)
    {
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify purchase belongs to current branch
        Purchase::where('branch_id', $branchId)->findOrFail($purchase);

        return $this->ok($this->purchases->receive($purchase), __('Received'));
    }

    public function pay(PurchasePayRequest $request, int $purchase)
    {
        $data = $request->validated();
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify purchase belongs to current branch
        Purchase::where('branch_id', $branchId)->findOrFail($purchase);

        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        return $this->ok($this->purchases->pay($purchase, decimal_float($data['amount'])), __('Paid'));
    }

    public function handleReturn(PurchaseReturnRequest $request, int $purchase)
    {
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify purchase belongs to current branch
        Purchase::where('branch_id', $branchId)->findOrFail($purchase);

        // V25-HIGH-09 FIX: Wire the endpoint to PurchaseReturnService for proper return workflow
        $validated = $request->validated();

        // Build the return data with purchase_id and branch_id
        $returnData = [
            'purchase_id' => $purchase,
            'branch_id' => $branchId,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'items' => $validated['items'],
        ];

        $return = $this->purchaseReturnService->createReturn($returnData);

        return $this->ok($return->load('items'), __('Return created successfully'), 201);
    }

    public function cancel(PurchaseCancelRequest $request, int $purchase)
    {
        $branchId = $this->requireBranchId($request);
        
        // V57-HIGH-01 FIX: Verify purchase belongs to current branch
        Purchase::where('branch_id', $branchId)->findOrFail($purchase);

        return $this->ok($this->purchases->cancel($purchase), __('Cancelled'));
    }
}
