<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Livewire\Component;

class CommandPalette extends Component
{
    public string $query = '';

    public int $selectedIndex = 0;

    public array $results = [];

    public array $recentSearches = [];

    /**
     * Maximum number of results to return
     */
    protected int $maxResults = 10;

    /**
     * Maximum number of recent searches to store
     */
    protected int $maxRecentSearches = 5;

    public function mount(): void
    {
        $this->loadRecentSearches();
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->selectedIndex = 0;

            return;
        }

        $this->results = $this->search();
        $this->selectedIndex = 0;
    }

    /**
     * Load recent searches from user preferences
     */
    protected function loadRecentSearches(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $this->recentSearches = $preferences['recent_searches'] ?? [];
    }

    /**
     * Save a search term to recent searches
     */
    protected function saveRecentSearch(string $term, string $type, string $url): void
    {
        $user = auth()->user();
        if (! $user || strlen($term) < 2) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $recentSearches = $preferences['recent_searches'] ?? [];

        // Create search entry
        $entry = [
            'term' => $term,
            'type' => $type,
            'url' => $url,
            'timestamp' => now()->toISOString(),
        ];

        // Remove duplicate if exists
        $recentSearches = array_filter($recentSearches, fn ($s) => $s['term'] !== $term || $s['type'] !== $type);

        // Add to beginning
        array_unshift($recentSearches, $entry);

        // Limit to max
        $recentSearches = array_slice($recentSearches, 0, $this->maxRecentSearches);

        // Save
        $preferences['recent_searches'] = $recentSearches;
        $user->preferences = $preferences;
        $user->save();

        $this->recentSearches = $recentSearches;
    }

    /**
     * Clear recent searches
     */
    public function clearRecentSearches(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $preferences['recent_searches'] = [];
        $user->preferences = $preferences;
        $user->save();

        $this->recentSearches = [];
    }

    protected function search(): array
    {
        $query = $this->query;
        $branchId = auth()->user()?->branch_id;
        $results = [];

        // Quick actions (commands starting with >)
        if (str_starts_with($query, '>')) {
            return $this->searchQuickActions(substr($query, 1));
        }

        // Search Products
        if (auth()->user()?->can('inventory.products.view')) {
            $products = Product::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get()
                ->map(fn ($p) => [
                    'type' => 'Product',
                    'icon' => '📦',
                    'name' => $p->name,
                    'subtitle' => $p->sku ?? '',
                    'url' => route('app.inventory.products.show', $p),
                ]);
            $results = array_merge($results, $products->toArray());
        }

        // Search Customers
        if (auth()->user()?->can('customers.view')) {
            $customers = Customer::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'type' => 'Customer',
                    'icon' => '👤',
                    'name' => $c->name,
                    'subtitle' => $c->phone ?? $c->email ?? '',
                    'url' => route('customers.index', ['search' => $c->name]),
                ]);
            $results = array_merge($results, $customers->toArray());
        }

        // Search Suppliers
        if (auth()->user()?->can('suppliers.view')) {
            $suppliers = Supplier::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'type' => 'Supplier',
                    'icon' => '🏢',
                    'name' => $s->name,
                    'subtitle' => $s->phone ?? $s->email ?? '',
                    'url' => route('suppliers.index', ['search' => $s->name]),
                ]);
            $results = array_merge($results, $suppliers->toArray());
        }

        // Search Sales/Invoices
        if (auth()->user()?->can('sales.view')) {
            $sales = Sale::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where(function ($q) use ($query) {
                    $q->where('id', 'like', "%{$query}%")
                        ->orWhere('reference_number', 'like', "%{$query}%");
                })
                ->with('customer')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'type' => 'Invoice',
                    'icon' => '🧾',
                    'name' => $s->reference_no ?? "Invoice #{$s->id}",
                    'subtitle' => $s->customer?->name ?? __('Walk-in Customer'),
                    'url' => route('app.sales.show', $s),
                ]);
            $results = array_merge($results, $sales->toArray());
        }

        return array_slice($results, 0, $this->maxResults);
    }

    /**
     * Search quick actions (commands)
     */
    protected function searchQuickActions(string $query): array
    {
        $actions = [
            ['name' => __('New Sale'), 'icon' => '➕', 'url' => route('app.sales.create'), 'permission' => 'sales.manage', 'keywords' => 'new sale create invoice'],
            ['name' => __('New Purchase'), 'icon' => '📥', 'url' => route('app.purchases.create'), 'permission' => 'purchases.manage', 'keywords' => 'new purchase create order'],
            ['name' => __('New Product'), 'icon' => '📦', 'url' => route('app.inventory.products.create'), 'permission' => 'inventory.products.create', 'keywords' => 'new product create item'],
            ['name' => __('New Customer'), 'icon' => '👤', 'url' => route('customers.create'), 'permission' => 'customers.manage', 'keywords' => 'new customer create client'],
            ['name' => __('Dashboard'), 'icon' => '📊', 'url' => route('dashboard'), 'permission' => 'dashboard.view', 'keywords' => 'dashboard home main'],
            ['name' => __('Settings'), 'icon' => '⚙️', 'url' => route('admin.settings'), 'permission' => 'settings.view', 'keywords' => 'settings config preferences'],
            ['name' => __('Reports'), 'icon' => '📈', 'url' => route('admin.reports.index'), 'permission' => 'reports.view', 'keywords' => 'reports analytics statistics'],
            ['name' => __('POS Terminal'), 'icon' => '💳', 'url' => route('pos.terminal'), 'permission' => 'pos.use', 'keywords' => 'pos terminal cashier register'],
        ];

        $user = auth()->user();
        $results = [];

        foreach ($actions as $action) {
            // Check permission
            if (! $user?->can($action['permission'])) {
                continue;
            }

            // Check if matches query
            if (empty($query) || str_contains(strtolower($action['keywords']), strtolower($query)) || str_contains(strtolower($action['name']), strtolower($query))) {
                $results[] = [
                    'type' => 'Action',
                    'icon' => $action['icon'],
                    'name' => $action['name'],
                    'subtitle' => __('Quick Action'),
                    'url' => $action['url'],
                ];
            }
        }

        return array_slice($results, 0, $this->maxResults);
    }

    public function selectResult(int $index): void
    {
        if (isset($this->results[$index])) {
            $result = $this->results[$index];

            // Save to recent searches (only for non-action results)
            if ($result['type'] !== 'Action' && ! empty($this->query)) {
                $this->saveRecentSearch($result['name'], $result['type'], $result['url']);
            }

            $this->redirect($result['url'], navigate: true);
        }
    }

    public function moveDown(): void
    {
        if ($this->selectedIndex < count($this->results) - 1) {
            $this->selectedIndex++;
        }
    }

    public function moveUp(): void
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    public function render()
    {
        return view('livewire.command-palette');
    }
}
