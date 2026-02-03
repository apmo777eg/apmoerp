<?php

use App\Http\Controllers\Admin\Store\StoreOrdersExportController;
use App\Livewire\Accounting\Index as AccountingIndexPage;
use App\Livewire\Admin\Branches\Form as BranchFormPage;
use App\Livewire\Admin\Branches\Index as BranchesIndexPage;
use App\Livewire\Admin\Logs\Audit as AuditLogPage;
use App\Livewire\Admin\Modules\Form as ModuleFormPage;
use App\Livewire\Admin\Modules\Index as ModulesIndexPage;
use App\Livewire\Admin\Reports\InventoryChartsDashboard;
use App\Livewire\Admin\Reports\PosChartsDashboard;
use App\Livewire\Admin\Reports\ReportTemplatesManager;
use App\Livewire\Admin\Reports\ScheduledReportsManager;
use App\Livewire\Admin\Roles\Form as RoleFormPage;
use App\Livewire\Admin\Roles\Index as RolesIndexPage;
use App\Livewire\Admin\Settings\UnifiedSettings;
use App\Livewire\Admin\Users\Form as UserFormPage;
use App\Livewire\Admin\Users\Index as UsersIndexPage;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login as LoginPage;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Auth\TwoFactorSetup;
use App\Livewire\Customers\Form as CustomerFormPage;
use App\Livewire\Customers\Index as CustomersIndexPage;
use App\Livewire\Dashboard\CustomizableDashboard;
use App\Livewire\Dashboard\Index as DashboardPage;
use App\Livewire\Expenses\Form as ExpenseFormPage;
use App\Livewire\Expenses\Index as ExpensesIndexPage;
use App\Livewire\Hrm\Attendance\Index as HrmAttendanceIndex;
use App\Livewire\Hrm\Employees\Form as HrmEmployeeForm;
use App\Livewire\Hrm\Employees\Index as HrmEmployeesIndex;
use App\Livewire\Hrm\Payroll\Index as HrmPayrollIndex;
use App\Livewire\Hrm\Payroll\Run as HrmPayrollRun;
use App\Livewire\Hrm\Reports\Dashboard as HrmReportsDashboard;
use App\Livewire\Income\Index as IncomeIndexPage;
use App\Livewire\Inventory\Products\Form as ProductFormPage;
use App\Livewire\Inventory\Products\Index as ProductsIndexPage;
use App\Livewire\Notifications\Center as NotificationsCenter;
use App\Livewire\Pos\Reports\OfflineSales as PosOfflineSalesPage;
use App\Livewire\Pos\Terminal as PosTerminalPage;
use App\Livewire\Profile\Edit as ProfileEditPage;
use App\Livewire\Purchases\Index as PurchasesIndexPage;
use App\Livewire\Rental\Contracts\Form as RentalContractForm;
use App\Livewire\Rental\Contracts\Index as RentalContractsIndex;
use App\Livewire\Rental\Reports\Dashboard as RentalReportsDashboard;
use App\Livewire\Rental\Units\Form as RentalUnitForm;
use App\Livewire\Rental\Units\Index as RentalUnitsIndex;
use App\Livewire\Sales\Index as SalesIndexPage;
use App\Livewire\Suppliers\Form as SupplierFormPage;
use App\Livewire\Suppliers\Index as SuppliersIndexPage;
use App\Livewire\Warehouse\Index as WarehouseIndexPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes - Modular Structure
|--------------------------------------------------------------------------
|
| Routes organized under /app/{module} pattern for business modules
| Admin, settings, and reports under /admin/*
|
*/

// Root redirect
Route::get('/', function () {
    if (auth()->guest()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    // Determine dashboard permission from config
    $dashboardPermission = config('screen_permissions.dashboard', 'dashboard.view');

    // Check if user can access dashboard first
    if ($user->can($dashboardPermission)) {
        return redirect()->intended(route('dashboard'));
    }

    // If not, find the first accessible module
    $moduleRoutes = [
        ['permission' => 'pos.use', 'route' => 'pos.terminal'],
        ['permission' => 'sales.view', 'route' => 'app.sales.index'],
        ['permission' => 'purchases.view', 'route' => 'app.purchases.index'],
        ['permission' => 'inventory.products.view', 'route' => 'app.inventory.products.index'],
        ['permission' => 'spares.compatibility.manage', 'route' => 'app.inventory.vehicle-models.index'],
        ['permission' => 'warehouse.view', 'route' => 'app.warehouse.index'],
        ['permission' => 'customers.view', 'route' => 'customers.index'],
        ['permission' => 'suppliers.view', 'route' => 'suppliers.index'],
        ['permission' => 'expenses.view', 'route' => 'app.expenses.index'],
        ['permission' => 'income.view', 'route' => 'app.income.index'],
        ['permission' => 'accounting.view', 'route' => 'app.accounting.index'],
        ['permission' => 'hrm.employees.view', 'route' => 'app.hrm.employees.index'],
        ['permission' => 'rental.units.view', 'route' => 'app.rental.units.index'],
        ['permission' => 'manufacturing.view', 'route' => 'app.manufacturing.boms.index'],
        ['permission' => 'banking.view', 'route' => 'app.banking.index'],
        ['permission' => 'fixed-assets.view', 'route' => 'app.fixed-assets.index'],
        ['permission' => 'projects.view', 'route' => 'app.projects.index'],
        ['permission' => 'documents.view', 'route' => 'app.documents.index'],
        ['permission' => 'helpdesk.view', 'route' => 'app.helpdesk.index'],
        ['permission' => 'reports.view', 'route' => 'admin.reports.index'],
        ['permission' => 'settings.view', 'route' => 'admin.settings'],
        ['permission' => 'users.manage', 'route' => 'admin.users.index'],
        ['permission' => 'roles.manage', 'route' => 'admin.roles.index'],
        ['permission' => 'branches.view', 'route' => 'admin.branches.index'],
        ['permission' => 'modules.manage', 'route' => 'admin.modules.index'],
        ['permission' => 'stores.view', 'route' => 'admin.stores.index'],
    ];

    foreach ($moduleRoutes as $module) {
        if ($user->can($module['permission'])) {
            return redirect()->intended(route($module['route']));
        }
    }

    // Fallback to profile if user has no module permissions
    return redirect()->intended(route('profile.edit'));
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]);
});

// CSRF token refresh endpoint to prevent 419 errors during long sessions
// Requires authentication and is rate-limited to prevent abuse
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->middleware(['web', 'auth', 'throttle:60,1']);

// Export download endpoint - handles file downloads from exports
Route::get('/download/export', function () {
    try {
        logger()->info('Export download requested', [
            'user_id' => auth()->id(),
            'session_has_export' => session()->has('export_file'),
        ]);

        $exportInfo = session()->pull('export_file');

        if (! $exportInfo || ! isset($exportInfo['path'], $exportInfo['name'], $exportInfo['user_id'])) {
            logger()->warning('Export file not found in session', [
                'export_info' => $exportInfo,
                'user_id' => auth()->id(),
            ]);
            abort(404, 'Export file not found or expired');
        }

        if ((int) $exportInfo['user_id'] !== auth()->id()) {
            logger()->warning('Unauthorized export download attempt', [
                'file_user_id' => $exportInfo['user_id'],
                'current_user_id' => auth()->id(),
            ]);
            abort(403, 'You are not authorized to download this export');
        }

        // User already had permission to create the export (checked in the export action)
        // No additional permission check needed here since we verify the user owns the export

        $resolvedPath = realpath($exportInfo['path']);

        // Allow exports from both legacy and current export directories.
        // In this project, the default "local" disk is rooted at storage/app/private,
        // so exports may be stored under storage/app/private/exports.
        $allowedBases = [
            realpath(storage_path('app/exports')) ?: storage_path('app/exports'),
            realpath(storage_path('app/private/exports')) ?: storage_path('app/private/exports'),
            realpath(storage_path('app/public/exports')) ?: storage_path('app/public/exports'),
        ];

        $allowedBases = array_values(array_unique(array_filter($allowedBases)));

        $baseOk = false;
        foreach ($allowedBases as $base) {
            if ($resolvedPath && Str::startsWith($resolvedPath, $base)) {
                $baseOk = true;
                break;
            }
        }

        logger()->info('Export path validation', [
            'resolved_path' => $resolvedPath,
            'allowed_bases' => $allowedBases,
            'file_exists' => file_exists($exportInfo['path']),
        ]);

        if (! $resolvedPath || ! $baseOk || ! file_exists($resolvedPath)) {
            logger()->error('Invalid export path', [
                'resolved_path' => $resolvedPath,
                'original_path' => $exportInfo['path'],
                'allowed_bases' => $allowedBases,
            ]);
            abort(403, 'Invalid export path');
        }

        // Check if file is too old (expired after 5 minutes)
        $exportExpirySeconds = 300; // 5 minutes
        if (isset($exportInfo['time']) && (now()->timestamp - $exportInfo['time']) > $exportExpirySeconds) {
            logger()->info('Export file expired', ['path' => $resolvedPath]);
            if (file_exists($resolvedPath)) {
                unlink($resolvedPath);
            }
            abort(410, 'Export file has expired');
        }

        logger()->info('Starting export download', [
            'path' => $resolvedPath,
            'name' => $exportInfo['name'],
        ]);

        return response()->download($resolvedPath, $exportInfo['name'])->deleteFileAfterSend(true);
    } catch (\Exception $e) {
        logger()->error('Export download failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
})->middleware(['web', 'auth'])->name('download.export');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', LoginPage::class)
    ->middleware('guest')
    ->name('login');

Route::get('/forgot-password', ForgotPassword::class)
    ->middleware('guest')
    ->name('password.request');

Route::get('/reset-password/{token}', ResetPassword::class)
    ->middleware('guest')
    ->name('password.reset');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

Route::get('/2fa/challenge', TwoFactorChallenge::class)
    ->middleware('auth')
    ->name('2fa.challenge');

Route::get('/2fa/setup', TwoFactorSetup::class)
    ->middleware('auth')
    ->name('2fa.setup');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Dashboard - Customizable
    Route::get('/dashboard', CustomizableDashboard::class)
        ->name('dashboard')
        ->middleware('can:'.config('screen_permissions.dashboard', 'dashboard.view'));

    // Dashboard - Classic (fallback)
    Route::get('/dashboard/classic', DashboardPage::class)
        ->name('dashboard.classic')
        ->middleware('can:'.config('screen_permissions.dashboard', 'dashboard.view'));

    // Profile
    Route::get('/profile', ProfileEditPage::class)
        ->name('profile.edit');

    // Notifications
    Route::get('/notifications', NotificationsCenter::class)
        ->name('notifications.center')
        ->middleware('can:'.config('screen_permissions.notifications.center', 'system.view-notifications'));

    // POS Terminal (special case - not under /app since it's a cashier interface)
    Route::get('/pos', PosTerminalPage::class)
        ->name('pos.terminal')
        ->middleware('can:'.config('screen_permissions.pos.terminal', 'pos.use'));

    Route::get('/pos/offline-sales', PosOfflineSalesPage::class)
        ->name('pos.offline.report')
        ->middleware('can:pos.offline.report.view');

    Route::get('/pos/daily-report', \App\Livewire\Pos\DailyReport::class)
        ->name('pos.daily.report')
        ->middleware('can:pos.daily-report.view');

    /*
    |--------------------------------------------------------------------------
    | Business Modules under /app/{module}
    |--------------------------------------------------------------------------
    */

    // SALES MODULE
    Route::prefix('app/sales')->name('app.sales.')->group(function () {
        Route::get('/', SalesIndexPage::class)
            ->name('index')
            ->middleware('can:sales.view');

        Route::get('/create', \App\Livewire\Sales\Form::class)
            ->name('create')
            ->middleware('can:sales.manage');

        Route::get('/returns', \App\Livewire\Sales\Returns\Index::class)
            ->name('returns.index')
            ->middleware('can:sales.return');

        Route::get('/analytics', \App\Livewire\Reports\SalesAnalytics::class)
            ->name('analytics')
            ->middleware('can:sales.view-reports');

        // Export & Import
        Route::get('/export', [\App\Http\Controllers\Branch\Sales\ExportImportController::class, 'exportSales'])
            ->name('sales.export')
            ->middleware('can:sales.export');

        Route::post('/import', [\App\Http\Controllers\Branch\Sales\ExportImportController::class, 'importSales'])
            ->name('sales.import')
            ->middleware('can:sales.import');

        Route::get('/{sale}', \App\Livewire\Sales\Show::class)
            ->name('show')
            ->middleware('can:sales.view')
            ->whereNumber('sale');

        Route::get('/{sale}/edit', \App\Livewire\Sales\Form::class)
            ->name('edit')
            ->middleware('can:sales.manage')
            ->whereNumber('sale');
    });

    // PURCHASES MODULE
    Route::prefix('app/purchases')->name('app.purchases.')->group(function () {
        Route::get('/', PurchasesIndexPage::class)
            ->name('index')
            ->middleware('can:purchases.view');

        Route::get('/create', \App\Livewire\Purchases\Form::class)
            ->name('create')
            ->middleware('can:purchases.manage');

        // Specific routes must come before wildcard routes
        Route::get('/returns', \App\Livewire\Purchases\Returns\Index::class)
            ->name('returns.index')
            ->middleware('can:purchases.return');

        // Purchase requisitions
        Route::get('/requisitions', \App\Livewire\Purchases\Requisitions\Index::class)
            ->name('requisitions.index')
            ->middleware('can:purchases.requisitions.view');

        Route::get('/requisitions/create', \App\Livewire\Purchases\Requisitions\Form::class)
            ->name('requisitions.create')
            ->middleware('can:purchases.requisitions.create');

        // Quotations
        Route::get('/quotations', \App\Livewire\Purchases\Quotations\Index::class)
            ->name('quotations.index')
            ->middleware('can:purchases.view');

        Route::get('/quotations/create', \App\Livewire\Purchases\Quotations\Form::class)
            ->name('quotations.create')
            ->middleware('can:purchases.manage');

        Route::get('/quotations/{quotation}/compare', \App\Livewire\Purchases\Quotations\Compare::class)
            ->name('quotations.compare')
            ->middleware('can:purchases.view');

        // Goods Received Notes
        Route::get('/grn', \App\Livewire\Purchases\GRN\Index::class)
            ->name('grn.index')
            ->middleware('can:purchases.view');

        Route::get('/grn/create', \App\Livewire\Purchases\GRN\Form::class)
            ->name('grn.create')
            ->middleware('can:purchases.manage');

        Route::get('/grn/{id}/edit', \App\Livewire\Purchases\GRN\Form::class)
            ->name('grn.edit')
            ->middleware('can:purchases.manage');

        // NEW-03 FIX: Added route for GRN Inspection feature
        Route::get('/grn/{id}/inspection', \App\Livewire\Purchases\GRN\Inspection::class)
            ->name('grn.inspection')
            ->middleware('can:grn.inspect');

        // Export & Import
        Route::get('/export', [\App\Http\Controllers\Branch\Purchases\ExportImportController::class, 'exportPurchases'])
            ->name('purchases.export')
            ->middleware('can:purchases.export');

        Route::post('/import', [\App\Http\Controllers\Branch\Purchases\ExportImportController::class, 'importPurchases'])
            ->name('purchases.import')
            ->middleware('can:purchases.import');

        // Wildcard routes must come last
        Route::get('/{purchase}', \App\Livewire\Purchases\Show::class)
            ->name('show')
            ->middleware('can:purchases.view');

        Route::get('/{purchase}/edit', \App\Livewire\Purchases\Form::class)
            ->name('edit')
            ->middleware('can:purchases.manage');
    });

    // INVENTORY MODULE
    Route::prefix('app/inventory')->name('app.inventory.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('app.inventory.products.index');
        })->name('index');

        // Products
        Route::get('/products', ProductsIndexPage::class)
            ->name('products.index')
            ->middleware('can:'.config('screen_permissions.inventory.products.index', 'inventory.products.view'));

        Route::get('/products/create', ProductFormPage::class)
            ->name('products.create')
            ->middleware('can:'.config('screen_permissions.inventory.products.index', 'inventory.products.view'));

        Route::get('/products/{product}', \App\Livewire\Inventory\Products\Show::class)
            ->name('products.show')
            ->middleware('can:inventory.products.view');

        Route::get('/products/{product}/edit', ProductFormPage::class)
            ->name('products.edit')
            ->middleware('can:'.config('screen_permissions.inventory.products.index', 'inventory.products.view'));

        Route::get('/products/{product}/history', \App\Livewire\Inventory\ProductHistory::class)
            ->name('products.history')
            ->middleware('can:inventory.products.view');

        Route::get('/products/{product}/store-mappings', \App\Livewire\Inventory\ProductStoreMappings::class)
            ->name('products.store-mappings')
            ->middleware('can:inventory.products.view');

        Route::get('/products/{product}/store-mappings/create', \App\Livewire\Inventory\ProductStoreMappings\Form::class)
            ->name('products.store-mappings.create')
            ->middleware('can:inventory.products.create');

        Route::get('/products/{product}/store-mappings/{mapping}/edit', \App\Livewire\Inventory\ProductStoreMappings\Form::class)
            ->name('products.store-mappings.edit')
            ->middleware('can:inventory.products.update');

        Route::get('/products/{product}/compatibility', \App\Livewire\Inventory\ProductCompatibility::class)
            ->name('products.compatibility')
            ->middleware('can:inventory.products.view');

        // Categories
        Route::get('/categories', \App\Livewire\Admin\Categories\Index::class)
            ->name('categories.index')
            ->middleware('can:inventory.categories.view');

        Route::get('/categories/create', \App\Livewire\Admin\Categories\Form::class)
            ->name('categories.create')
            ->middleware('can:inventory.categories.manage');

        Route::get('/categories/{category}/edit', \App\Livewire\Admin\Categories\Form::class)
            ->name('categories.edit')
            ->middleware('can:inventory.categories.manage');

        // Units
        Route::get('/units', \App\Livewire\Admin\UnitsOfMeasure\Index::class)
            ->name('units.index')
            ->middleware('can:inventory.units.view');

        Route::get('/units/create', \App\Livewire\Admin\UnitsOfMeasure\Form::class)
            ->name('units.create')
            ->middleware('can:inventory.units.manage');

        Route::get('/units/{unit}/edit', \App\Livewire\Admin\UnitsOfMeasure\Form::class)
            ->name('units.edit')
            ->middleware('can:inventory.units.manage');

        // Stock Alerts
        Route::get('/stock-alerts', \App\Livewire\Inventory\StockAlerts::class)
            ->name('stock-alerts')
            ->middleware('can:inventory.view');

        // Batches
        Route::get('/batches', \App\Livewire\Inventory\Batches\Index::class)
            ->name('batches.index')
            ->middleware('can:inventory.view');

        Route::get('/batches/create', \App\Livewire\Inventory\Batches\Form::class)
            ->name('batches.create')
            ->middleware('can:inventory.manage');

        Route::get('/batches/{batch}/edit', \App\Livewire\Inventory\Batches\Form::class)
            ->name('batches.edit')
            ->middleware('can:inventory.manage');

        // Serials
        Route::get('/serials', \App\Livewire\Inventory\Serials\Index::class)
            ->name('serials.index')
            ->middleware('can:inventory.view');

        Route::get('/serials/create', \App\Livewire\Inventory\Serials\Form::class)
            ->name('serials.create')
            ->middleware('can:inventory.manage');

        Route::get('/serials/{serial}/edit', \App\Livewire\Inventory\Serials\Form::class)
            ->name('serials.edit')
            ->middleware('can:inventory.manage');

        // Services
        Route::get('/services/create', \App\Livewire\Inventory\Services\Form::class)
            ->name('services.create')
            ->middleware('can:inventory.products.create');

        Route::get('/services/{service}/edit', \App\Livewire\Inventory\Services\Form::class)
            ->name('services.edit')
            ->middleware('can:inventory.products.update');

        // Barcode printing
        Route::get('/barcodes', \App\Livewire\Inventory\BarcodePrint::class)
            ->name('barcodes')
            ->middleware('can:inventory.view');

        // Vehicle Models (for spare parts compatibility)
        Route::get('/vehicle-models', \App\Livewire\Inventory\VehicleModels::class)
            ->name('vehicle-models.index')
            ->middleware('can:inventory.view');

        Route::get('/vehicle-models/create', \App\Livewire\Inventory\VehicleModels\Form::class)
            ->name('vehicle-models.create')
            ->middleware('can:spares.compatibility.manage');

        Route::get('/vehicle-models/{vehicleModel}/edit', \App\Livewire\Inventory\VehicleModels\Form::class)
            ->name('vehicle-models.edit')
            ->middleware('can:spares.compatibility.manage');
    });

    // WAREHOUSE MODULE
    Route::prefix('app/warehouse')->name('app.warehouse.')->group(function () {
        Route::get('/', WarehouseIndexPage::class)
            ->name('index')
            ->middleware('can:warehouse.view');

        Route::get('/warehouses/create', \App\Livewire\Warehouse\Warehouses\Form::class)
            ->name('warehouses.create')
            ->middleware('can:warehouse.manage');

        Route::get('/warehouses/{warehouse}/edit', \App\Livewire\Warehouse\Warehouses\Form::class)
            ->name('warehouses.edit')
            ->middleware('can:warehouse.manage');

        Route::get('/locations', \App\Livewire\Warehouse\Locations\Index::class)
            ->name('locations.index')
            ->middleware('can:warehouse.view');

        Route::get('/locations/create', \App\Livewire\Warehouse\Locations\Form::class)
            ->name('locations.create')
            ->middleware('can:warehouse.manage');

        Route::get('/locations/{warehouse}/edit', \App\Livewire\Warehouse\Locations\Form::class)
            ->name('locations.edit')
            ->middleware('can:warehouse.manage');

        Route::get('/movements', \App\Livewire\Warehouse\Movements\Index::class)
            ->name('movements.index')
            ->middleware('can:warehouse.view');

        Route::get('/transfers', \App\Livewire\Warehouse\Transfers\Index::class)
            ->name('transfers.index')
            ->middleware('can:warehouse.view');

        Route::get('/transfers/create', \App\Livewire\Warehouse\Transfers\Form::class)
            ->name('transfers.create')
            ->middleware('can:warehouse.manage');

        Route::get('/adjustments', \App\Livewire\Warehouse\Adjustments\Index::class)
            ->name('adjustments.index')
            ->middleware('can:warehouse.view');

        Route::get('/adjustments/create', \App\Livewire\Warehouse\Adjustments\Form::class)
            ->name('adjustments.create')
            ->middleware('can:warehouse.manage');
    });

    // RENTAL MODULE
    Route::prefix('app/rental')->name('app.rental.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('app.rental.units.index');
        })->name('index');

        // Units
        Route::get('/units', RentalUnitsIndex::class)
            ->name('units.index')
            ->middleware('can:rental.units.view');

        Route::get('/units/create', RentalUnitForm::class)
            ->name('units.create')
            ->middleware('can:rental.units.manage');

        Route::get('/units/{unit}/edit', RentalUnitForm::class)
            ->name('units.edit')
            ->middleware('can:rental.units.manage');

        // Properties
        Route::get('/properties', \App\Livewire\Rental\Properties\Index::class)
            ->name('properties.index')
            ->middleware('can:rental.view');

        Route::get('/properties/create', \App\Livewire\Rental\Properties\Form::class)
            ->name('properties.create')
            ->middleware('can:rental.properties.create');

        Route::get('/properties/{property}/edit', \App\Livewire\Rental\Properties\Form::class)
            ->name('properties.edit')
            ->middleware('can:rental.properties.update');

        // Tenants
        Route::get('/tenants', \App\Livewire\Rental\Tenants\Index::class)
            ->name('tenants.index')
            ->middleware('can:rental.view');

        Route::get('/tenants/create', \App\Livewire\Rental\Tenants\Form::class)
            ->name('tenants.create')
            ->middleware('can:rental.tenants.create');

        Route::get('/tenants/{tenant}/edit', \App\Livewire\Rental\Tenants\Form::class)
            ->name('tenants.edit')
            ->middleware('can:rental.tenants.update');

        // Contracts
        Route::get('/contracts', RentalContractsIndex::class)
            ->name('contracts.index')
            ->middleware('can:rental.contracts.view');

        Route::get('/contracts/create', RentalContractForm::class)
            ->name('contracts.create')
            ->middleware('can:rental.contracts.manage');

        Route::get('/contracts/{contract}/edit', RentalContractForm::class)
            ->name('contracts.edit')
            ->middleware('can:rental.contracts.manage');

        // Reports
        Route::get('/reports', RentalReportsDashboard::class)
            ->name('reports')
            ->middleware('can:rental.view-reports');
    });

    // MANUFACTURING MODULE
    Route::prefix('app/manufacturing')->name('app.manufacturing.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('app.manufacturing.boms.index');
        })->name('index');

        // Bills of Materials
        Route::get('/boms', \App\Livewire\Manufacturing\BillsOfMaterials\Index::class)
            ->name('boms.index')
            ->middleware('can:manufacturing.view');

        Route::get('/boms/create', \App\Livewire\Manufacturing\BillsOfMaterials\Form::class)
            ->name('boms.create')
            ->middleware('can:manufacturing.manage');

        Route::get('/boms/{bom}/edit', \App\Livewire\Manufacturing\BillsOfMaterials\Form::class)
            ->name('boms.edit')
            ->middleware('can:manufacturing.manage');

        // Production Orders
        Route::get('/orders', \App\Livewire\Manufacturing\ProductionOrders\Index::class)
            ->name('orders.index')
            ->middleware('can:manufacturing.view');

        Route::get('/orders/create', \App\Livewire\Manufacturing\ProductionOrders\Form::class)
            ->name('orders.create')
            ->middleware('can:manufacturing.manage');

        Route::get('/orders/{order}/edit', \App\Livewire\Manufacturing\ProductionOrders\Form::class)
            ->name('orders.edit')
            ->middleware('can:manufacturing.manage');

        // Work Centers
        Route::get('/work-centers', \App\Livewire\Manufacturing\WorkCenters\Index::class)
            ->name('work-centers.index')
            ->middleware('can:manufacturing.view');

        Route::get('/work-centers/create', \App\Livewire\Manufacturing\WorkCenters\Form::class)
            ->name('work-centers.create')
            ->middleware('can:manufacturing.manage');

        Route::get('/work-centers/{workCenter}/edit', \App\Livewire\Manufacturing\WorkCenters\Form::class)
            ->name('work-centers.edit')
            ->middleware('can:manufacturing.manage');

        // Production Timeline
        Route::get('/timeline', \App\Livewire\Manufacturing\Timeline::class)
            ->name('timeline')
            ->middleware('can:manufacturing.view');
    });

    // HRM MODULE
    Route::prefix('app/hrm')->name('app.hrm.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('app.hrm.employees.index');
        })->name('index');

        // Employees
        Route::get('/employees', HrmEmployeesIndex::class)
            ->name('employees.index')
            ->middleware('can:hrm.employees.view');

        Route::get('/employees/create', HrmEmployeeForm::class)
            ->name('employees.create')
            ->middleware('can:hrm.employees.assign');

        Route::get('/employees/{employee}/edit', HrmEmployeeForm::class)
            ->name('employees.edit')
            ->middleware('can:hrm.employees.assign');

        // Attendance
        Route::get('/attendance', HrmAttendanceIndex::class)
            ->name('attendance.index')
            ->middleware('can:hrm.attendance.view');

        // Payroll
        Route::get('/payroll', HrmPayrollIndex::class)
            ->name('payroll.index')
            ->middleware('can:hrm.payroll.view');

        Route::get('/payroll/run', HrmPayrollRun::class)
            ->name('payroll.run')
            ->middleware('can:hrm.payroll.run');

        // Shifts
        Route::get('/shifts', \App\Livewire\Hrm\Shifts\Index::class)
            ->name('shifts.index')
            ->middleware('can:hrm.view');

        Route::get('/shifts/create', \App\Livewire\Hrm\Shifts\Form::class)
            ->name('shifts.create')
            ->middleware('can:hrm.manage');

        Route::get('/shifts/{shift}/edit', \App\Livewire\Hrm\Shifts\Form::class)
            ->name('shifts.edit')
            ->middleware('can:hrm.manage');

        // Reports
        Route::get('/reports', HrmReportsDashboard::class)
            ->name('reports')
            ->middleware('can:hrm.view-reports');

        // Self Service Routes
        Route::get('/my-attendance', \App\Livewire\Hrm\SelfService\MyAttendance::class)
            ->name('my-attendance')
            ->middleware('can:employee.self.attendance');

        Route::get('/my-leaves', \App\Livewire\Hrm\SelfService\MyLeaves::class)
            ->name('my-leaves')
            ->middleware('can:employee.self.leave-request');

        Route::get('/my-payslips', \App\Livewire\Hrm\SelfService\MyPayslips::class)
            ->name('my-payslips')
            ->middleware('can:employee.self.payslip-view');
    });

    // BANKING MODULE
    Route::prefix('app/banking')->name('app.banking.')->group(function () {
        Route::get('/', \App\Livewire\Banking\Index::class)
            ->name('index')
            ->middleware('can:banking.view');

        Route::get('/accounts', \App\Livewire\Banking\Accounts\Index::class)
            ->name('accounts.index')
            ->middleware('can:banking.view');

        Route::get('/accounts/create', \App\Livewire\Banking\Accounts\Form::class)
            ->name('accounts.create')
            ->middleware('can:banking.create');

        Route::get('/accounts/{account}/edit', \App\Livewire\Banking\Accounts\Form::class)
            ->name('accounts.edit')
            ->middleware('can:banking.edit');

        Route::get('/transactions', \App\Livewire\Banking\Transactions\Index::class)
            ->name('transactions.index')
            ->middleware('can:banking.view');

        Route::get('/reconciliation', \App\Livewire\Banking\Reconciliation::class)
            ->name('reconciliation')
            ->middleware('can:banking.reconcile');
    });

    // FIXED ASSETS MODULE
    Route::prefix('app/fixed-assets')->name('app.fixed-assets.')->group(function () {
        Route::get('/', \App\Livewire\FixedAssets\Index::class)
            ->name('index')
            ->middleware('can:fixed-assets.view');

        Route::get('/create', \App\Livewire\FixedAssets\Form::class)
            ->name('create')
            ->middleware('can:fixed-assets.manage');

        Route::get('/{asset}/edit', \App\Livewire\FixedAssets\Form::class)
            ->name('edit')
            ->middleware('can:fixed-assets.manage');

        Route::get('/depreciation', \App\Livewire\FixedAssets\Depreciation::class)
            ->name('depreciation')
            ->middleware('can:fixed-assets.view');
    });

    // PROJECTS MODULE
    Route::prefix('app/projects')->name('app.projects.')->group(function () {
        Route::get('/', \App\Livewire\Projects\Index::class)
            ->name('index')
            ->middleware('can:projects.view');

        Route::get('/gantt', \App\Livewire\Projects\GanttChart::class)
            ->name('gantt')
            ->middleware('can:projects.view');

        Route::get('/create', \App\Livewire\Projects\Form::class)
            ->name('create')
            ->middleware('can:projects.manage');

        Route::get('/{project}', \App\Livewire\Projects\Show::class)
            ->name('show')
            ->middleware('can:projects.view');

        Route::get('/{project}/edit', \App\Livewire\Projects\Form::class)
            ->name('edit')
            ->middleware('can:projects.manage');

        // Tasks
        Route::get('/{project}/tasks', \App\Livewire\Projects\Tasks::class)
            ->name('tasks.index')
            ->middleware('can:projects.view');

        // Expenses
        Route::get('/{project}/expenses', \App\Livewire\Projects\Expenses::class)
            ->name('expenses.index')
            ->middleware('can:projects.view');
    });

    // DOCUMENTS MODULE
    Route::prefix('app/documents')->name('app.documents.')->group(function () {
        Route::get('/', \App\Livewire\Documents\Index::class)
            ->name('index')
            ->middleware('can:documents.view');

        Route::get('/create', \App\Livewire\Documents\Form::class)
            ->name('create')
            ->middleware('can:documents.manage');

        Route::get('/tags', \App\Livewire\Documents\Tags\Index::class)
            ->name('tags.index')
            ->middleware('can:documents.tags.manage');

        Route::get('/tags/create', \App\Livewire\Documents\Tags\Form::class)
            ->name('tags.create')
            ->middleware('can:documents.tags.manage');

        Route::get('/tags/{tag}/edit', \App\Livewire\Documents\Tags\Form::class)
            ->name('tags.edit')
            ->middleware('can:documents.tags.manage');

        Route::get('/{document}', \App\Livewire\Documents\Show::class)
            ->name('show')
            ->middleware('can:documents.view');

        Route::get('/{document}/edit', \App\Livewire\Documents\Form::class)
            ->name('edit')
            ->middleware('can:documents.manage');

        Route::get('/{document}/versions', \App\Livewire\Documents\Versions::class)
            ->name('versions')
            ->middleware('can:documents.view');

        // Authenticated, permission-guarded download route
        Route::get('/{document}/download', \App\Http\Controllers\Documents\DownloadController::class)
            ->name('download')
            ->middleware(['auth', 'can:documents.download']);
    });

    // Attachments
    Route::get('/attachments/{attachment}/download', \App\Http\Controllers\Attachments\DownloadController::class)
        ->name('attachments.download')
        ->middleware(['auth', 'signed']);

    // Media downloads for app users
    Route::get('/app/media/{media}/download', \App\Http\Controllers\Admin\MediaDownloadController::class)
        ->name('app.media.download')
        ->middleware(['auth', 'can:media.view']);

    // HELPDESK MODULE
    Route::prefix('app/helpdesk')->name('app.helpdesk.')->group(function () {
        Route::get('/', \App\Livewire\Helpdesk\Index::class)
            ->name('index')
            ->middleware('can:helpdesk.view');

        Route::get('/tickets', \App\Livewire\Helpdesk\Tickets\Index::class)
            ->name('tickets.index')
            ->middleware('can:helpdesk.view');

        Route::get('/tickets/create', \App\Livewire\Helpdesk\Tickets\Form::class)
            ->name('tickets.create')
            ->middleware('can:helpdesk.manage');

        Route::get('/tickets/{ticket}', \App\Livewire\Helpdesk\Tickets\Show::class)
            ->name('tickets.show')
            ->middleware('can:helpdesk.view');

        Route::get('/tickets/{ticket}/edit', \App\Livewire\Helpdesk\Tickets\Form::class)
            ->name('tickets.edit')
            ->middleware('can:helpdesk.edit');

        Route::get('/categories', \App\Livewire\Helpdesk\Categories\Index::class)
            ->name('categories.index')
            ->middleware('can:helpdesk.manage');

        Route::get('/categories/create', \App\Livewire\Helpdesk\Categories\Form::class)
            ->name('categories.create')
            ->middleware('can:helpdesk.manage');

        Route::get('/categories/{category}/edit', \App\Livewire\Helpdesk\Categories\Form::class)
            ->name('categories.edit')
            ->middleware('can:helpdesk.manage');

        Route::get('/priorities', \App\Livewire\Helpdesk\Priorities\Index::class)
            ->name('priorities.index')
            ->middleware('can:helpdesk.manage');

        Route::get('/priorities/create', \App\Livewire\Helpdesk\Priorities\Form::class)
            ->name('priorities.create')
            ->middleware('can:helpdesk.manage');

        Route::get('/priorities/{priority}/edit', \App\Livewire\Helpdesk\Priorities\Form::class)
            ->name('priorities.edit')
            ->middleware('can:helpdesk.manage');

        Route::get('/sla-policies', \App\Livewire\Helpdesk\SLAPolicies\Index::class)
            ->name('sla-policies.index')
            ->middleware('can:helpdesk.manage');

        Route::get('/sla-policies/create', \App\Livewire\Helpdesk\SLAPolicies\Form::class)
            ->name('sla-policies.create')
            ->middleware('can:helpdesk.manage');

        Route::get('/sla-policies/{policy}/edit', \App\Livewire\Helpdesk\SLAPolicies\Form::class)
            ->name('sla-policies.edit')
            ->middleware('can:helpdesk.manage');
    });

    // ACCOUNTING MODULE (kept separate as it's more complex)
    Route::prefix('app/accounting')->name('app.accounting.')->group(function () {
        Route::get('/', AccountingIndexPage::class)
            ->name('index')
            ->middleware('can:accounting.view');

        Route::get('/accounts/create', \App\Livewire\Accounting\Accounts\Form::class)
            ->name('accounts.create')
            ->middleware('can:accounting.create');

        Route::get('/accounts/{account}/edit', \App\Livewire\Accounting\Accounts\Form::class)
            ->name('accounts.edit')
            ->middleware('can:accounting.view');

        Route::get('/journal-entries/create', \App\Livewire\Accounting\JournalEntries\Form::class)
            ->name('journal-entries.create')
            ->middleware('can:accounting.create');

        Route::get('/journal-entries/{journalEntry}/edit', \App\Livewire\Accounting\JournalEntries\Form::class)
            ->name('journal-entries.edit')
            ->middleware('can:accounting.view');
    });

    // EXPENSES & INCOME (financial transactions)
    Route::prefix('app/expenses')->name('app.expenses.')->group(function () {
        Route::get('/', ExpensesIndexPage::class)
            ->name('index')
            ->middleware('can:expenses.view');

        Route::get('/create', ExpenseFormPage::class)
            ->name('create')
            ->middleware('can:expenses.manage');

        Route::get('/{expense}/edit', ExpenseFormPage::class)
            ->name('edit')
            ->middleware('can:expenses.manage');

        Route::get('/categories', \App\Livewire\Expenses\Categories\Index::class)
            ->name('categories.index')
            ->middleware('can:expenses.manage');

        Route::get('/categories/create', \App\Livewire\Expenses\Categories\Form::class)
            ->name('categories.create')
            ->middleware('can:expenses.manage');

        Route::get('/categories/{category}/edit', \App\Livewire\Expenses\Categories\Form::class)
            ->name('categories.edit')
            ->middleware('can:expenses.manage');
    });

    Route::prefix('app/income')->name('app.income.')->group(function () {
        Route::get('/', IncomeIndexPage::class)
            ->name('index')
            ->middleware('can:income.view');

        Route::get('/create', \App\Livewire\Income\Form::class)
            ->name('create')
            ->middleware('can:income.manage');

        Route::get('/{income}/edit', \App\Livewire\Income\Form::class)
            ->name('edit')
            ->middleware('can:income.manage');

        Route::get('/categories', \App\Livewire\Income\Categories\Index::class)
            ->name('categories.index')
            ->middleware('can:income.manage');

        Route::get('/categories/create', \App\Livewire\Income\Categories\Form::class)
            ->name('categories.create')
            ->middleware('can:income.manage');

        Route::get('/categories/{category}/edit', \App\Livewire\Income\Categories\Form::class)
            ->name('categories.edit')
            ->middleware('can:income.manage');
    });

    // CUSTOMERS & SUPPLIERS (business contacts)
    Route::get('/customers', CustomersIndexPage::class)
        ->name('customers.index')
        ->middleware('can:customers.view');

    Route::get('/customers/create', CustomerFormPage::class)
        ->name('customers.create')
        ->middleware('can:customers.manage');

    Route::get('/customers/{customer}/edit', CustomerFormPage::class)
        ->name('customers.edit')
        ->middleware('can:customers.manage');

    Route::get('/suppliers', SuppliersIndexPage::class)
        ->name('suppliers.index')
        ->middleware('can:suppliers.view');

    Route::get('/suppliers/create', SupplierFormPage::class)
        ->name('suppliers.create')
        ->middleware('can:suppliers.manage');

    Route::get('/suppliers/{supplier}/edit', SupplierFormPage::class)
        ->name('suppliers.edit')
        ->middleware('can:suppliers.manage');

    /*
    |--------------------------------------------------------------------------
    | Admin Area
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->group(function () {

        // Users Management
        Route::get('/users', UsersIndexPage::class)
            ->name('users.index')
            ->middleware('can:'.config('screen_permissions.admin.users.index', 'users.manage'));

        Route::get('/users/create', UserFormPage::class)
            ->name('users.create')
            ->middleware('can:'.config('screen_permissions.admin.users.index', 'users.manage'));

        Route::get('/users/{user}/edit', UserFormPage::class)
            ->name('users.edit')
            ->middleware('can:'.config('screen_permissions.admin.users.index', 'users.manage'));

        // Roles Management
        Route::get('/roles', RolesIndexPage::class)
            ->name('roles.index')
            ->middleware('can:roles.manage');

        Route::get('/roles/create', RoleFormPage::class)
            ->name('roles.create')
            ->middleware('can:roles.manage');

        Route::get('/roles/{role}/edit', RoleFormPage::class)
            ->name('roles.edit')
            ->middleware('can:roles.manage');

        // Branches Management
        Route::get('/branches', BranchesIndexPage::class)
            ->name('branches.index')
            ->middleware('can:'.config('screen_permissions.admin.branches.index', 'branches.view'));

        Route::get('/branches/create', BranchFormPage::class)
            ->name('branches.create')
            ->middleware('can:'.config('screen_permissions.admin.branches.index', 'branches.view'));

        Route::get('/branches/{branch}/edit', BranchFormPage::class)
            ->name('branches.edit')
            ->middleware('can:'.config('screen_permissions.admin.branches.index', 'branches.view'))
            ->whereNumber('branch');

        Route::get('/branches/{branch}/modules', \App\Livewire\Admin\Branches\Modules::class)
            ->name('branches.modules')
            ->middleware('can:branches.manage')
            ->whereNumber('branch');

        Route::get('/branches/compare', \App\Livewire\Admin\Branches\Compare::class)
            ->name('branches.compare')
            ->middleware('can:branches.manage');

        // Modules Management
        Route::get('/modules', ModulesIndexPage::class)
            ->name('modules.index')
            ->middleware('can:modules.manage');

        Route::get('/modules/create', ModuleFormPage::class)
            ->name('modules.create')
            ->middleware('can:modules.manage');

        Route::get('/modules/{module}/edit', ModuleFormPage::class)
            ->name('modules.edit')
            ->middleware('can:modules.manage')
            ->whereNumber('module');

        Route::get('/modules/{module}/fields', \App\Livewire\Admin\Modules\Fields::class)
            ->name('modules.fields')
            ->middleware('can:modules.manage')
            ->whereNumber('module');

        Route::get('/modules/{module}/fields/create', \App\Livewire\Admin\Modules\Fields\Form::class)
            ->name('modules.fields.create')
            ->middleware('can:modules.manage')
            ->whereNumber('module');

        Route::get('/modules/{module}/fields/{field}/edit', \App\Livewire\Admin\Modules\Fields\Form::class)
            ->name('modules.fields.edit')
            ->middleware('can:modules.manage')
            ->whereNumber('module')
            ->whereNumber('field');

        Route::get('/modules/{module}/rental-periods', \App\Livewire\Admin\Modules\RentalPeriods::class)
            ->name('modules.rental-periods')
            ->middleware('can:modules.manage')
            ->whereNumber('module');

        Route::get('/modules/{module}/rental-periods/create', \App\Livewire\Admin\Modules\RentalPeriods\Form::class)
            ->name('modules.rental-periods.create')
            ->middleware('can:modules.manage')
            ->whereNumber('module');

        Route::get('/modules/{module}/rental-periods/{period}/edit', \App\Livewire\Admin\Modules\RentalPeriods\Form::class)
            ->name('modules.rental-periods.edit')
            ->middleware('can:modules.manage')
            ->whereNumber('module')
            ->whereNumber('period');

        Route::get('/modules/product-fields/{moduleId?}', \App\Livewire\Admin\Modules\ProductFields::class)
            ->name('modules.product-fields')
            ->middleware('can:modules.manage');

        Route::get('/modules/product-fields/{moduleId}/create', \App\Livewire\Admin\Modules\ProductFields\Form::class)
            ->name('modules.product-fields.create')
            ->middleware('can:modules.manage');

        Route::get('/modules/product-fields/{moduleId}/{field}/edit', \App\Livewire\Admin\Modules\ProductFields\Form::class)
            ->name('modules.product-fields.edit')
            ->middleware('can:modules.manage');

        // Stores Management
        Route::get('/stores', \App\Livewire\Admin\Store\Stores::class)
            ->name('stores.index')
            ->middleware('can:stores.view');

        Route::get('/stores/create', \App\Livewire\Admin\Store\Form::class)
            ->name('stores.create')
            ->middleware('can:stores.manage');

        Route::get('/stores/{store}/edit', \App\Livewire\Admin\Store\Form::class)
            ->name('stores.edit')
            ->middleware('can:stores.manage');

        Route::get('/stores/orders', \App\Livewire\Admin\Store\OrdersDashboard::class)
            ->name('stores.orders')
            ->middleware('can:stores.view');

        Route::get('/stores/orders/export', StoreOrdersExportController::class)
            ->name('stores.orders.export')
            ->middleware('can:store.reports.dashboard');

        // API Documentation
        Route::get('/api-docs', \App\Livewire\Admin\ApiDocumentation::class)
            ->name('api-docs')
            ->middleware('can:stores.view');

        // Translation Management
        Route::get('/translations', \App\Livewire\Admin\Translations\Index::class)
            ->name('translations.index')
            ->middleware('can:settings.view');

        Route::get('/translations/create', \App\Livewire\Admin\Translations\Form::class)
            ->name('translations.create')
            ->middleware('can:settings.translations.manage');

        Route::get('/translations/edit', \App\Livewire\Admin\Translations\Form::class)
            ->name('translations.edit')
            ->middleware('can:settings.translations.manage');

        // Currency Management
        Route::get('/currencies', \App\Livewire\Admin\CurrencyManager::class)
            ->name('currencies.index')
            ->middleware('can:settings.view');

        Route::get('/currencies/create', \App\Livewire\Admin\Currency\Form::class)
            ->name('currencies.create')
            ->middleware('can:settings.currency.manage');

        Route::get('/currencies/{currency}/edit', \App\Livewire\Admin\Currency\Form::class)
            ->name('currencies.edit')
            ->middleware('can:settings.currency.manage');

        Route::get('/currency-rates', \App\Livewire\Admin\CurrencyRates::class)
            ->name('currency-rates.index')
            ->middleware('can:settings.view');

        Route::get('/currency-rates/create', \App\Livewire\Admin\CurrencyRate\Form::class)
            ->name('currency-rates.create')
            ->middleware('can:settings.manage');

        Route::get('/currency-rates/{currencyRate}/edit', \App\Livewire\Admin\CurrencyRate\Form::class)
            ->name('currency-rates.edit')
            ->middleware('can:settings.manage');

        // Setup Wizard
        Route::get('/setup', \App\Livewire\Admin\SetupWizard::class)
            ->name('setup-wizard')
            ->middleware('can:settings.manage');

        // Unified Settings (NEW)
        Route::get('/settings', UnifiedSettings::class)
            ->name('settings')
            ->middleware('can:settings.view');

        // Module-specific settings pages
        Route::get('/settings/warehouse', \App\Livewire\Admin\Settings\WarehouseSettings::class)
            ->name('settings.warehouse')
            ->middleware('can:settings.view');

        Route::get('/settings/purchases', \App\Livewire\Admin\Settings\PurchasesSettings::class)
            ->name('settings.purchases')
            ->middleware('can:settings.view');

        // Redirects from old settings routes
        Route::redirect('/settings/system', '/admin/settings?tab=general');
        Route::redirect('/settings/branch', '/admin/settings?tab=branch');
        Route::redirect('/settings/translations', '/admin/settings?tab=translations');
        Route::redirect('/settings/advanced', '/admin/settings?tab=advanced');

        // Export & Import
        Route::get('/export/customize', \App\Livewire\Admin\Export\CustomizeExport::class)
            ->name('export.customize')
            ->middleware('can:reports.export');

        // Bulk Import
        Route::get('/bulk-import', \App\Livewire\Admin\BulkImport::class)
            ->name('bulk-import')
            ->middleware('can:settings.view');

        // Backup & Restore
        Route::get('/backup', \App\Livewire\Admin\BackupRestore::class)
            ->name('backup')
            ->middleware('can:settings.manage');

        // Media Library
        Route::get('/media', \App\Livewire\Admin\MediaLibrary::class)
            ->name('media.index')
            ->middleware('can:media.view');
        Route::get('/media/{media}/download', \App\Http\Controllers\Admin\MediaDownloadController::class)
            ->name('media.download')
            ->middleware(['auth', 'can:media.view']);

        // Audit Logs
        Route::get('/logs/audit', AuditLogPage::class)
            ->name('logs.audit')
            ->middleware('can:'.config('screen_permissions.logs.audit', 'logs.audit.view'));

        // Activity Log (Spatie)
        Route::get('/activity-log', \App\Livewire\Admin\ActivityLog::class)
            ->name('activity-log')
            ->middleware('can:logs.audit.view');

        Route::get('/activity-log/{id}', \App\Livewire\Admin\ActivityLogShow::class)
            ->name('activity-log.show')
            ->middleware('can:logs.audit.view')
            ->whereNumber('id');

        // Branch Admin Routes
        Route::get('/branch-settings', \App\Livewire\Admin\Branch\Settings::class)
            ->name('branch-settings')
            ->middleware('can:branch.settings.manage');

        Route::get('/branch-reports', \App\Livewire\Admin\Branch\Reports::class)
            ->name('branch-reports')
            ->middleware('can:branch.reports.view');

        Route::get('/branch-employees', \App\Livewire\Admin\Branch\Employees::class)
            ->name('branch-employees')
            ->middleware('can:branch.employees.manage');

        /*
        |--------------------------------------------------------------------------
        | Admin Reports
        |--------------------------------------------------------------------------
        */

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', \App\Livewire\Admin\Reports\Index::class)
                ->name('index')
                ->middleware('can:reports.view');

            Route::get('/aggregate', \App\Livewire\Admin\Reports\Aggregate::class)
                ->name('aggregate')
                ->middleware('can:reports.aggregate');

            Route::get('/module/{module}', \App\Livewire\Admin\Reports\ModuleReport::class)
                ->name('module')
                ->middleware('can:reports.view');

            Route::get('/sales', \App\Livewire\Reports\SalesAnalytics::class)
                ->name('sales')
                ->middleware('can:sales.view-reports');

            Route::get('/inventory', InventoryChartsDashboard::class)
                ->name('inventory')
                ->middleware('can:inventory.view-reports');

            Route::get('/pos', PosChartsDashboard::class)
                ->name('pos')
                ->middleware('can:pos.view-reports');

            Route::get('/scheduled', ScheduledReportsManager::class)
                ->name('scheduled')
                ->middleware('can:reports.schedule');

            // Note: scheduled/create and scheduled/edit routes removed
            // The ScheduledReportsManager component handles inline create/edit
            // This avoids the dual-system issue where create/edit used report_schedules
            // while ScheduledReportsManager used scheduled_reports table

            Route::get('/templates', ReportTemplatesManager::class)
                ->name('templates')
                ->middleware('can:reports.templates');

            Route::get('/advanced-analytics', \App\Livewire\Admin\Analytics\AdvancedDashboard::class)
                ->name('advanced-analytics')
                ->middleware('can:reports.view');
        });

        // Export customization
        Route::get('/export/customize', \App\Livewire\Admin\Export\CustomizeExport::class)
            ->name('export.customize')
            ->middleware('can:reports.export');
    });

    // Legacy route redirects (for backward compatibility)
    Route::redirect('/sales', '/app/sales');
    Route::redirect('/sales/returns', '/app/sales/returns');
    Route::redirect('/purchases', '/app/purchases');
    Route::redirect('/purchases/returns', '/app/purchases/returns');
    Route::redirect('/inventory/products', '/app/inventory/products');
    Route::redirect('/inventory/categories', '/app/inventory/categories');
    Route::redirect('/inventory/units', '/app/inventory/units');
    Route::redirect('/warehouse', '/app/warehouse');
    Route::redirect('/accounting', '/app/accounting');
    Route::redirect('/expenses', '/app/expenses');
    Route::redirect('/income', '/app/income');
    Route::redirect('/hrm/employees', '/app/hrm/employees');
    Route::redirect('/hrm/attendance', '/app/hrm/attendance');
    Route::redirect('/hrm/payroll', '/app/hrm/payroll');
    Route::redirect('/rental/units', '/app/rental/units');
    Route::redirect('/rental/contracts', '/app/rental/contracts');
    Route::redirect('/rental/properties', '/app/rental/properties');
    Route::redirect('/rental/tenants', '/app/rental/tenants');
    Route::redirect('/manufacturing/boms', '/app/manufacturing/boms');
    Route::redirect('/manufacturing/orders', '/app/manufacturing/orders');
    Route::redirect('/reports', '/admin/reports');
});
