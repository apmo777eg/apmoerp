<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class ApiDocumentation extends Component
{
    public string $activeSection = 'overview';

    public array $sections = [
        'overview' => 'Overview',
        'authentication' => 'Authentication',
        'products' => 'Products',
        'inventory' => 'Inventory',
        'orders' => 'Orders',
        'customers' => 'Customers',
        'webhooks' => 'Webhooks',
        'errors' => 'Error Handling',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('api.docs.view')) {
            // Allow access if user has any store permission
            if (! $user || ! $user->can('stores.view')) {
                abort(403);
            }
        }
    }

    public function switchSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function getEndpointsProperty(): array
    {
        $commonHeaders = [
            'Authorization' => 'Bearer {STORE_API_TOKEN}',
            'Accept' => 'application/json',
        ];

        return [
            'products' => [
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/products',
                    'description' => 'List products (requires: products.read)',
                    'headers' => $commonHeaders,
                    'params' => [
                        'per_page' => 'Items per page (default: 50, max: 100)',
                        'page' => 'Page number',
                        'search' => 'Search by name, SKU, or barcode',
                        'category_id' => 'Filter by category ID',
                        'sort_by' => 'Sort by: created_at, name, sku, price, stock_quantity',
                        'sort_dir' => 'Sort direction: asc, desc',
                    ],
                    'response' => <<<'JSON'
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [ { "id": 1, "name": "Sample Product", "sku": "SKU-001" } ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 50, "total": 1, "from": 1, "to": 1 },
  "links": { "first": "...", "last": "...", "prev": null, "next": null }
}
JSON,
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/products',
                    'description' => 'Create a product (requires: products.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'name' => 'required|string|max:255',
                        'sku' => 'required|string|max:100 (unique within branch)',
                        'description' => 'nullable|string',
                        'price' => 'required|numeric|min:0',
                        'cost_price' => 'nullable|numeric|min:0',
                        'quantity' => 'required|numeric|min:0',
                        'warehouse_id' => 'required_with:quantity|exists:warehouses,id (branch-scoped)',
                        'category_id' => 'nullable|exists:product_categories,id (branch-scoped)',
                        'barcode' => 'nullable|string|max:100',
                        'unit' => 'nullable|string|max:50',
                        'min_stock' => 'nullable|integer|min:0',
                        'external_id' => 'nullable|string|max:100',
                    ],
                    'response' => '{"success": true, "message": "Product created successfully", "data": {"id": 1, "name": "Sample Product"}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/products/{id}',
                    'description' => 'Get product by ID (requires: products.read)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Product retrieved successfully", "data": {"product": {"id": 1}, "store_mapping": {"external_id": "123"}}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/products/external/{externalId}',
                    'description' => 'Get product by external ID mapping (requires: products.read)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Product retrieved successfully", "data": {"product": {"id": 1}, "store_mapping": {"external_id": "123"}}}',
                ],
                [
                    'method' => 'PUT',
                    'endpoint' => '/api/v1/products/{id}',
                    'description' => 'Update a product (requires: products.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'name' => 'sometimes|string|max:255',
                        'sku' => 'sometimes|string|max:100 (unique within branch)',
                        'description' => 'nullable|string',
                        'price' => 'sometimes|numeric|min:0',
                        'cost_price' => 'nullable|numeric|min:0',
                        'quantity' => 'sometimes|numeric|min:0',
                        'warehouse_id' => 'required_with:quantity|exists:warehouses,id (branch-scoped)',
                        'category_id' => 'nullable|exists:product_categories,id (branch-scoped)',
                        'barcode' => 'nullable|string|max:100',
                        'unit' => 'nullable|string|max:50',
                        'min_stock' => 'nullable|integer|min:0',
                    ],
                    'response' => '{"success": true, "message": "Product updated successfully", "data": {"id": 1}}',
                ],
                [
                    'method' => 'DELETE',
                    'endpoint' => '/api/v1/products/{id}',
                    'description' => 'Delete a product (requires: products.write)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Product deleted successfully", "data": null}',
                ],
            ],
            'inventory' => [
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/inventory/stock',
                    'description' => 'Get current stock levels (requires: inventory.read)',
                    'headers' => $commonHeaders,
                    'params' => [
                        'sku' => 'Filter by product SKU',
                        'warehouse_id' => 'Filter by warehouse ID (branch-scoped)',
                        'low_stock' => 'Only low stock items (true/false)',
                        'per_page' => 'Items per page (default: 50, max: 100)',
                    ],
                    'response' => '{"success": true, "message": "Stock levels retrieved successfully", "data": [...], "meta": {...}, "links": {...}}',
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/inventory/update-stock',
                    'description' => 'Update stock for one product (requires: inventory.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'product_id' => 'required_without:external_id|integer (branch-scoped)',
                        'external_id' => 'required_without:product_id|string',
                        'qty' => 'required|numeric',
                        'direction' => 'required|in:in,out,set',
                        'reason' => 'nullable|string|max:255',
                        'warehouse_id' => 'nullable|integer (branch-scoped)',
                    ],
                    'response' => '{"success": true, "message": "Stock updated successfully", "data": {"product_id": 1, "old_quantity": 5, "new_quantity": 10}}',
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/inventory/bulk-update-stock',
                    'description' => 'Bulk stock update (requires: inventory.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'updates' => 'required|array|min:1',
                        'updates.*.product_id' => 'required_without:updates.*.external_id|integer (branch-scoped)',
                        'updates.*.external_id' => 'required_without:updates.*.product_id|string',
                        'updates.*.qty' => 'required|numeric',
                        'updates.*.direction' => 'required|in:in,out,set',
                        'updates.*.reason' => 'nullable|string|max:255',
                        'updates.*.warehouse_id' => 'nullable|integer (branch-scoped)',
                    ],
                    'response' => '{"success": true, "message": "Bulk stock update completed", "data": {"success": [...], "failed": [...]}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/inventory/movements',
                    'description' => 'Get stock movement history (requires: inventory.read)',
                    'headers' => $commonHeaders,
                    'params' => [
                        'product_id' => 'Filter by product ID',
                        'warehouse_id' => 'Filter by warehouse ID',
                        'direction' => 'Filter by direction (in/out)',
                        'start_date' => 'Start date (Y-m-d)',
                        'end_date' => 'End date (Y-m-d)',
                        'per_page' => 'Items per page (default: 50, max: 100)',
                    ],
                    'response' => '{"success": true, "message": "Stock movements retrieved successfully", "data": [...], "meta": {...}, "links": {...}}',
                ],
            ],
            'orders' => [
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/orders',
                    'description' => 'List orders (requires: orders.read)',
                    'headers' => $commonHeaders,
                    'params' => [
                        'per_page' => 'Items per page (default: 50, max: 100)',
                        'sort_by' => 'Sort by: created_at, sale_date, id, status, total_amount',
                        'sort_dir' => 'Sort direction: asc, desc',
                        'status' => 'Filter by status',
                        'customer_id' => 'Filter by customer ID',
                        'from_date' => 'Start date (Y-m-d)',
                        'to_date' => 'End date (Y-m-d)',
                    ],
                    'response' => '{"success": true, "message": "Orders retrieved successfully", "data": [...], "meta": {...}, "links": {...}}',
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/orders',
                    'description' => 'Create an order (requires: orders.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'customer_id' => 'nullable|exists:customers,id (branch-scoped)',
                        'customer' => 'nullable|array (create/find customer if customer_id is not provided)',
                        'customer.name' => 'required_with:customer|string|max:255',
                        'customer.email' => 'nullable|required_without:customer.phone|email|max:255',
                        'customer.phone' => 'nullable|required_without:customer.email|string|max:50',
                        'items' => 'required|array|min:1',
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
                        'order_date' => 'nullable|date',
                        'warehouse_id' => 'nullable|exists:warehouses,id (branch-scoped)',
                    ],
                    'response' => '{"success": true, "message": "Order created successfully", "data": {"id": 1, "status": "draft"}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/orders/{id}',
                    'description' => 'Get order by ID (requires: orders.read)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Order retrieved successfully", "data": {"id": 1, "items": [...]}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/orders/external/{externalId}',
                    'description' => 'Get order by external ID (requires: orders.read)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Order retrieved successfully", "data": {"id": 1}}',
                ],
                [
                    'method' => 'PATCH',
                    'endpoint' => '/api/v1/orders/{id}/status',
                    'description' => 'Update order status (requires: orders.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'status' => 'required|in:draft,pending,processing,completed,cancelled,refunded',
                    ],
                    'response' => '{"success": true, "message": "Order status updated successfully", "data": {"id": 1, "status": "processing"}}',
                ],
            ],
            'customers' => [
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/customers',
                    'description' => 'List customers (requires: customers.read)',
                    'headers' => $commonHeaders,
                    'params' => [
                        'per_page' => 'Items per page (default: 50, max: 100)',
                        'sort_by' => 'Sort by: created_at, id, name, email',
                        'sort_dir' => 'Sort direction: asc, desc',
                        'search' => 'Search by name, email, or phone',
                    ],
                    'response' => '{"success": true, "message": "Customers retrieved successfully", "data": [...], "meta": {...}, "links": {...}}',
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/customers',
                    'description' => 'Create a customer (requires: customers.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'name' => 'required|string|max:255',
                        'email' => 'nullable|email|max:255 (unique within branch)',
                        'phone' => 'nullable|string|max:50 (unique within branch)',
                        'address' => 'nullable|string|max:500',
                        'city' => 'nullable|string|max:100',
                        'country' => 'nullable|string|max:100',
                        'company' => 'nullable|string|max:255',
                        'tax_number' => 'nullable|string|max:100',
                        'notes' => 'nullable|string',
                        'external_id' => 'nullable|string|max:100',
                    ],
                    'response' => '{"success": true, "message": "Customer created successfully", "data": {"id": 1, "name": "..."}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/customers/{id}',
                    'description' => 'Get customer by ID (requires: customers.read)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Customer retrieved successfully", "data": {"id": 1, "name": "..."}}',
                ],
                [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/customers/email/{email}',
                    'description' => 'Find customer by email (requires: customers.read)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Customer retrieved successfully", "data": {"id": 1}}',
                ],
                [
                    'method' => 'PUT',
                    'endpoint' => '/api/v1/customers/{id}',
                    'description' => 'Update a customer (requires: customers.write)',
                    'headers' => $commonHeaders,
                    'body' => [
                        'name' => 'sometimes|string|max:255',
                        'email' => 'nullable|email|max:255 (unique within branch)',
                        'phone' => 'nullable|string|max:50 (unique within branch)',
                        'address' => 'nullable|string|max:500',
                        'city' => 'nullable|string|max:100',
                        'country' => 'nullable|string|max:100',
                        'company' => 'nullable|string|max:255',
                        'tax_number' => 'nullable|string|max:100',
                        'notes' => 'nullable|string',
                    ],
                    'response' => '{"success": true, "message": "Customer updated successfully", "data": {"id": 1}}',
                ],
                [
                    'method' => 'DELETE',
                    'endpoint' => '/api/v1/customers/{id}',
                    'description' => 'Delete a customer (requires: customers.write)',
                    'headers' => $commonHeaders,
                    'response' => '{"success": true, "message": "Customer deleted successfully", "data": null}',
                ],
            ],
            'webhooks' => [
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/webhooks/shopify/{storeId}',
                    'description' => 'Shopify webhook endpoint',
                    'headers' => [
                        'X-Shopify-Topic' => 'Webhook topic',
                        'X-Shopify-Hmac-Sha256' => 'HMAC signature for verification',
                        'X-Shopify-Webhook-Id' => 'Delivery ID (idempotency)',
                        'X-Shopify-Triggered-At' => 'Timestamp (freshness window)',
                    ],
                    'topics' => ['products/create', 'products/update', 'products/delete', 'orders/create', 'orders/updated', 'inventory_levels/update'],
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/webhooks/woocommerce/{storeId}',
                    'description' => 'WooCommerce webhook endpoint',
                    'headers' => [
                        'X-WC-Webhook-Topic' => 'Webhook topic',
                        'X-WC-Webhook-Signature' => 'HMAC signature for verification',
                        'X-WC-Webhook-Delivery-ID' => 'Delivery ID (idempotency)',
                        'X-WC-Webhook-Timestamp' => 'Timestamp (freshness window)',
                    ],
                    'topics' => ['product.created', 'product.updated', 'product.deleted', 'order.created', 'order.updated'],
                ],
                [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/webhooks/laravel/{storeId}',
                    'description' => 'Laravel store webhook endpoint',
                    'headers' => [
                        'X-Webhook-Signature' => 'HMAC SHA-256 signature (hex)',
                        'X-Webhook-Id' => 'Delivery ID (idempotency)',
                        'X-Webhook-Timestamp' => 'Timestamp (freshness window)',
                    ],
                    'topics' => ['product.created', 'product.updated', 'product.deleted', 'order.created', 'order.updated', 'inventory.updated'],
                    'response' => '{"success": true, "message": "Webhook processed successfully", "data": null}',
                 ],
             ],
         ];
     }


    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.api-documentation', [
            'endpoints' => $this->endpoints,
        ]);
    }
}
