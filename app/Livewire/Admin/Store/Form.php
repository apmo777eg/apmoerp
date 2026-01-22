<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Store;

use App\Models\Branch;
use App\Models\Store;
use App\Models\StoreIntegration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    public ?int $storeId = null;

    public string $name = '';

    public string $type = 'shopify';

    public string $url = '';

    public ?int $branch_id = null;

    public bool $is_active = true;

    public array $settings = [];

    public string $api_key = '';

    public string $api_secret = '';

    public string $access_token = '';

    public string $webhook_secret = '';

    public array $sync_settings = [
        'sync_products' => true,
        'sync_inventory' => true,
        'sync_orders' => true,
        'sync_customers' => false,
        'auto_sync' => false,
        'sync_interval' => 60,
        'sync_modules' => [],
        'sync_categories' => [],
    ];

    public array $branches = [];

    protected array $storeTypes = [
        'shopify' => 'Shopify',
        'woocommerce' => 'WooCommerce',
        'laravel' => 'Laravel API',
        'custom' => 'Custom API',
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:shopify,woocommerce,laravel,custom',
            'url' => 'required|url|max:500',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'access_token' => 'nullable|string|max:1000',
            'webhook_secret' => 'nullable|string|max:255',
        ];
    }

    public function mount(?int $store = null): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('stores.view')) {
            abort(403);
        }

        $columns = ['id', 'name'];
        if (Schema::hasColumn('branches', 'name_ar')) {
            $columns[] = 'name_ar';
        }

        $this->branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get($columns)
            ->toArray();

        if ($store) {
            $this->storeId = $store;
            $this->loadStore();
        }
    }

    protected function loadStore(): void
    {
        $store = Store::with('integration')->findOrFail($this->storeId);

        $this->name = $store->name;
        $this->type = $store->type;
        $this->url = $store->url;
        $this->branch_id = $store->branch_id;
        $this->is_active = $store->is_active;
        $this->settings = $store->settings ?? [];
        $this->sync_settings = array_merge($this->sync_settings, $store->settings['sync'] ?? []);
        $this->sanitizeSyncSettings();

        if ($store->integration) {
            $this->api_key = $store->integration->api_key ?? '';
            $this->api_secret = $store->integration->api_secret ?? '';
            $this->access_token = $store->integration->access_token ?? '';
            $this->webhook_secret = $store->integration->webhook_secret ?? '';
        }
    }

    protected function sanitizeSyncSettings(): void
    {
        $this->sync_settings['sync_modules'] = collect($this->sync_settings['sync_modules'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->sync_settings['sync_categories'] = collect($this->sync_settings['sync_categories'] ?? [])
            ->filter(fn ($category) => $category !== null && $category !== '')
            ->values()
            ->all();
    }

    public function save(): void
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $user = Auth::user();
        if (! $user || ! $user->can('stores.view')) {
            abort(403);
        }

        $this->validate();
        $this->sanitizeSyncSettings();

        DB::beginTransaction();

        try {
            $storeData = [
                'name' => $this->name,
                'type' => $this->type,
                'url' => rtrim($this->url, '/'),
                'branch_id' => $this->branch_id,
                'is_active' => $this->is_active,
                'settings' => array_merge($this->settings, ['sync' => $this->sync_settings]),
            ];

            if ($this->storeId) {
                $store = Store::findOrFail($this->storeId);
                $store->update($storeData);
            } else {
                $store = Store::create($storeData);
            }

            $integrationData = [
                'platform' => $this->type,
                'is_active' => $this->is_active,
            ];

            if ($this->api_key) {
                $integrationData['api_key'] = $this->api_key;
            }
            if ($this->api_secret) {
                $integrationData['api_secret'] = $this->api_secret;
            }
            if ($this->access_token) {
                $integrationData['access_token'] = $this->access_token;
            }
            if ($this->webhook_secret) {
                $integrationData['webhook_secret'] = $this->webhook_secret;
            }

            StoreIntegration::updateOrCreate(
                ['store_id' => $store->id],
                $integrationData
            );

            DB::commit();

            session()->flash('success', $this->storeId ? __('Store updated successfully') : __('Store created successfully'));

            $this->redirectRoute('admin.stores.index', navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Error saving store: ').$e->getMessage());
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $moduleQuery = \App\Models\Module::where('is_active', true)
            ->where('has_inventory', true)
            ->where('supports_items', true);

        if ($this->branch_id) {
            $enabledModuleIds = \App\Models\BranchModule::where('branch_id', $this->branch_id)
                ->where('enabled', true)
                ->pluck('module_id')
                ->filter()
                ->all();

            if (! empty($enabledModuleIds)) {
                $moduleQuery->whereIn('id', $enabledModuleIds);
            }
        }

        $modules = $moduleQuery->orderBy('name')
            ->get(['id', 'name', 'name_ar']);

        return view('livewire.admin.store.form', [
            'storeTypes' => $this->storeTypes,
            'modules' => $modules,
        ]);
    }
}
