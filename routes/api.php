<?php

use App\Http\Controllers\Api\V1\CustomersController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrdersController;
use App\Http\Controllers\Api\V1\POSController;
use App\Http\Controllers\Api\V1\ProductsController;
use App\Http\Controllers\Api\V1\WebhooksController;
use App\Http\Controllers\Internal\DiagnosticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', function () {
        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'message' => 'API is running',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Internal diagnostics endpoint
    Route::prefix('internal')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/diagnostics', [DiagnosticsController::class, 'index']);
    });

    // Branch-scoped API routes with model binding and full middleware stack
    Route::prefix('branches/{branch}')
        ->middleware(['api-core', 'api-auth', 'api-branch', 'throttle:120,1'])
        ->scopeBindings()
        ->group(function () {
            // Load all branch-specific route files
            require __DIR__.'/api/branch/common.php';
            require __DIR__.'/api/branch/hrm.php';
            require __DIR__.'/api/branch/motorcycle.php';
            require __DIR__.'/api/branch/rental.php';
            require __DIR__.'/api/branch/spares.php';
            require __DIR__.'/api/branch/wood.php';

            // Authenticated POS session management routes (consolidated into branch scope)
            Route::prefix('pos')->group(function () {
                Route::get('/session', [POSController::class, 'getCurrentSession']);
                Route::post('/session/open', [POSController::class, 'openSession']);
                Route::post('/session/{session}/close', [POSController::class, 'closeSession']);
                Route::get('/session/{session}/report', [POSController::class, 'getSessionReport']);
            });
        });

    Route::middleware(['throttle:api'])->group(function () {
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductsController::class, 'index'])->middleware('store.token:products.read');
            Route::post('/', [ProductsController::class, 'store'])->middleware('store.token:products.write');
            Route::get('/external/{externalId}', [ProductsController::class, 'byExternalId'])->middleware('store.token:products.read');
            Route::get('/{id}', [ProductsController::class, 'show'])->middleware('store.token:products.read');
            Route::put('/{id}', [ProductsController::class, 'update'])->middleware('store.token:products.write');
            Route::delete('/{id}', [ProductsController::class, 'destroy'])->middleware('store.token:products.write');
        });

        Route::prefix('inventory')->group(function () {
            Route::get('/stock', [InventoryController::class, 'getStock'])->middleware('store.token:inventory.read');
            Route::post('/update-stock', [InventoryController::class, 'updateStock'])->middleware('store.token:inventory.write');
            Route::post('/bulk-update-stock', [InventoryController::class, 'bulkUpdateStock'])->middleware('store.token:inventory.write');
            Route::get('/movements', [InventoryController::class, 'getMovements'])->middleware('store.token:inventory.read');
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrdersController::class, 'index'])->middleware('store.token:orders.read');
            Route::post('/', [OrdersController::class, 'store'])->middleware('store.token:orders.write');
            Route::get('/external/{externalId}', [OrdersController::class, 'byExternalId'])->middleware('store.token:orders.read');
            Route::get('/{id}', [OrdersController::class, 'show'])->middleware('store.token:orders.read');
            Route::patch('/{id}/status', [OrdersController::class, 'updateStatus'])->middleware('store.token:orders.write');
        });

        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomersController::class, 'index'])->middleware('store.token:customers.read');
            Route::post('/', [CustomersController::class, 'store'])->middleware('store.token:customers.write');
            Route::get('/email/{email}', [CustomersController::class, 'byEmail'])->middleware('store.token:customers.read');
            Route::get('/{id}', [CustomersController::class, 'show'])->middleware('store.token:customers.read');
            Route::put('/{id}', [CustomersController::class, 'update'])->middleware('store.token:customers.write');
            Route::delete('/{id}', [CustomersController::class, 'destroy'])->middleware('store.token:customers.write');
        });
    });

    // V31-CRIT-02 FIX: Add api-core middleware to webhooks for consistent API behavior
    // (JSON validation, headers, ClearBranchContext) across all API endpoints
    Route::prefix('webhooks')->middleware(['api-core', 'throttle:30,1'])->group(function () {
        Route::post('/shopify/{storeId}', [WebhooksController::class, 'handleShopify'])->name('webhooks.shopify');
        Route::post('/woocommerce/{storeId}', [WebhooksController::class, 'handleWooCommerce'])->name('webhooks.woocommerce');
        Route::post('/laravel/{storeId}', [WebhooksController::class, 'handleLaravel'])->name('webhooks.laravel');
    });

    // Auth routes (public routes + authenticated routes)
    Route::middleware(['api-core', 'throttle:api'])->group(function () {
        require __DIR__.'/api/auth.php';
    });

    // Notifications and file upload routes (requires api-core + api-auth)
    Route::middleware(['api-core', 'api-auth', 'impersonate'])->group(function () {
        require __DIR__.'/api/notifications.php';
        require __DIR__.'/api/files.php';
    });

    // Admin routes (requires api-core + api-auth + impersonate)
    Route::middleware(['api-core', 'api-auth', 'impersonate'])->group(function () {
        require __DIR__.'/api/admin.php';
    });

});
