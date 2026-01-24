<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\HREmployee;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RentalContract;
use App\Models\Sale;
use App\Models\SearchHistory;
use App\Models\SearchIndex;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class GlobalSearchService
{
    /**
     * Searchable models configuration.
     */
    private const SEARCHABLE_MODELS = [
        'products' => [
            'model' => Product::class,
            'title' => ['name', 'sku'],
            'content' => ['description', 'barcode'],
            'icon' => '📦',
            'route' => 'products.show',
            'module' => 'inventory',
            'filters' => ['module_id', 'category_id', 'status'],
        ],
        'motorcycles' => [
            'model' => Product::class,
            'title' => ['name', 'sku'],
            'content' => ['description'],
            'icon' => '🏍️',
            'route' => 'products.show',
            'module' => 'motorcycle',
            'filters' => ['brand', 'model', 'year'],
            'module_filter' => 'motorcycle',
        ],
        'spares' => [
            'model' => Product::class,
            'title' => ['name', 'sku'],
            'content' => ['description'],
            'icon' => '🔧',
            'route' => 'products.show',
            'module' => 'spares',
            'filters' => ['part_number', 'compatible_vehicles'],
            'module_filter' => 'spares',
        ],
        'wood_products' => [
            'model' => Product::class,
            'title' => ['name', 'sku'],
            'content' => ['description'],
            'icon' => '🪵',
            'route' => 'products.show',
            'module' => 'wood',
            'filters' => ['wood_type', 'dimensions'],
            'module_filter' => 'wood',
        ],
        'rental_units' => [
            'model' => Product::class,
            'title' => ['name', 'sku'],
            'content' => ['description'],
            'icon' => '🏠',
            'route' => 'products.show',
            'module' => 'rental',
            'filters' => ['property_type', 'location'],
            'module_filter' => 'rental',
        ],
        'materials' => [
            'model' => Product::class,
            'title' => ['name', 'sku'],
            'content' => ['description'],
            'icon' => '🏭',
            'route' => 'products.show',
            'module' => 'manufacturing',
            'filters' => ['material_type', 'quality_grade'],
            'module_filter' => 'manufacturing',
        ],
        'customers' => [
            'model' => Customer::class,
            'title' => ['name', 'email'],
            'content' => ['phone', 'address'],
            'icon' => '👤',
            'route' => 'customers.show',
            'module' => 'customers',
            'filters' => ['status', 'customer_type'],
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'title' => ['name', 'email'],
            'content' => ['phone', 'address'],
            'icon' => '🏭',
            'route' => 'suppliers.show',
            'module' => 'suppliers',
            'filters' => ['status'],
        ],
        'sales' => [
            'model' => Sale::class,
            'title' => ['code'],
            'content' => ['notes'],
            'icon' => '💵',
            'route' => 'sales.show',
            'module' => 'sales',
            'filters' => ['status', 'payment_status', 'date_range'],
        ],
        'purchases' => [
            'model' => Purchase::class,
            'title' => ['code'],
            'content' => ['notes'],
            'icon' => '🛒',
            'route' => 'purchases.show',
            'module' => 'purchases',
            'filters' => ['status', 'supplier_id', 'date_range'],
        ],
        'rental_contracts' => [
            'model' => RentalContract::class,
            'title' => ['contract_number'],
            'content' => ['notes'],
            'icon' => '📋',
            'route' => 'rentals.contracts.show',
            'module' => 'rentals',
            'filters' => ['status', 'tenant_id', 'unit_id'],
        ],
        'employees' => [
            'model' => HREmployee::class,
            'title' => ['name', 'code', 'email'],
            'content' => ['phone', 'position'],
            'icon' => '👨‍💼',
            'route' => 'hrm.employees.show',
            'module' => 'hrm',
            'filters' => ['department', 'status', 'position'],
        ],
    ];

    private const MODULE_PERMISSIONS = [
        'inventory' => 'inventory.products.view',
        'motorcycle' => 'inventory.products.view',
        'spares' => 'inventory.products.view',
        'wood' => 'inventory.products.view',
        'rental' => 'inventory.products.view',
        'manufacturing' => 'manufacturing.view',
        'customers' => 'customers.view',
        'suppliers' => 'suppliers.view',
        'sales' => 'sales.view',
        'purchases' => 'purchases.view',
        'rentals' => 'rental.contracts.view',
        'hrm' => 'hrm.employees.view',
    ];

    /**
     * Module-specific search filters
     */
    private const MODULE_FILTERS = [
        'motorcycle' => [
            'brand' => ['label' => 'Brand', 'label_ar' => 'الماركة', 'type' => 'text'],
            'model' => ['label' => 'Model', 'label_ar' => 'الموديل', 'type' => 'text'],
            'year' => ['label' => 'Year', 'label_ar' => 'السنة', 'type' => 'number'],
        ],
        'spares' => [
            'part_number' => ['label' => 'Part Number', 'label_ar' => 'رقم القطعة', 'type' => 'text'],
            'oem_number' => ['label' => 'OEM Number', 'label_ar' => 'رقم OEM', 'type' => 'text'],
            'compatible_vehicles' => ['label' => 'Compatible Vehicles', 'label_ar' => 'المركبات المتوافقة', 'type' => 'text'],
        ],
        'wood' => [
            'wood_type' => ['label' => 'Wood Type', 'label_ar' => 'نوع الخشب', 'type' => 'select', 'options' => ['pine', 'oak', 'beech', 'mdf', 'plywood']],
            'grade' => ['label' => 'Grade', 'label_ar' => 'الدرجة', 'type' => 'select', 'options' => ['A', 'B', 'C']],
        ],
        'rental' => [
            'property_type' => ['label' => 'Property Type', 'label_ar' => 'نوع العقار', 'type' => 'select', 'options' => ['apartment', 'villa', 'office', 'shop', 'warehouse']],
            'location' => ['label' => 'Location', 'label_ar' => 'الموقع', 'type' => 'text'],
            'furnished' => ['label' => 'Furnished', 'label_ar' => 'مفروش', 'type' => 'boolean'],
        ],
        'manufacturing' => [
            'material_type' => ['label' => 'Material Type', 'label_ar' => 'نوع المادة', 'type' => 'select', 'options' => ['raw_material', 'component', 'sub_assembly', 'finished_good']],
            'quality_grade' => ['label' => 'Quality Grade', 'label_ar' => 'درجة الجودة', 'type' => 'select', 'options' => ['A', 'B', 'C']],
        ],
        'sales' => [
            'status' => ['label' => 'Status', 'label_ar' => 'الحالة', 'type' => 'select', 'options' => ['draft', 'confirmed', 'delivered', 'cancelled']],
            'payment_status' => ['label' => 'Payment Status', 'label_ar' => 'حالة الدفع', 'type' => 'select', 'options' => ['pending', 'partial', 'paid']],
        ],
        'purchases' => [
            'status' => ['label' => 'Status', 'label_ar' => 'الحالة', 'type' => 'select', 'options' => ['draft', 'pending', 'approved', 'received', 'cancelled']],
        ],
        'hrm' => [
            'department' => ['label' => 'Department', 'label_ar' => 'القسم', 'type' => 'text'],
            'status' => ['label' => 'Status', 'label_ar' => 'الحالة', 'type' => 'select', 'options' => ['active', 'inactive', 'on_leave']],
        ],
    ];

    /**
     * Get available filters for a module
     */
    public function getFiltersForModule(string $module): array
    {
        return self::MODULE_FILTERS[$module] ?? [];
    }

    /**
     * Perform global search.
     */
    public function search(string $query, User $user, ?int $branchId = null, ?string $module = null): array
    {
        if (strlen($query) < 2) {
            return ['results' => [], 'count' => 0];
        }

        $resolvedBranchId = $this->resolveBranchId($user, $branchId);
        $authorizedModules = $this->authorizedModulesForUser($user, $module);

        // Log search if user provided
        $this->logSearch($user->id, $query, $module);

        // Search in index
        $results = SearchIndex::search($query, $resolvedBranchId, $authorizedModules, 50);

        // Group by module
        $grouped = collect($results)->groupBy('module')->map(function ($items) {
            return $items->take(10)->toArray();
        })->toArray();

        return [
            'results' => $results,
            'grouped' => $grouped,
            'count' => count($results),
        ];
    }

    /**
     * Index a model instance.
     */
    public function indexModel($model): void
    {
        $config = $this->getModelConfig($model);

        if (! $config) {
            return;
        }

        $title = $this->extractFields($model, $config['title']);
        $content = $this->extractFields($model, $config['content']);

        SearchIndex::updateOrCreate(
            [
                'searchable_type' => get_class($model),
                'searchable_id' => $model->id,
            ],
            [
                'branch_id' => $model->branch_id ?? config('app.default_branch_id', 1),
                'title' => $title,
                'content' => $content,
                'module' => $config['module'],
                'icon' => $config['icon'],
                'url' => $this->generateUrl($model, $config['route']),
                'metadata' => $this->extractMetadata($model),
                'indexed_at' => now(),
            ]
        );
    }

    /**
     * Remove model from index.
     */
    public function removeFromIndex($model): void
    {
        SearchIndex::where('searchable_type', get_class($model))
            ->where('searchable_id', $model->id)
            ->delete();
    }

    /**
     * Reindex all searchable models.
     */
    public function reindexAll(?int $branchId = null): int
    {
        $indexed = 0;

        foreach (self::SEARCHABLE_MODELS as $config) {
            $query = $config['model']::query();

            if ($branchId && method_exists($config['model'], 'branch')) {
                $query->where('branch_id', $branchId);
            }

            $query->chunk(100, function ($models) use (&$indexed) {
                foreach ($models as $model) {
                    $this->indexModel($model);
                    $indexed++;
                }
            });
        }

        return $indexed;
    }

    /**
     * Get recent searches for user.
     */
    public function getRecentSearches(int $userId, int $limit = 10): array
    {
        return SearchHistory::getRecentSearches($userId, $limit);
    }

    /**
     * Get popular searches.
     */
    public function getPopularSearches(int $limit = 10): array
    {
        return SearchHistory::getPopularSearches($limit);
    }

    /**
     * Clear search history for user.
     */
    public function clearHistory(int $userId): void
    {
        SearchHistory::where('user_id', $userId)->delete();
    }

    /**
     * Get available modules for filtering.
     */
    public function getAvailableModules(User $user): array
    {
        return $this->authorizedModulesForUser($user);
    }

    /**
     * Log a search query.
     */
    private function logSearch(int $userId, string $query, ?string $module): void
    {
        SearchHistory::create([
            'user_id' => $userId,
            'query' => $query,
            'module' => $module,
            'results_count' => 0, // Updated after search completes
        ]);
    }

    /**
     * Get model configuration.
     */
    private function getModelConfig($model): ?array
    {
        $class = get_class($model);

        foreach (self::SEARCHABLE_MODELS as $config) {
            if ($config['model'] === $class) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Extract fields from model.
     */
    private function extractFields($model, array $fields): string
    {
        $values = [];

        foreach ($fields as $field) {
            if (isset($model->$field) && ! empty($model->$field)) {
                $values[] = $model->$field;
            }
        }

        return implode(' ', $values);
    }

    /**
     * Generate URL for model.
     */
    private function generateUrl($model, string $route): string
    {
        try {
            return route($route, $model->id);
        } catch (\Exception $e) {
            return '#';
        }
    }

    /**
     * Extract metadata from model.
     */
    private function extractMetadata($model): array
    {
        $metadata = [
            'id' => $model->id,
        ];

        // Add common fields if available
        if (isset($model->status)) {
            $metadata['status'] = $model->status;
        }

        if (isset($model->created_at)) {
            $metadata['created_at'] = $model->created_at->toISOString();
        }

        return $metadata;
    }

    private function resolveBranchId(User $user, ?int $branchId): int
    {
        $resolvedBranch = $branchId ?? $user->current_branch_id ?? $user->branch_id;

        if (! $resolvedBranch) {
            throw new AuthorizationException('Branch context is required for search.');
        }

        return (int) $resolvedBranch;
    }

    /**
     * @return array<string>
     */
    private function authorizedModulesForUser(User $user, ?string $module = null): array
    {
        $modules = [];

        foreach (self::MODULE_PERMISSIONS as $moduleKey => $permission) {
            if ($user->can($permission)) {
                $modules[] = $moduleKey;
            }
        }

        if ($module) {
            if (! in_array($module, $modules, true)) {
                throw new AuthorizationException('You are not allowed to search this module.');
            }

            return [$module];
        }

        if (empty($modules)) {
            throw new AuthorizationException('You are not allowed to perform searches.');
        }

        return $modules;
    }
}
