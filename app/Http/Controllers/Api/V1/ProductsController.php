<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ProductStoreMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductsController extends BaseApiController
{
    /**
     * Search products by name, SKU, or barcode for POS terminal.
     * This endpoint is used by the frontend POS system.
     */
    public function search(Request $request, ?int $branchId = null): JsonResponse
    {
        $query = $request->get('q', '');
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $user = auth()->user();

        if (! $user) {
            return $this->errorResponse(__('Authentication required'), 401);
        }

        $this->authorize('viewAny', Product::class);

        $userBranchId = $user->branch_id;

        if ($branchId !== null && $userBranchId !== null && $branchId !== $userBranchId) {
            return $this->errorResponse(__('Unauthorized branch access'), 403);
        }

        $resolvedBranchId = $branchId ?? $userBranchId;

        if (! $resolvedBranchId) {
            return $this->errorResponse(__('Branch context required'), 403);
        }

        if (strlen($query) < 2) {
            return $this->successResponse([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ], __('Search query too short'));
        }

        $productsQuery = Product::query()
            ->when($resolvedBranchId, fn ($q) => $q->where('branch_id', $resolvedBranchId))
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('sku', 'like', '%'.$query.'%')
                    ->orWhere('barcode', 'like', '%'.$query.'%');
            })
            ->when(! $request->filled('status'), fn ($q) => $q->where('status', 'active'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->select('id', 'name', 'sku', 'default_price', 'barcode', 'category_id', 'tax_id');

        $products = $productsQuery->paginate($perPage, ['*'], 'page', $page);

        // Format response to match frontend expectations
        $formattedProducts = $products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'product_id' => $product->id, // Frontend expects both
                'name' => $product->name,
                'label' => $product->name, // Frontend fallback
                'sku' => $product->sku,
                // V51-HIGH-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for prices
                'price' => decimal_float($product->default_price, 4),
                'sale_price' => decimal_float($product->default_price, 4), // Frontend fallback
                'barcode' => $product->barcode,
                'tax_id' => $product->tax_id,
            ];
        });

        return $this->successResponse([
            'data' => $formattedProducts,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ], __('Products found'));
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        if (! $store || ! $store->branch_id) {
            return $this->errorResponse(__('Store authentication required'), 401);
        }

        $validated = $request->validate([
            'sort_by' => 'sometimes|string|in:created_at,id,name,sku,default_price',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        // Clamp per_page to a maximum of 100 to prevent DoS via large requests
        $perPage = min((int) ($validated['per_page'] ?? 50), 100);

        $query = Product::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id)
            )
            ->orderBy($sortBy, $sortDir);

        $products = $query->paginate($perPage);

        return $this->paginatedResponse($products, __('Products retrieved successfully'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        // V22-HIGH-01 FIX: Remove auth()->user() check for store token routes
        // Store token authentication is sufficient; auth user is not required
        if (! $store || ! $store->branch_id) {
            return $this->errorResponse(__('Store authentication required'), 401);
        }

        $product = Product::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $product) {
            return $this->errorResponse(__('Product not found'), 404);
        }

        // V22-HIGH-01 FIX: Skip authorization for store token routes (no auth user)
        // The store token middleware handles access control via abilities

        $product->load(['category']);

        $mapping = null;
        if ($store) {
            $mapping = ProductStoreMapping::where('product_id', $product->id)
                ->where('store_id', $store->id)
                ->first();
        }

        return $this->successResponse([
            'product' => $product,
            'store_mapping' => $mapping,
        ], __('Product retrieved successfully'));
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        if (! $store || ! $store->branch_id) {
            return $this->errorResponse(__('Store authentication required'), 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // NEW-HIGH-04 FIX: Scope SKU uniqueness to branch_id for multi-branch ERP support
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where('branch_id', $store->branch_id),
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            // NEW-MEDIUM-05 FIX: Use numeric validation to support fractional quantities (weight/volume/meters)
            'quantity' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'warehouse_id' => [
                'required_with:quantity',
                Rule::exists('warehouses', 'id')->where('branch_id', $store->branch_id),
            ],
            'barcode' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
            'external_id' => 'nullable|string|max:100',
        ]);

        // Map API fields to database columns
        $validated['default_price'] = $validated['price'];
        unset($validated['price']);
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $quantity = decimal_float($validated['quantity'], 4);
        $warehouseId = $validated['warehouse_id'] ?? null;
        unset($validated['quantity']);

        if (isset($validated['cost_price'])) {
            $validated['cost'] = $validated['cost_price'];
            unset($validated['cost_price']);
        }

        // V9-CRITICAL-01 FIX: Create product without stock_quantity and use stock_movements instead
        $product = new Product($validated);
        $product->branch_id = $store->branch_id;
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $product->created_by = actual_user_id();
        // Keep stock_quantity as cached value but also create stock movement
        $product->stock_quantity = $quantity;
        $product->save();

        // V9-CRITICAL-01 FIX: Create initial stock movement if quantity > 0 and warehouse is specified
        if ($quantity > 0 && $warehouseId) {
            $stockMovementRepo = app(\App\Repositories\Contracts\StockMovementRepositoryInterface::class);
            $stockMovementRepo->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'movement_type' => 'initial_stock',
                'reference_type' => 'product_create',
                'reference_id' => $product->id,
                'qty' => $quantity,
                'direction' => 'in',
                'unit_cost' => $product->cost ?? null,
                'notes' => 'Initial stock via API product creation',
                // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                'created_by' => actual_user_id(),
            ]);
        }

        if ($store && $request->filled('external_id')) {
            ProductStoreMapping::create([
                'product_id' => $product->id,
                'store_id' => $store->id,
                'external_id' => $request->external_id,
                'external_sku' => $request->external_sku ?? $product->sku,
                'last_synced_at' => now(),
            ]);
        }

        return $this->successResponse($product, __('Product created successfully'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        if (! $store || ! $store->branch_id) {
            return $this->errorResponse(__('Store authentication required'), 401);
        }

        $product = Product::query()
            ->when($store->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $product) {
            return $this->errorResponse(__('Product not found'), 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            // V22-HIGH-02 FIX: Scope SKU uniqueness to branch_id for multi-branch ERP support
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id)->where('branch_id', $store->branch_id),
            ],
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            // NEW-MEDIUM-02 FIX: Use numeric validation to support fractional quantities (consistent with store())
            'quantity' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'warehouse_id' => [
                'required_with:quantity',
                Rule::exists('warehouses', 'id')->where('branch_id', $store->branch_id),
            ],
            'barcode' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        // Map API fields to database columns
        if (isset($validated['price'])) {
            $validated['default_price'] = $validated['price'];
            unset($validated['price']);
        }

        if (isset($validated['cost_price'])) {
            $validated['cost'] = $validated['cost_price'];
            unset($validated['cost_price']);
        }

        // V9-CRITICAL-01 FIX: When quantity is updated, create a stock adjustment movement
        // Note: If warehouse_id is not provided, we can only update the cached stock_quantity
        // A stock movement requires a warehouse_id. This is acceptable as stock_quantity serves as
        // a fallback cache when stock_movements isn't fully utilized.
        $warehouseId = $validated['warehouse_id'] ?? null;
        if (array_key_exists('quantity', $validated)) {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $newQuantity = decimal_float($validated['quantity'], 4);

            // Update cached stock_quantity
            $product->stock_quantity = $newQuantity;
            unset($validated['quantity']);

            // Create stock adjustment movement if warehouse is specified
            if ($warehouseId) {
                $currentStock = \App\Services\StockService::getCurrentStock($product->id, $warehouseId);
                $quantityDiff = $newQuantity - $currentStock;

                // Only create movement if there's a meaningful difference
                if (abs($quantityDiff) > 0.0001) {
                    $stockMovementRepo = app(\App\Repositories\Contracts\StockMovementRepositoryInterface::class);
                    $stockMovementRepo->create([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'movement_type' => 'adjustment',
                        'reference_type' => 'product_update',
                        'reference_id' => $product->id,
                        'qty' => abs($quantityDiff),
                        'direction' => $quantityDiff > 0 ? 'in' : 'out',
                        'unit_cost' => $product->cost ?? null,
                        'notes' => 'Stock adjustment via API product update',
                        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
                        'created_by' => actual_user_id(),
                    ]);
                }
            }
        }

        $product->fill($validated);
        // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
        $product->updated_by = actual_user_id();
        $product->save();

        return $this->successResponse($product, __('Product updated successfully'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $this->getStore($request);

        // V22-HIGH-01 FIX: Remove auth()->user() check for store token routes
        // Store token authentication is sufficient; auth user is not required
        if (! $store || ! $store->branch_id) {
            return $this->errorResponse(__('Store authentication required'), 401);
        }

        $product = Product::query()
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->find($id);

        if (! $product) {
            return $this->errorResponse(__('Product not found'), 404);
        }

        // V22-HIGH-01 FIX: Skip authorization for store token routes (no auth user)
        // The store token middleware handles access control via 'products.write' ability

        $product->delete();

        return $this->successResponse(null, __('Product deleted successfully'));
    }

    public function byExternalId(Request $request, string $externalId): JsonResponse
    {
        $store = $this->getStore($request);

        if (! $store) {
            return $this->errorResponse(__('Store authentication required'), 401);
        }

        $mapping = ProductStoreMapping::where('store_id', $store->id)
            ->where('external_id', $externalId)
            ->with('product')
            ->first();

        if (! $mapping || ! $mapping->product) {
            return $this->errorResponse(__('Product not found'), 404);
        }

        return $this->successResponse([
            'product' => $mapping->product,
            'store_mapping' => $mapping,
        ], __('Product retrieved successfully'));
    }
}
