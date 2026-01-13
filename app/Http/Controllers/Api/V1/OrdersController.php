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
use Illuminate\Validation\ValidationException;

class OrdersController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        // NEW-MEDIUM-06 FIX: Validate and cap per_page to prevent DoS
        $validated = $request->validate([
            'sort_by' => 'sometimes|string|in:created_at,id,status,total_amount',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 50;

        $query = Sale::query()
            ->with(['customer:id,name,email,phone', 'items.product:id,name,sku'])
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status)
            )
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id)
            )
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->from_date)
            )
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->to_date)
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
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer' => 'nullable|array',
            'customer.name' => 'required_with:customer|string|max:255',
            // NEW-CRITICAL-02 FIX: Require at least email or phone to prevent random customer matching
            'customer.email' => 'nullable|required_without:customer.phone|email|max:255',
            'customer.phone' => 'nullable|required_without:customer.email|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required_without:items.*.external_id|exists:products,id',
            'items.*.external_id' => 'required_without:items.*.product_id|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'external_id' => 'nullable|string|max:100',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $store = $this->getStore($request);

        try {
            $order = DB::transaction(function () use ($validated, $store) {
                $branchId = $store?->branch_id ?? auth()->user()?->branch_id;

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

                // Idempotency: reuse existing sale by external reference for the same branch
                if (! empty($validated['external_id'])) {
                    $existing = Sale::query()
                        ->where('branch_id', $branchId)
                        ->where('reference_number', $validated['external_id'])
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

                    $lineSubtotal = (float) $item['price'] * (float) $item['quantity'];
                    $lineDiscount = max(0, (float) ($item['discount'] ?? 0));
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

                $orderDiscount = max(0, (float) ($validated['discount'] ?? 0));
                $orderDiscount = min($orderDiscount, max(0, $subTotal - $itemDiscountTotal));
                $tax = max(0, (float) ($validated['tax'] ?? 0));
                $shipping = max(0, (float) ($validated['shipping'] ?? 0));
                $grandTotal = $subTotal - ($itemDiscountTotal + $orderDiscount) + $tax + $shipping;

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
                    'reference_number' => $validated['external_id'] ?? null,
                    'sale_date' => now()->toDateString(),
                    'created_by' => auth()->id(),
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

        DB::transaction(function () use ($order, $next) {
            $order->status = $next;
            // V7-HIGH-N05 FIX: Use computed total_paid from payments instead of paid_amount
            // paid_amount can be stale or manually edited, leading to wrong payment status
            // Load payments to get accurate total
            $order->load('payments');
            $totalPaid = $order->payments->where('status', 'completed')->sum('amount');
            
            $order->payment_status = $totalPaid >= $order->total_amount
                ? 'paid'
                : ($totalPaid > 0 ? 'partial' : 'unpaid');
            $order->save();
        });

        return $this->successResponse($order->fresh(), __('Order status updated successfully'));
    }

    public function byExternalId(Request $request, string $externalId): JsonResponse
    {
        $store = $this->getStore($request);

        // BUG-007 FIX: Use reference_number instead of external_reference to match order creation
        $order = Sale::query()
            ->with(['customer', 'items.product'])
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->where('reference_number', $externalId)
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
