<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrdersController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        // NEW-MEDIUM-06 FIX: Validate and cap per_page to prevent DoS
        // V38-HIGH-03 FIX: Add sale_date to allowed sort_by options
        $validated = $request->validate([
            'sort_by' => 'sometimes|string|in:created_at,sale_date,id,status,total_amount',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $sortBy = $validated['sort_by'] ?? 'sale_date';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 50;

        // V38-HIGH-03 FIX: Use sale_date instead of created_at for date filtering
        // This ensures backdated orders appear correctly in date-filtered results
        $query = Sale::query()
            ->with(['customer:id,name,email,phone', 'items.product:id,name,sku'])
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status)
            )
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id)
            )
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('sale_date', '>=', $request->from_date)
            )
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('sale_date', '<=', $request->to_date)
            )
            ->orderBy($sortBy, $sortDir);

        $orders = $query->paginate($perPage);

        return $this->paginatedResponse($orders, __('Orders retrieved successfully'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        $order = Sale::query()
            ->with(['customer', 'items.product', 'createdBy:id,name'])
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $order) {
            return $this->errorResponse(__('Order not found'), 404);
        }

        return $this->successResponse($order, __('Order retrieved successfully'));
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->getStore($request);
        $branchId = $store?->branch_id ?? auth()->user()?->branch_id;

        // V22-HIGH-03 FIX: Scope exists validations to the store's branch
        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')->where(function ($query) use ($branchId) {
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    }
                }),
            ],
            'customer' => 'nullable|array',
            'customer.name' => 'required_with:customer|string|max:255',
            // NEW-CRITICAL-02 FIX: Require at least email or phone to prevent random customer matching
            'customer.email' => 'nullable|required_without:customer.phone|email|max:255',
            'customer.phone' => 'nullable|required_without:customer.email|string|max:50',
            'items' => 'required|array|min:1',
            // V22-HIGH-03 FIX: product_id validation is handled in the transaction with branch check
            'items.*.product_id' => 'required_without:items.*.external_id|integer',
            'items.*.external_id' => 'required_without:items.*.product_id|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'external_id' => 'nullable|string|max:100',
            // V31-MED-06 FIX: Accept order_date from input for proper date handling
            'order_date' => 'nullable|date',
            // V22-HIGH-03 FIX: Scope warehouse validation to the store's branch
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($branchId) {
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    }
                }),
            ],
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $store, $branchId) {
                // V22-HIGH-03 FIX: Use branchId from closure instead of re-extracting
                if (! $branchId) {
                    throw ValidationException::withMessages([
                        'branch_id' => [__('Branch is required for order creation.')],
                    ]);
                }

                $customerId = $validated['customer_id'] ?? null;

                if (! $customerId && isset($validated['customer'])) {
                    $customerData = $validated['customer'];

                    // NEW-CRITICAL-02 FIX: Only look up existing customer if we have email or phone
                    // Without a unique identifier, always create a new customer to avoid wrong assignment
                    $customer = null;
                    if (! empty($customerData['email']) || ! empty($customerData['phone'])) {
                        $customer = Customer::query()
                            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
                            ->when(! empty($customerData['email']), fn ($q) => $q->where('email', $customerData['email']))
                            ->when(
                                empty($customerData['email']) && ! empty($customerData['phone']),
                                fn ($q) => $q->where('phone', $customerData['phone'])
                            )
                            ->first();
                    }

                    if (! $customer) {
                        $customer = Customer::create([
                            'name' => $customerData['name'],
                            'email' => $customerData['email'] ?? null,
                            'phone' => $customerData['phone'] ?? null,
                            'branch_id' => $branchId,
                        ]);
                    }

                    $customerId = $customer->id;
                }

                // BUG-006 FIX: Enforce warehouse scoping to branch
                $warehouseId = $this->resolveWarehouseId($validated['warehouse_id'] ?? null, $branchId);

                if (! $warehouseId) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => [__('Warehouse is required for order creation.')],
                    ]);
                }

                // STILL-V7-HIGH-U05 FIX: Idempotency - use external_reference instead of reference_number
                // This aligns with StoreSyncService which uses external_reference for external IDs
                if (! empty($validated['external_id'])) {
                    $existing = Sale::query()
                        ->where('branch_id', $branchId)
                        ->where('external_reference', $validated['external_id'])
                        ->first();

                    if ($existing) {
                        return $existing->load(['customer', 'items.product']);
                    }
                }

                $subTotal = 0;
                $itemDiscountTotal = 0;
                $itemsData = [];

                foreach ($validated['items'] as $item) {
                    $product = null;

                    if (isset($item['product_id'])) {
                        $product = Product::query()
                            ->where('branch_id', $branchId)
                            ->find($item['product_id']);
                    } elseif (isset($item['external_id']) && $store) {
                        $mapping = ProductStoreMapping::where('store_id', $store->id)
                            ->where('external_id', $item['external_id'])
                            ->first();

                        if ($mapping && $mapping->product->branch_id === $branchId) {
                            $product = $mapping->product;
                        }
                    }

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'items' => [__('Product not available for this branch')],
                        ]);
                    }

                    // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
                    $lineSubtotal = decimal_float($item['price']) * decimal_float($item['quantity']);
                    $lineDiscount = max(0, decimal_float($item['discount'] ?? 0));
                    $lineDiscount = min($lineDiscount, $lineSubtotal);

                    $lineTotal = $lineSubtotal - $lineDiscount;
                    $subTotal += $lineSubtotal;
                    $itemDiscountTotal += $lineDiscount;

                    $itemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'discount_amount' => $lineDiscount,
                        'tax_percent' => 0,
                        'line_total' => $lineTotal,
                    ];
                }

                $orderDiscount = max(0, decimal_float($validated['discount'] ?? 0));
                $orderDiscount = min($orderDiscount, max(0, $subTotal - $itemDiscountTotal));
                $tax = max(0, decimal_float($validated['tax'] ?? 0));
                $shipping = max(0, decimal_float($validated['shipping'] ?? 0));
                $grandTotal = $subTotal - ($itemDiscountTotal + $orderDiscount) + $tax + $shipping;

                // V31-MED-06 FIX: Get integration user ID for store token auth
                // This provides proper audit trail for API orders
                $createdById = auth()->id();
                if (! $createdById && $store?->integration) {
                    $createdById = $store->integration->user_id;
                }

                $sale = Sale::create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'customer_id' => $customerId,
                    'status' => 'draft',
                    'channel' => 'api',
                    'subtotal' => $subTotal,
                    'discount_amount' => $itemDiscountTotal + $orderDiscount,
                    'discount_type' => 'fixed',
                    'tax_amount' => $tax,
                    'shipping_amount' => $shipping,
                    'total_amount' => $grandTotal,
                    'paid_amount' => 0,
                    'payment_status' => 'unpaid',
                    'notes' => $validated['notes'] ?? null,
                    // STILL-V7-HIGH-U05 FIX: Use external_reference for external IDs (aligns with StoreSyncService)
                    // reference_number should remain internal-only
                    'external_reference' => $validated['external_id'] ?? null,
                    // V31-MED-06 FIX: Accept order_date from input, defaulting to today
                    'sale_date' => $validated['order_date'] ?? now()->toDateString(),
                    // V31-MED-06 FIX: Use integration user ID when auth is not available
                    'created_by' => $createdById,
                ]);

                foreach ($itemsData as $itemData) {
                    $sale->items()->create($itemData);
                }

                return $sale->load(['customer', 'items.product']);
            });

            return $this->successResponse($order, __('Order created successfully'), 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('API order creation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'store_id' => $store?->id,
            ]);

            return $this->errorResponse(__('Unable to create order at this time.'), 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,pending,processing,completed,cancelled,refunded',
        ]);

        $store = $this->getStore($request);

        $order = Sale::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $order) {
            return $this->errorResponse(__('Order not found'), 404);
        }

        $allowedTransitions = [
            'draft' => ['pending', 'cancelled'],
            'pending' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        $current = $order->status ?? 'draft';
        $next = $validated['status'];

        if (! in_array($next, $allowedTransitions[$current] ?? [], true)) {
            return $this->errorResponse(__('Invalid status transition'), 422);
        }

        if ($next === 'completed' && $order->remaining_amount > 0) {
            return $this->errorResponse(__('Cannot complete unpaid order'), 422);
        }

        DB::transaction(function () use ($order, $next, $current) {
            $order->status = $next;
            // V22-HIGH-04 FIX: Use the Sale::total_paid accessor which considers all valid payment statuses
            // (completed, posted, paid) instead of hardcoding only 'completed'
            // This ensures consistency with the Sale model's payment status logic
            $totalPaid = $order->total_paid;

            $order->payment_status = $totalPaid >= $order->total_amount
                ? 'paid'
                : ($totalPaid > 0 ? 'partial' : 'unpaid');
            $order->save();

            // V25-HIGH-05 FIX: Dispatch SaleCompleted event when transitioning to completed
            // This triggers inventory deductions and other side effects
            if ($current !== 'completed' && $next === 'completed' && $order->warehouse_id) {
                event(new \App\Events\SaleCompleted($order->fresh('items')));
            }
        });

        return $this->successResponse($order->fresh(), __('Order status updated successfully'));
    }

    public function byExternalId(Request $request, string $externalId): JsonResponse
    {
        $store = $this->getStore($request);

        // STILL-V7-HIGH-U05 FIX: Use external_reference instead of reference_number
        // This aligns with StoreSyncService and the store() method
        $order = Sale::query()
            ->with(['customer', 'items.product'])
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->where('external_reference', $externalId)
            ->first();

        if (! $order) {
            return $this->errorResponse(__('Order not found'), 404);
        }

        return $this->successResponse($order, __('Order retrieved successfully'));
    }

    /**
     * Resolve the warehouse ID for order creation.
     * BUG-006 FIX: Enforce warehouse must belong to the specified branch.
     */
    protected function resolveWarehouseId(?int $preferredId, ?int $branchId = null): ?int
    {
        // If a preferred warehouse ID is provided, validate it belongs to the branch
        if ($preferredId !== null && $branchId !== null) {
            $warehouse = Warehouse::where('id', $preferredId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($warehouse) {
                return $warehouse->id;
            }

            // Preferred warehouse doesn't belong to branch - reject it
            throw ValidationException::withMessages([
                'warehouse_id' => [__('The selected warehouse does not belong to the store\'s branch.')],
            ]);
        }

        // If no preferred ID, try default warehouse scoped to branch
        $defaultWarehouseId = setting('default_warehouse_id');
        if ($defaultWarehouseId !== null && $branchId !== null) {
            $defaultWarehouse = Warehouse::where('id', $defaultWarehouseId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($defaultWarehouse) {
                return $defaultWarehouse->id;
            }
        }

        // Fall back to any active warehouse in the branch
        if ($branchId !== null) {
            $branchWarehouse = Warehouse::where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($branchWarehouse) {
                return $branchWarehouse->id;
            }
        }

        // BUG-006 FIX: Do not fall back to a global warehouse outside the branch
        return null;
    }
}
