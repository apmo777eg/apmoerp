<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Store;
use App\Services\BranchContextManager;
use App\Services\Store\StoreSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhooksController extends BaseApiController
{
    protected array $shopifyAllowedTopics = [
        'products/create', 'products/update', 'products/delete',
        'orders/create', 'orders/updated', 'inventory_levels/update',
    ];

    public function __construct(
        protected StoreSyncService $syncService
    ) {}

    public function handleShopify(Request $request, int $storeId): JsonResponse
    {
        // V22-CRIT-01 FIX: Load Store without BranchScope since webhooks don't have auth user
        $store = Store::withoutGlobalScopes()->with('integration')->find($storeId);

        if (! $store || ! $store->is_active || $store->type !== 'shopify') {
            return $this->errorResponse(__('Store not found or not active'), 404);
        }

        // V31-CRIT-02 FIX: Use try/finally to ensure BranchContext is always cleared
        // This prevents context leakage in long-running servers (Octane/RoadRunner)
        try {
            // V22-CRIT-01 FIX: Set branch context from the store's branch_id
            if ($store->branch_id) {
                BranchContextManager::setBranchContext($store->branch_id);
                request()->attributes->set('branch_id', $store->branch_id);
            }

            if (! $this->verifyShopifyWebhook($request, $store)) {
                return $this->errorResponse(__('Invalid webhook signature'), 401);
            }

            $topic = $request->header('X-Shopify-Topic');
            $data = $request->all();

            try {
                match ($topic) {
                    'products/create', 'products/update' => $this->syncService->handleShopifyProductUpdate($store, $data),
                    'products/delete' => $this->syncService->handleShopifyProductDelete($store, $data),
                    'orders/create' => $this->syncService->handleShopifyOrderCreate($store, $data),
                    'orders/updated' => $this->syncService->handleShopifyOrderUpdate($store, $data),
                    'inventory_levels/update' => $this->syncService->handleShopifyInventoryUpdate($store, $data),
                    default => null,
                };

                return $this->successResponse(null, __('Webhook processed successfully'));
            } catch (\Exception $e) {
                // BUG-006 FIX: Log detailed error server-side, return generic message to client
                Log::error('Shopify webhook processing failed', [
                    'store_id' => $storeId,
                    'topic' => $topic,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->errorResponse(__('Webhook processing failed'), 500);
            }
        } finally {
            // V31-CRIT-02 FIX: Always clear branch context to prevent leakage
            BranchContextManager::clearBranchContext();
        }
    }

    public function handleWooCommerce(Request $request, int $storeId): JsonResponse
    {
        // V22-CRIT-01 FIX: Load Store without BranchScope since webhooks don't have auth user
        $store = Store::withoutGlobalScopes()->with('integration')->find($storeId);

        if (! $store || ! $store->is_active || $store->type !== 'woocommerce') {
            return $this->errorResponse(__('Store not found or not active'), 404);
        }

        // V31-CRIT-02 FIX: Use try/finally to ensure BranchContext is always cleared
        // This prevents context leakage in long-running servers (Octane/RoadRunner)
        try {
            // V22-CRIT-01 FIX: Set branch context from the store's branch_id
            if ($store->branch_id) {
                BranchContextManager::setBranchContext($store->branch_id);
                request()->attributes->set('branch_id', $store->branch_id);
            }

            if (! $this->verifyWooCommerceWebhook($request, $store)) {
                return $this->errorResponse(__('Invalid webhook signature'), 401);
            }

            $topic = $request->header('X-WC-Webhook-Topic');
            $data = $request->all();

            try {
                match ($topic) {
                    'product.created', 'product.updated' => $this->syncService->handleWooProductUpdate($store, $data),
                    'product.deleted' => $this->syncService->handleWooProductDelete($store, $data),
                    'order.created' => $this->syncService->handleWooOrderCreate($store, $data),
                    'order.updated' => $this->syncService->handleWooOrderUpdate($store, $data),
                    default => null,
                };

                return $this->successResponse(null, __('Webhook processed successfully'));
            } catch (\Exception $e) {
                // BUG-006 FIX: Log detailed error server-side, return generic message to client
                Log::error('WooCommerce webhook processing failed', [
                    'store_id' => $storeId,
                    'topic' => $topic,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->errorResponse(__('Webhook processing failed'), 500);
            }
        } finally {
            // V31-CRIT-02 FIX: Always clear branch context to prevent leakage
            BranchContextManager::clearBranchContext();
        }
    }

    protected function verifyShopifyWebhook(Request $request, Store $store): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $deliveryId = $request->header('X-Shopify-Webhook-Id');
        $timestamp = $request->header('X-Shopify-Triggered-At') ?? $request->header('X-Shopify-Webhook-Created-At');
        $topic = $request->header('X-Shopify-Topic');
        $secret = $store->integration?->webhook_secret;

        if (! $hmacHeader || ! $secret) {
            return false;
        }

        if ($topic && ! in_array($topic, $this->shopifyAllowedTopics, true)) {
            return false;
        }

        $calculatedHmac = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! hash_equals($hmacHeader, $calculatedHmac)) {
            return false;
        }

        if (! $this->isFresh($timestamp)) {
            return false;
        }

        return $this->reserveDelivery($deliveryId, $store->id);
    }

    protected function verifyWooCommerceWebhook(Request $request, Store $store): bool
    {
        $signature = $request->header('X-WC-Webhook-Signature');
        $deliveryId = $request->header('X-WC-Webhook-Delivery-ID');
        $timestamp = $request->header('X-WC-Webhook-Timestamp');
        $secret = $store->integration?->webhook_secret;

        if (! $signature || ! $secret) {
            return false;
        }

        $calculatedSignature = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! hash_equals($signature, $calculatedSignature)) {
            return false;
        }

        if (! $this->isFresh($timestamp)) {
            return false;
        }

        return $this->reserveDelivery($deliveryId, $store->id);
    }

    /**
     * Handle Laravel store webhook
     */
    public function handleLaravel(Request $request, int $storeId): JsonResponse
    {
        // V22-CRIT-01 FIX: Load Store without BranchScope since webhooks don't have auth user
        $store = Store::withoutGlobalScopes()->with('integration')->find($storeId);

        if (! $store || ! $store->is_active || $store->type !== 'laravel') {
            return $this->errorResponse(__('Store not found or not active'), 404);
        }

        // V31-CRIT-02 FIX: Use try/finally to ensure BranchContext is always cleared
        // This prevents context leakage in long-running servers (Octane/RoadRunner)
        try {
            // V22-CRIT-01 FIX: Set branch context from the store's branch_id
            if ($store->branch_id) {
                BranchContextManager::setBranchContext($store->branch_id);
                request()->attributes->set('branch_id', $store->branch_id);
            }

            if (! $this->verifyLaravelWebhook($request, $store)) {
                return $this->errorResponse(__('Invalid webhook signature'), 401);
            }

            $event = $request->input('event');
            $data = $request->input('data', []);

            try {
                match ($event) {
                    'product.created', 'product.updated' => $this->syncService->syncLaravelProductToERP($store, $data),
                    'product.deleted' => $this->handleLaravelProductDelete($store, $data),
                    'order.created' => $this->syncService->syncLaravelOrderToERP($store, $data),
                    'order.updated' => $this->syncService->syncLaravelOrderToERP($store, $data),
                    'inventory.updated' => $this->handleLaravelInventoryUpdate($store, $data),
                    default => null,
                };

                return $this->successResponse(null, __('Webhook processed successfully'));
            } catch (\Exception $e) {
                // BUG-006 FIX: Log detailed error server-side, return generic message to client
                Log::error('Laravel webhook processing failed', [
                    'store_id' => $storeId,
                    'event' => $event,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->errorResponse(__('Webhook processing failed'), 500);
            }
        } finally {
            // V31-CRIT-02 FIX: Always clear branch context to prevent leakage
            BranchContextManager::clearBranchContext();
        }
    }

    protected function verifyLaravelWebhook(Request $request, Store $store): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $deliveryId = $request->header('X-Webhook-Id');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $secret = $store->integration?->webhook_secret;

        if (! $signature || ! $secret) {
            return false;
        }

        $calculatedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($signature, $calculatedSignature)) {
            return false;
        }

        if (! $this->isFresh($timestamp)) {
            return false;
        }

        return $this->reserveDelivery($deliveryId, $store->id);
    }

    protected function handleLaravelProductDelete(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if ($externalId) {
            \App\Models\ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->delete();
        }
    }

    protected function handleLaravelInventoryUpdate(Store $store, array $data): void
    {
        $productId = $data['product_id'] ?? null;
        $quantity = $data['quantity'] ?? null;

        if ($productId && $quantity !== null) {
            $mapping = \App\Models\ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', (string) $productId)
                ->first();

            if ($mapping && $mapping->product) {
                $inventoryService = app(\App\Services\Contracts\InventoryServiceInterface::class);
                request()->attributes->set('branch_id', $store->branch_id);

                // V22-CRIT-02 FIX: Get the default warehouse for the store's branch
                $warehouseId = $this->getDefaultWarehouseForBranch($store->branch_id);

                if (! $warehouseId) {
                    Log::warning('Laravel inventory update skipped: no default warehouse for branch', [
                        'store_id' => $store->id,
                        'branch_id' => $store->branch_id,
                        'product_id' => $mapping->product->id,
                    ]);

                    return;
                }

                $currentQty = $inventoryService->currentQty($mapping->product->id, $warehouseId);
                $difference = $quantity - $currentQty;

                if ($difference != 0) {
                    $inventoryService->adjust(
                        $mapping->product->id,
                        $difference,
                        $warehouseId,
                        'Laravel store inventory webhook update'
                    );
                }
            }
        }
    }

    /**
     * V22-CRIT-02 FIX: Get the default warehouse for a branch
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

    protected function isFresh(?string $timestamp, int $allowedSkewSeconds = 180): bool
    {
        if (! $timestamp) {
            return false;
        }

        try {
            $time = Carbon::parse($timestamp);
        } catch (\Exception) {
            return false;
        }

        return abs(now()->diffInSeconds($time, false)) <= $allowedSkewSeconds;
    }

    protected function reserveDelivery(?string $deliveryId, int $storeId): bool
    {
        if (! $deliveryId) {
            return false;
        }

        return Cache::add('webhook_delivery_'.$storeId.'_'.$deliveryId, true, now()->addHours(24));
    }
}
