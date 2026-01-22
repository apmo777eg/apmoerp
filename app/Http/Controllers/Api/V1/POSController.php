<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PosSession;
use App\Rules\BranchScopedExists;
use App\Services\POSService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class POSController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected POSService $posService
    ) {}

    public function checkout(Request $request, ?int $branchId = null): JsonResponse
    {
        $this->authorize('pos.use');

        // V57-CRITICAL-03 FIX: First resolve the branch, then validate items with branch scoping
        // Step 1: Validate branch_id first if not provided in route
        if (! $branchId) {
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
            ]);
            $branchId = $request->integer('branch_id');
        }

        // Step 2: Validate the branch is active
        $branch = Branch::query()
            ->whereKey($branchId)
            ->where('is_active', true)
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => [__('The selected branch is invalid or inactive.')],
            ]);
        }

        // Step 3: Now validate the rest of the request with branch-scoped exists rules
        // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch data references
        $validationRules = [
            'client_uuid' => 'nullable|uuid',
            'client_sale_uuid' => 'nullable|uuid', // Deprecated: use client_uuid instead
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', new BranchScopedExists('products', 'id', $branch->id)],
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.percent' => 'nullable|boolean',
            'items.*.tax_id' => ['nullable', 'integer', new BranchScopedExists('taxes', 'id', $branch->id, true)],
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|in:cash,card,transfer,cheque',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'customer_id' => ['nullable', 'integer', new BranchScopedExists('customers', 'id', $branch->id, true)],
            'warehouse_id' => ['nullable', 'integer', new BranchScopedExists('warehouses', 'id', $branch->id, true)],
            'notes' => 'nullable|string|max:1000',
        ];

        $request->validate($validationRules);

        // NEW-CRITICAL-03 FIX: Verify user has access to this branch
        $user = auth()->user();
        if (! $this->userCanAccessBranch($user, $branch->id)) {
            throw ValidationException::withMessages([
                'branch_id' => [__('You do not have permission to operate in this branch.')],
            ]);
        }

        // Merge branchId into request data
        $checkoutData = $request->all();
        $checkoutData['branch_id'] = $branch->id;

        try {
            $sale = $this->posService->checkout($checkoutData);

            return response()->json([
                'success' => true,
                'message' => __('Sale completed successfully'),
                'data' => [
                    'id' => $sale->id,
                    'code' => $sale->code,
                    'client_uuid' => $sale->client_uuid,
                    'grand_total' => $sale->grand_total,
                    'paid_total' => $sale->paid_total,
                    'due_total' => $sale->due_total,
                    'status' => $sale->status,
                    'items' => $sale->items->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'qty' => $item->qty,
                        'unit_price' => $item->unit_price,
                        'discount' => $item->discount,
                        'line_total' => $item->line_total,
                    ]),
                    'payments' => $sale->payments->map(fn ($p) => [
                        'method' => $p->payment_method,
                        'amount' => $p->amount,
                        'reference_no' => $p->reference_no,
                    ]),
                    'created_at' => $sale->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getCurrentSession(Branch $branch): JsonResponse
    {
        $this->authorize('pos.use');

        $userId = auth()->id();

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
                'data' => null,
            ], 401);
        }

        $session = $this->posService->getCurrentSession($branch->id, $userId);

        return response()->json([
            'success' => true,
            'data' => $session ? [
                'id' => $session->id,
                'branch_id' => $session->branch_id,
                'user_id' => $session->user_id,
                'opening_cash' => $session->opening_cash,
                'status' => $session->status,
                'opened_at' => $session->opened_at?->toDateTimeString(),
                'total_transactions' => $session->total_transactions,
                'total_sales' => $session->total_sales,
            ] : null,
        ]);
    }

    public function openSession(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('pos.session.manage');

        $request->validate([
            'opening_cash' => 'nullable|numeric|min:0',
        ]);

        $userId = auth()->id();
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
            ], 401);
        }

        try {
            $session = $this->posService->openSession(
                $branch->id,
                $userId,
                decimal_float($request->input('opening_cash') ?? 0)
            );

            return response()->json([
                'success' => true,
                'message' => __('Session opened successfully'),
                'data' => [
                    'id' => $session->id,
                    'branch_id' => $session->branch_id,
                    'user_id' => $session->user_id,
                    'opening_cash' => $session->opening_cash,
                    'status' => $session->status,
                    'opened_at' => $session->opened_at?->toDateTimeString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function closeSession(Request $request, Branch $branch, PosSession $session): JsonResponse
    {
        $this->authorize('pos.session.manage');

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        abort_if($session->branch_id !== $branch->id, 404, __('POS session not found for this branch'));

        try {
            $session = $this->posService->closeSession(
                $session->id,
                decimal_float($request->input('closing_cash')),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => __('Session closed successfully'),
                'data' => [
                    'id' => $session->id,
                    'opening_cash' => $session->opening_cash,
                    'closing_cash' => $session->closing_cash,
                    'expected_cash' => $session->expected_cash,
                    'cash_difference' => $session->cash_difference,
                    'total_transactions' => $session->total_transactions,
                    'total_sales' => $session->total_sales,
                    'payment_summary' => $session->payment_summary,
                    'opened_at' => $session->opened_at?->toDateTimeString(),
                    'closed_at' => $session->closed_at?->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getSessionReport(Branch $branch, PosSession $session): JsonResponse
    {
        $this->authorize('pos.daily-report.view');

        abort_if($session->branch_id !== $branch->id, 404, __('POS session not found for this branch'));

        try {
            $report = $this->posService->getSessionReport($session->id);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * NEW-CRITICAL-03 FIX: Check if user has access to a specific branch
     */
    protected function userCanAccessBranch(?object $user, int $branchId): bool
    {
        if (! $user) {
            return false;
        }

        // Super Admin can access all branches
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'super-admin'])) {
            return true;
        }

        // Check if the branch matches user's primary branch
        if (isset($user->branch_id) && $user->branch_id === $branchId) {
            return true;
        }

        // Check if user has access to additional branches via relationship
        if (method_exists($user, 'branches')) {
            if (! $user->relationLoaded('branches')) {
                $user->load('branches');
            }

            return $user->branches->contains('id', $branchId);
        }

        return false;
    }
}
