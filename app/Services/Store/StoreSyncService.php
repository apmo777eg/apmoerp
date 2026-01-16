<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreSyncLog;
use App\Models\User;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Store\Clients\LaravelClient;
use App\Services\Store\Clients\ShopifyClient;
use App\Services\Store\Clients\WooCommerceClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoreSyncService
{
    public function __construct(protected InventoryServiceInterface $inventory) {}

    public function pullProductsFromShopify(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_PRODUCTS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new ShopifyClient($store);
            $products = $client->getProducts();

            foreach ($products as $product) {
                try {
                    $this->syncShopifyProductToERP($store, $product);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync Shopify product: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pushStockToShopify(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_INVENTORY, StoreSyncLog::DIRECTION_PUSH);

        try {
            $client = new ShopifyClient($store);
            $mappings = ProductStoreMapping::where('store_id', $store->id)->with('product')->get();

            // V25-HIGH-04 FIX: Set branch context before calling currentQty
            // Without branch context, currentQty returns 0.0
            request()->attributes->set('branch_id', $store->branch_id);

            // V25-HIGH-04 FIX: Get default warehouse for the store's branch for more accurate stock
            $warehouseId = $this->getDefaultWarehouseForBranch($store->branch_id);

            foreach ($mappings as $mapping) {
                try {
                    $product = $mapping->product;
                    if ($product) {
                        // Get current stock level from inventory service
                        // V25-HIGH-04 FIX: Pass warehouse_id for warehouse-specific stock
                        $currentQty = $this->inventory->currentQty($product->id, $warehouseId);
                        $client->updateInventory($mapping->external_id, (int) $currentQty);
                        $mapping->markSynced();
                        $log->incrementSuccess();
                    }
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to push stock to Shopify: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullOrdersFromShopify(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_ORDERS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new ShopifyClient($store);
            $orders = $client->getOrders();

            foreach ($orders as $order) {
                try {
                    $this->syncShopifyOrderToERP($store, $order);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync Shopify order: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullProductsFromWooCommerce(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_PRODUCTS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new WooCommerceClient($store);
            $products = $client->getProducts();

            foreach ($products as $product) {
                try {
                    $this->syncWooProductToERP($store, $product);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync WooCommerce product: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pushStockToWooCommerce(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_INVENTORY, StoreSyncLog::DIRECTION_PUSH);

        try {
            $client = new WooCommerceClient($store);
            $mappings = ProductStoreMapping::where('store_id', $store->id)->with('product')->get();

            // V25-HIGH-04 FIX: Set branch context before calling currentQty
            // Without branch context, currentQty returns 0.0
            request()->attributes->set('branch_id', $store->branch_id);

            // V25-HIGH-04 FIX: Get default warehouse for the store's branch for more accurate stock
            $warehouseId = $this->getDefaultWarehouseForBranch($store->branch_id);

            foreach ($mappings as $mapping) {
                try {
                    $product = $mapping->product;
                    if ($product) {
                        // Get current stock level from inventory service
                        // V25-HIGH-04 FIX: Pass warehouse_id for warehouse-specific stock
                        $currentQty = $this->inventory->currentQty($product->id, $warehouseId);
                        $client->updateStock($mapping->external_id, (int) $currentQty);
                        $mapping->markSynced();
                        $log->incrementSuccess();
                    }
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to push stock to WooCommerce: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullOrdersFromWooCommerce(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_ORDERS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new WooCommerceClient($store);
            $orders = $client->getOrders();

            foreach ($orders as $order) {
                try {
                    $this->syncWooOrderToERP($store, $order);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync WooCommerce order: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function handleShopifyProductUpdate(Store $store, array $data): void
    {
        $this->syncShopifyProductToERP($store, $data);
    }

    public function handleShopifyProductDelete(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if ($externalId) {
            ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->delete();
        }
    }

    public function handleShopifyOrderCreate(Store $store, array $data): void
    {
        $this->syncShopifyOrderToERP($store, $data);
    }

    public function handleShopifyOrderUpdate(Store $store, array $data): void
    {
        $this->syncShopifyOrderToERP($store, $data);
    }

    public function handleShopifyInventoryUpdate(Store $store, array $data): void
    {
        $inventoryItemId = $data['inventory_item_id'] ?? null;
        $available = $data['available'] ?? null;

        if ($inventoryItemId && $available !== null) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->whereJsonContains('external_data->inventory_item_id', $inventoryItemId)
                ->first();

            if ($mapping && $mapping->product) {
                // Use inventory service to adjust stock instead of direct update
                request()->attributes->set('branch_id', $store->branch_id);

                // V22-CRIT-02 FIX: Get the default warehouse for the store's branch
                // Inventory adjustments require a warehouse ID
                $warehouseId = $this->getDefaultWarehouseForBranch($store->branch_id);

                if (! $warehouseId) {
                    Log::warning('Shopify inventory update skipped: no default warehouse for branch', [
                        'store_id' => $store->id,
                        'branch_id' => $store->branch_id,
                        'product_id' => $mapping->product->id,
                    ]);

                    return;
                }

                $currentQty = $this->inventory->currentQty($mapping->product->id, $warehouseId);
                $difference = $available - $currentQty;

                if (abs($difference) > 0.001) {
                    $this->inventory->adjust(
                        $mapping->product->id,
                        $difference,
                        $warehouseId,
                        'Shopify inventory webhook update'
                    );
                }
            }
        }
    }

    public function handleWooProductUpdate(Store $store, array $data): void
    {
        $this->syncWooProductToERP($store, $data);
    }

    public function handleWooProductDelete(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if ($externalId) {
            ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->delete();
        }
    }

    public function handleWooOrderCreate(Store $store, array $data): void
    {
        $this->syncWooOrderToERP($store, $data);
    }

    public function handleWooOrderUpdate(Store $store, array $data): void
    {
        $this->syncWooOrderToERP($store, $data);
    }

    protected function syncShopifyProductToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->first();

            $productData = [
                'name' => $data['title'] ?? 'Unknown Product',
                'description' => strip_tags($data['body_html'] ?? ''),
                'sku' => $data['variants'][0]['sku'] ?? 'SHOP-'.$externalId,
                'default_price' => (float) ($data['variants'][0]['price'] ?? 0),
                'branch_id' => $store->branch_id,
            ];

            if ($mapping) {
                $mapping->product->update($productData);
                $mapping->update([
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            } else {
                $product = Product::create($productData);
                ProductStoreMapping::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'external_id' => $externalId,
                    'external_sku' => $data['variants'][0]['sku'] ?? null,
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            }
        });
    }

    protected function syncShopifyOrderToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        // CRITICAL-05 FIX: Add branch_id scoping to idempotency check
        $existingOrder = Sale::where('external_reference', $externalId)
            ->where('channel', 'shopify')
            ->where('branch_id', $store->branch_id)
            ->first();

        if ($existingOrder) {
            $oldStatus = $existingOrder->status;
            $newStatus = $this->mapShopifyOrderStatus($data['financial_status'] ?? 'pending');

            $existingOrder->update([
                'status' => $newStatus,
            ]);

            // V25-HIGH-03 FIX: Dispatch SaleCompleted event when status transitions to completed
            if ($oldStatus !== 'completed' && $newStatus === 'completed' && $existingOrder->warehouse_id) {
                event(new \App\Events\SaleCompleted($existingOrder->fresh('items')));
            }

            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $customerData = $data['customer'] ?? null;
            $customerId = null;

            if ($customerData) {
                // CRITICAL-05 FIX: Add branch_id to customer lookup to prevent cross-branch customer reuse
                $customer = Customer::firstOrCreate(
                    [
                        'email' => $customerData['email'] ?? 'shopify-'.($customerData['id'] ?? '').'@unknown.com',
                        'branch_id' => $store->branch_id,
                    ],
                    [
                        'name' => trim(($customerData['first_name'] ?? '').' '.($customerData['last_name'] ?? '')),
                        'phone' => $customerData['phone'] ?? null,
                    ]
                );
                $customerId = $customer->id;
            }

            // V25-HIGH-03 FIX: Get default warehouse for the store's branch
            $warehouseId = $this->getDefaultWarehouseForBranch($store->branch_id);

            // V25-HIGH-03 FIX: Parse order date from Shopify using Carbon for better error handling
            $orderDate = isset($data['created_at'])
                ? \Carbon\Carbon::parse($data['created_at'])->toDateString()
                : now()->toDateString();

            $status = $this->mapShopifyOrderStatus($data['financial_status'] ?? 'pending');

            // CRITICAL-05 FIX: Use correct schema column names
            // V25-HIGH-03 FIX: Include warehouse_id, sale_date, and created_by
            // V29-HIGH-03 FIX: Include created_by for audit trail
            $sale = Sale::create([
                'branch_id' => $store->branch_id,
                'warehouse_id' => $warehouseId, // V25-HIGH-03 FIX
                'customer_id' => $customerId,
                'sale_date' => $orderDate, // V25-HIGH-03 FIX
                'subtotal' => (float) ($data['subtotal_price'] ?? 0),
                'tax_amount' => (float) ($data['total_tax'] ?? 0),
                'discount_amount' => (float) ($data['total_discounts'] ?? 0),
                'total_amount' => (float) ($data['total_price'] ?? 0),
                'status' => $status,
                'channel' => 'shopify',
                'external_reference' => $externalId,
                'created_by' => $this->getIntegrationUserId(), // V29-HIGH-03 FIX
            ]);

            foreach ($data['line_items'] ?? [] as $lineItem) {
                // V26-MED-07 FIX: Guard against missing product_id in line_items
                // Some Shopify line_items may not have product_id (custom items, gifts, adjustments)
                $externalProductId = $lineItem['product_id'] ?? null;
                if ($externalProductId === null) {
                    Log::warning('Shopify order sync: skipping line item without product_id', [
                        'store_id' => $store->id,
                        'external_order_id' => $externalId,
                        'line_item_name' => $lineItem['name'] ?? null,
                        'sku' => $lineItem['sku'] ?? null,
                    ]);

                    continue;
                }

                $productMapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', (string) $externalProductId)
                    ->first();

                // CRITICAL-05 FIX: Skip items without product mapping to avoid null product_id
                $productId = $productMapping?->product_id;
                if ($productId === null) {
                    Log::warning('Shopify order sync: skipping line item with unmapped product', [
                        'store_id' => $store->id,
                        'external_order_id' => $externalId,
                        'external_product_id' => $externalProductId,
                        'sku' => $lineItem['sku'] ?? null,
                    ]);

                    continue;
                }

                // CRITICAL-05 FIX: Use correct SaleItem schema column names
                // V29-HIGH-03 FIX: Include warehouse_id and cost_price for ERP consistency
                $sale->items()->create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId, // V29-HIGH-03 FIX
                    'quantity' => (int) ($lineItem['quantity'] ?? 1),
                    'unit_price' => (float) ($lineItem['price'] ?? 0),
                    'cost_price' => $this->getProductCostPrice($productId), // V29-HIGH-03 FIX
                    'discount_amount' => (float) ($lineItem['total_discount'] ?? 0),
                    'line_total' => (float) ($lineItem['quantity'] ?? 1) * (float) ($lineItem['price'] ?? 0) - (float) ($lineItem['total_discount'] ?? 0),
                ]);
            }

            // V25-HIGH-03 FIX: Dispatch SaleCompleted event for completed orders to trigger inventory updates
            if ($status === 'completed' && $warehouseId) {
                event(new \App\Events\SaleCompleted($sale->fresh('items')));
            }
        });
    }

    protected function syncWooProductToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->first();

            $productData = [
                'name' => $data['name'] ?? 'Unknown Product',
                'description' => strip_tags($data['description'] ?? ''),
                'sku' => $data['sku'] ?? 'WOO-'.$externalId,
                'default_price' => (float) ($data['price'] ?? 0),
                'branch_id' => $store->branch_id,
            ];

            if ($mapping) {
                $mapping->product->update($productData);
                $mapping->update([
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            } else {
                $product = Product::create($productData);
                ProductStoreMapping::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'external_id' => $externalId,
                    'external_sku' => $data['sku'] ?? null,
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            }
        });
    }

    protected function syncWooOrderToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        // CRITICAL-05 FIX: Add branch_id scoping to idempotency check
        $existingOrder = Sale::where('external_reference', $externalId)
            ->where('channel', 'woocommerce')
            ->where('branch_id', $store->branch_id)
            ->first();

        if ($existingOrder) {
            $oldStatus = $existingOrder->status;
            $newStatus = $this->mapWooOrderStatus($data['status'] ?? 'pending');

            $existingOrder->update([
                'status' => $newStatus,
            ]);

            // V25-HIGH-03 FIX: Dispatch SaleCompleted event when status transitions to completed
            if ($oldStatus !== 'completed' && $newStatus === 'completed' && $existingOrder->warehouse_id) {
                event(new \App\Events\SaleCompleted($existingOrder->fresh('items')));
            }

            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $billing = $data['billing'] ?? [];
            $customerId = null;

            if (! empty($billing['email'])) {
                // CRITICAL-05 FIX: Add branch_id to customer lookup to prevent cross-branch customer reuse
                $customer = Customer::firstOrCreate(
                    [
                        'email' => $billing['email'],
                        'branch_id' => $store->branch_id,
                    ],
                    [
                        'name' => trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')),
                        'phone' => $billing['phone'] ?? null,
                        'address' => ($billing['address_1'] ?? '').' '.($billing['address_2'] ?? ''),
                        'city' => $billing['city'] ?? null,
                        'country' => $billing['country'] ?? null,
                    ]
                );
                $customerId = $customer->id;
            }

            // V25-HIGH-03 FIX: Get default warehouse for the store's branch
            $warehouseId = $this->getDefaultWarehouseForBranch($store->branch_id);

            // V25-HIGH-03 FIX: Parse order date from WooCommerce using Carbon for better error handling
            $orderDate = isset($data['date_created'])
                ? \Carbon\Carbon::parse($data['date_created'])->toDateString()
                : now()->toDateString();

            $status = $this->mapWooOrderStatus($data['status'] ?? 'pending');

            // CRITICAL-05 FIX: Use correct schema column names
            // V25-HIGH-03 FIX: Include warehouse_id and sale_date
            // V29-HIGH-03 FIX: Include created_by for audit trail
            $sale = Sale::create([
                'branch_id' => $store->branch_id,
                'warehouse_id' => $warehouseId, // V25-HIGH-03 FIX
                'customer_id' => $customerId,
                'sale_date' => $orderDate, // V25-HIGH-03 FIX
                'subtotal' => (float) ($data['total'] ?? 0) - (float) ($data['total_tax'] ?? 0),
                'tax_amount' => (float) ($data['total_tax'] ?? 0),
                'discount_amount' => (float) ($data['discount_total'] ?? 0),
                'total_amount' => (float) ($data['total'] ?? 0),
                'status' => $status,
                'channel' => 'woocommerce',
                'external_reference' => $externalId,
                'created_by' => $this->getIntegrationUserId(), // V29-HIGH-03 FIX
            ]);

            foreach ($data['line_items'] ?? [] as $lineItem) {
                // V26-MED-07 FIX: Guard against missing product_id in line_items
                // Some WooCommerce line_items may not have product_id (custom items, fees, adjustments)
                $externalProductId = $lineItem['product_id'] ?? null;
                if ($externalProductId === null) {
                    Log::warning('WooCommerce order sync: skipping line item without product_id', [
                        'store_id' => $store->id,
                        'external_order_id' => $externalId,
                        'line_item_name' => $lineItem['name'] ?? null,
                        'sku' => $lineItem['sku'] ?? null,
                    ]);

                    continue;
                }

                $productMapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', (string) $externalProductId)
                    ->first();

                // CRITICAL-05 FIX: Skip items without product mapping to avoid null product_id
                $productId = $productMapping?->product_id;
                if ($productId === null) {
                    Log::warning('WooCommerce order sync: skipping line item with unmapped product', [
                        'store_id' => $store->id,
                        'external_order_id' => $externalId,
                        'external_product_id' => $externalProductId,
                        'sku' => $lineItem['sku'] ?? null,
                    ]);

                    continue;
                }

                // CRITICAL-05 FIX: Use correct SaleItem schema column names
                // V29-HIGH-03 FIX: Include warehouse_id and cost_price for ERP consistency
                $sale->items()->create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId, // V29-HIGH-03 FIX
                    'quantity' => (int) ($lineItem['quantity'] ?? 1),
                    'unit_price' => (float) ($lineItem['price'] ?? 0),
                    'cost_price' => $this->getProductCostPrice($productId), // V29-HIGH-03 FIX
                    'discount_amount' => 0,
                    'line_total' => (float) ($lineItem['total'] ?? 0),
                ]);
            }

            // V25-HIGH-03 FIX: Dispatch SaleCompleted event for completed orders
            if ($status === 'completed' && $warehouseId) {
                event(new \App\Events\SaleCompleted($sale->fresh('items')));
            }
        });
    }

    // Laravel Store Sync Methods

    public function pullProductsFromLaravel(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_PRODUCTS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new LaravelClient($store);
            $products = $client->getProducts();

            foreach ($products as $product) {
                try {
                    $this->syncLaravelProductToERP($store, $product);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync Laravel product: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pushStockToLaravel(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_INVENTORY, StoreSyncLog::DIRECTION_PUSH);

        try {
            $client = new LaravelClient($store);
            $mappings = ProductStoreMapping::where('store_id', $store->id)->with('product')->get();

            $items = [];
            foreach ($mappings as $mapping) {
                if ($mapping->product) {
                    $currentQty = $this->inventory->currentQty($mapping->product->id);
                    $items[] = [
                        'product_id' => $mapping->external_id,
                        'quantity' => (int) $currentQty,
                        'action' => 'set',
                    ];
                }
            }

            if (! empty($items)) {
                $result = $client->bulkUpdateStock($items);
                if ($result['success'] ?? false) {
                    $log->records_success = $result['updated'] ?? count($items);
                    $log->records_failed = $result['failed'] ?? 0;
                    $log->records_processed = ($log->records_success ?? 0) + ($log->records_failed ?? 0);
                    foreach ($mappings as $mapping) {
                        $mapping->markSynced();
                    }
                } else {
                    $log->records_failed = count($items);
                    $log->records_processed = count($items);
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullOrdersFromLaravel(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_ORDERS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new LaravelClient($store);
            $orders = $client->getOrders();

            foreach ($orders as $order) {
                try {
                    $this->syncLaravelOrderToERP($store, $order);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync Laravel order: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function syncLaravelProductToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->first();

            $productData = [
                'name' => $data['name'] ?? 'Unknown Product',
                'description' => $data['description'] ?? '',
                'sku' => $data['sku'] ?? 'LAR-'.$externalId,
                'default_price' => (float) ($data['default_price'] ?? $data['price'] ?? 0),
                'cost' => (float) ($data['cost'] ?? 0),
                'branch_id' => $store->branch_id,
            ];

            if ($mapping) {
                $mapping->product->update($productData);
                $mapping->update([
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            } else {
                $product = Product::create($productData);
                ProductStoreMapping::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'external_id' => $externalId,
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            }
        });
    }

    public function syncLaravelOrderToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? $data['external_id'] ?? '');
        if (! $externalId) {
            return;
        }

        // CRITICAL-05 FIX: Add branch_id scoping to idempotency check
        $existingOrder = Sale::where('external_reference', $externalId)
            ->where('channel', 'laravel')
            ->where('branch_id', $store->branch_id)
            ->first();

        if ($existingOrder) {
            $existingOrder->update([
                'status' => $data['status'] ?? $existingOrder->status,
            ]);

            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $customerId = null;

            // Try to find or create customer
            $customerData = $data['customer'] ?? [];
            if (! empty($customerData['email'])) {
                // CRITICAL-05 FIX: Add branch_id to customer lookup to prevent cross-branch customer reuse
                $customer = Customer::firstOrCreate(
                    [
                        'email' => $customerData['email'],
                        'branch_id' => $store->branch_id,
                    ],
                    [
                        'name' => $customerData['name'] ?? 'Unknown',
                        'phone' => $customerData['phone'] ?? null,
                        'address' => $customerData['address'] ?? null,
                    ]
                );
                $customerId = $customer->id;
            }

            // CRITICAL-05 FIX: Use correct schema column names
            $sale = Sale::create([
                'branch_id' => $store->branch_id,
                'customer_id' => $customerId,
                'subtotal' => (float) ($data['sub_total'] ?? $data['subtotal'] ?? 0),
                'tax_amount' => (float) ($data['tax_total'] ?? $data['tax'] ?? 0),
                'discount_amount' => (float) ($data['discount_total'] ?? $data['discount'] ?? 0),
                'total_amount' => (float) ($data['grand_total'] ?? $data['total'] ?? 0),
                'status' => $data['status'] ?? 'pending',
                'channel' => 'laravel',
                'external_reference' => $externalId,
            ]);

            foreach ($data['items'] ?? [] as $lineItem) {
                $productMapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', (string) ($lineItem['product_id'] ?? ''))
                    ->first();

                // CRITICAL-05 FIX: Skip items without product mapping to avoid null product_id
                $productId = $productMapping?->product_id;
                if ($productId === null) {
                    Log::warning('Laravel order sync: skipping line item with unmapped product', [
                        'store_id' => $store->id,
                        'external_order_id' => $externalId,
                        'external_product_id' => $lineItem['product_id'] ?? null,
                        'sku' => $lineItem['sku'] ?? null,
                    ]);

                    continue;
                }

                // CRITICAL-05 FIX: Use correct SaleItem schema column names
                $sale->items()->create([
                    'product_id' => $productId,
                    'quantity' => (float) ($lineItem['qty'] ?? $lineItem['quantity'] ?? 1),
                    'unit_price' => (float) ($lineItem['unit_price'] ?? $lineItem['price'] ?? 0),
                    'discount_amount' => (float) ($lineItem['discount'] ?? 0),
                    'line_total' => (float) ($lineItem['line_total'] ?? $lineItem['total'] ?? 0),
                ]);
            }
        });
    }

    // Helper methods

    protected function mapShopifyOrderStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'completed',
            'pending' => 'pending',
            'refunded', 'partially_refunded' => 'refunded',
            'voided' => 'cancelled',
            default => 'pending',
        };
    }

    protected function mapWooOrderStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'processing' => 'processing',
            'on-hold', 'pending' => 'pending',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    protected function createSyncLog(Store $store, string $type, string $direction): StoreSyncLog
    {
        return StoreSyncLog::create([
            'store_id' => $store->id,
            'type' => $type,
            'direction' => $direction,
            'status' => StoreSyncLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * V22-CRIT-02 FIX: Get the default warehouse for a branch
     * This is used for inventory adjustments via webhooks
     */
    protected function getDefaultWarehouseForBranch(?int $branchId): ?int
    {
        if (! $branchId) {
            return null;
        }

        // First try to get the default warehouse for this branch
        $warehouse = \App\Models\Warehouse::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($warehouse) {
            return $warehouse->id;
        }

        // Fall back to any active warehouse in the branch
        $warehouse = \App\Models\Warehouse::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        return $warehouse?->id;
    }

    /**
     * V29-HIGH-03 FIX: Get or create a system user ID for store integrations.
     * 
     * This provides a deterministic user ID for audit trails when syncing orders
     * from external stores (Shopify/WooCommerce). The user is created on first use
     * and cached using Laravel's cache for efficiency.
     * 
     * @return int|null The system user ID, or null if user creation fails
     */
    protected function getIntegrationUserId(): ?int
    {
        // Use Laravel's cache with a long TTL (24 hours) for efficiency
        // This is safe because the integration user email is constant
        return \Illuminate\Support\Facades\Cache::remember(
            'store_sync_integration_user_id',
            now()->addHours(24),
            function () {
                // Try to find existing integration user
                $user = User::withoutGlobalScopes()
                    ->where('email', 'system-integration@apmoerp.local')
                    ->first();
                
                if ($user) {
                    return $user->id;
                }
                
                // Create integration user if it doesn't exist
                // This is a system user for automated processes
                try {
                    $user = User::create([
                        'name' => 'System Integration',
                        'email' => 'system-integration@apmoerp.local',
                        'password' => Hash::make(Str::random(64)), // Use 64 chars for better security
                        'email_verified_at' => now(),
                    ]);
                    return $user->id;
                } catch (\Exception $e) {
                    Log::warning('Failed to create integration user for store sync', [
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            }
        );
    }

    /**
     * V29-HIGH-03 FIX: Get the cost price for a product at the time of sale.
     * 
     * @param int $productId The product ID
     * @return float|null The cost price, or null if product not found
     */
    protected function getProductCostPrice(int $productId): ?float
    {
        $product = Product::withoutGlobalScopes()->find($productId);
        
        if (!$product) {
            return null;
        }
        
        // Use standard_cost if available, otherwise fall back to cost
        return (float) ($product->standard_cost ?? $product->cost ?? 0);
    }
}
