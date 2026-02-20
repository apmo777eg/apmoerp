<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Auth;
use App\Livewire\BaseComponent as Component;
class QuickActions extends Component
{
    public array $actions = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->actions = [
            [
                'title' => __('New Sale'),
                'description' => __('Create a new sales invoice'),
                'icon' => '💰',
                'route' => 'app.sales.create',
                'permission' => 'sales.create',
                'color' => 'emerald',
            ],
            [
                'title' => __('New Purchase'),
                'description' => __('Create a new purchase order'),
                'icon' => '🛒',
                'route' => 'app.purchases.create',
                'permission' => 'purchases.create',
                'color' => 'blue',
            ],
            [
                'title' => __('Add Product'),
                'description' => __('Add new product to inventory'),
                'icon' => '📦',
                'route' => 'app.inventory.products.create',
                'permission' => 'inventory.products.create',
                'color' => 'purple',
            ],
            [
                'title' => __('Stock Adjustment'),
                'description' => __('Adjust inventory stock levels'),
                'icon' => '📊',
                'route' => 'app.warehouse.adjustments.create',
                'permission' => 'stock.adjust',
                'color' => 'orange',
            ],
            [
                'title' => __('Add Customer'),
                'description' => __('Register a new customer'),
                'icon' => '👤',
                'route' => 'customers.create',
                'permission' => 'customers.create',
                'color' => 'cyan',
            ],
            [
                'title' => __('Add Supplier'),
                'description' => __('Register a new supplier'),
                'icon' => '🏭',
                'route' => 'suppliers.create',
                'permission' => 'suppliers.create',
                'color' => 'violet',
            ],
            [
                'title' => __('Record Expense'),
                'description' => __('Record a new expense'),
                'icon' => '💸',
                'route' => 'app.expenses.create',
                'permission' => 'expenses.create',
                'color' => 'red',
            ],
            [
                'title' => __('Record Income'),
                'description' => __('Record a new income'),
                'icon' => '💵',
                'route' => 'app.income.create',
                'permission' => 'income.create',
                'color' => 'green',
            ],
            [
                'title' => __('POS Terminal'),
                'description' => __('Open point of sale'),
                'icon' => '🧾',
                'route' => 'pos.terminal',
                'permission' => 'pos.use',
                'color' => 'amber',
            ],
            [
                'title' => __('Import Data'),
                'description' => __('Import products, customers, etc.'),
                'icon' => '📥',
                'route' => 'admin.bulk-import',
                'permission' => 'products.import',
                'color' => 'indigo',
            ],
            [
                'title' => __('View Reports'),
                'description' => __('Access reports and analytics'),
                'icon' => '📈',
                'route' => 'admin.reports.index',
                'permission' => 'reports.view',
                'color' => 'pink',
            ],
            [
                'title' => __('Settings'),
                'description' => __('Configure system settings'),
                'icon' => '⚙️',
                'route' => 'admin.settings',
                'permission' => 'settings.view',
                'color' => 'slate',
            ],
        ];

        // Filter actions based on user permissions and route availability
        $this->actions = collect($this->actions)->filter(function ($action) use ($user) {
            $hasPermission = ! isset($action['permission']) || $user->can($action['permission']);
            $routeExists = \Route::has($action['route']);

            return $hasPermission && $routeExists;
        })->values()->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.quick-actions');
    }
}
