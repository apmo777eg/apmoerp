<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Store;

use App\Models\Branch;
use App\Models\Store;
use App\Models\StoreIntegration;
use App\Models\StoreToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
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

    /**
     * Store API Tokens (ERP Store API)
     */
    public array $tokens = [];

    public bool $showTokenModal = false;

    public string $token_name = '';

    public array $token_abilities = [];

    public ?string $token_expires_at = null;

    /**
     * Plain token shown once after creation
     */
    public ?string $generated_token = null;

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
            'branch_id' => 'required|exists:branches,id',
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

        // Default branch for new stores (store API is branch-scoped)
        if (! $store && $user && $user->branch_id) {
            $this->branch_id = (int) $user->branch_id;
        }

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

        $this->loadTokens();
    }

    protected function loadTokens(): void
    {
        if (! $this->storeId) {
            $this->tokens = [];
            return;
        }

        $this->tokens = StoreToken::query()
            ->where('store_id', $this->storeId)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])
            ->map(fn (StoreToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'abilities' => $t->abilities ?? [],
                'last_used_at' => $t->last_used_at?->toDateTimeString(),
                'expires_at' => $t->expires_at?->toDateTimeString(),
                'created_at' => $t->created_at?->toDateTimeString(),
                'is_expired' => $t->isExpired(),
            ])
            ->toArray();
    }

    protected function tokenAbilityOptions(): array
    {
        return [
            '*' => __('Full access (all abilities)'),
            'products.read' => __('Products: Read'),
            'products.write' => __('Products: Write'),
            'inventory.read' => __('Inventory: Read'),
            'inventory.write' => __('Inventory: Write'),
            'orders.read' => __('Orders: Read'),
            'orders.write' => __('Orders: Write'),
            'customers.read' => __('Customers: Read'),
            'customers.write' => __('Customers: Write'),
        ];
    }

    public function openTokenModal(): void
    {
        $this->resetErrorBag();
        $this->generated_token = null;
        $this->token_name = '';
        $this->token_abilities = [];
        $this->token_expires_at = null;
        $this->showTokenModal = true;
    }

    public function closeTokenModal(): void
    {
        $this->showTokenModal = false;
        $this->generated_token = null;
    }

    public function createToken(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('stores.manage')) {
            abort(403);
        }

        if (! $this->storeId) {
            $this->dispatch('notify', type: 'error', message: __('Please save the store first.'));
            return;
        }

        $this->validate([
            'token_name' => 'required|string|max:100',
            'token_abilities' => 'array',
            'token_abilities.*' => 'string',
            'token_expires_at' => 'nullable|date',
        ]);

        $expiresAt = null;
        if ($this->token_expires_at) {
            $expiresAt = Carbon::parse($this->token_expires_at)->endOfDay();
            if ($expiresAt->isPast()) {
                $this->addError('token_expires_at', __('Expiry date must be in the future.'));
                $this->dispatch('notify', type: 'error', message: __('Please correct the expiry date.'));
                return;
            }
        }

        $abilities = array_values(array_unique(array_filter($this->token_abilities ?? [], fn ($a) => $a !== null && $a !== '')));
        if (empty($abilities)) {
            $abilities = ['*'];
        }
        if (in_array('*', $abilities, true)) {
            $abilities = ['*'];
        }

        $store = Store::findOrFail($this->storeId);
        $token = $store->generateApiToken($this->token_name, $abilities, $expiresAt);

        // Show the plain token ONCE (copy it now)
        $this->generated_token = $token->token;
        $this->dispatch('notify', type: 'success', message: __('Token generated. Copy it now - it will not be shown again.'));

        $this->loadTokens();
    }

    public function revokeToken(int $tokenId): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('stores.manage')) {
            abort(403);
        }

        if (! $this->storeId) {
            return;
        }

        $token = StoreToken::query()
            ->where('id', $tokenId)
            ->where('store_id', $this->storeId)
            ->firstOrFail();

        $token->delete();
        $this->dispatch('notify', type: 'success', message: __('Token revoked successfully.'));
        $this->loadTokens();
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
        if (! $user || ! $user->can('stores.manage')) {
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

            // Ensure integration is stored under the SAME branch as the store.
            // Do not rely on BranchContextManager here (Super Admin may have no explicit branch context).
            StoreIntegration::withoutBranchScope()->updateOrCreate(
                ['store_id' => $store->id],
                array_merge($integrationData, ['branch_id' => $store->branch_id])
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
            'tokenAbilityOptions' => $this->tokenAbilityOptions(),
        ]);
    }
}
