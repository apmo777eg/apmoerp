<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Store;

use App\Models\Branch;
use App\Models\Store;
use App\Models\StoreSyncLog;
use App\Services\Store\StoreSyncService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Stores extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public ?string $typeFilter = null;

    public ?string $statusFilter = null;

    public bool $showSyncModal = false;

    public ?int $syncingStoreId = null;

    public array $branches = [];

    public array $syncLogs = [];

    protected array $storeTypes = [
        'shopify' => 'Shopify',
        'woocommerce' => 'WooCommerce',
        'laravel' => 'Laravel API',
        'custom' => 'Custom API',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('stores.view')) {
            abort(403);
        }

        // Select name_ar only if column exists (defensive for older schemas)
        $columns = ['id', 'name'];
        if (Schema::hasColumn('branches', 'name_ar')) {
            $columns[] = 'name_ar';
        }

        $this->branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get($columns)
            ->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('stores.delete');
        
        $store = Store::findOrFail($id);
        $store->delete();

        session()->flash('success', __('Store deleted successfully'));
    }

    public function toggleStatus(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('stores.edit');
        
        $store = Store::findOrFail($id);
        $store->update(['is_active' => ! $store->is_active]);

        if ($store->integration) {
            $store->integration->update(['is_active' => $store->is_active]);
        }
    }

    public function openSyncModal(int $storeId): void
    {
        $this->syncingStoreId = $storeId;
        $this->loadSyncLogs();
        $this->showSyncModal = true;
    }

    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
        $this->syncingStoreId = null;
        $this->syncLogs = [];
    }

    protected function loadSyncLogs(): void
    {
        if ($this->syncingStoreId) {
            $this->syncLogs = StoreSyncLog::where('store_id', $this->syncingStoreId)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->toArray();
        }
    }

    public function syncProducts(): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('stores.sync');
        
        if (! $this->syncingStoreId) {
            return;
        }

        $store = Store::findOrFail($this->syncingStoreId);
        $service = new StoreSyncService;

        try {
            if ($store->isShopify()) {
                $log = $service->pullProductsFromShopify($store);
            } elseif ($store->isWooCommerce()) {
                $log = $service->pullProductsFromWooCommerce($store);
            } elseif ($store->isLaravel()) {
                $log = $service->pullProductsFromLaravel($store);
            } else {
                session()->flash('error', __('Sync not supported for this store type'));

                return;
            }

            $this->loadSyncLogs();
            session()->flash('success', __('Products synced: ').$log->records_success.' / '.($log->records_success + $log->records_failed));

        } catch (\Exception $e) {
            session()->flash('error', __('Sync failed: ').$e->getMessage());
        }
    }

    public function syncInventory(): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('stores.sync');
        
        if (! $this->syncingStoreId) {
            return;
        }

        $store = Store::findOrFail($this->syncingStoreId);
        $service = new StoreSyncService;

        try {
            if ($store->isShopify()) {
                $log = $service->pushStockToShopify($store);
            } elseif ($store->isWooCommerce()) {
                $log = $service->pushStockToWooCommerce($store);
            } elseif ($store->isLaravel()) {
                $log = $service->pushStockToLaravel($store);
            } else {
                session()->flash('error', __('Sync not supported for this store type'));

                return;
            }

            $this->loadSyncLogs();
            session()->flash('success', __('Inventory synced: ').$log->records_success.' / '.($log->records_success + $log->records_failed));

        } catch (\Exception $e) {
            session()->flash('error', __('Sync failed: ').$e->getMessage());
        }
    }

    public function syncOrders(): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('stores.sync');
        
        if (! $this->syncingStoreId) {
            return;
        }

        $store = Store::findOrFail($this->syncingStoreId);
        $service = new StoreSyncService;

        try {
            if ($store->isShopify()) {
                $log = $service->pullOrdersFromShopify($store);
            } elseif ($store->isWooCommerce()) {
                $log = $service->pullOrdersFromWooCommerce($store);
            } elseif ($store->isLaravel()) {
                $log = $service->pullOrdersFromLaravel($store);
            } else {
                session()->flash('error', __('Sync not supported for this store type'));

                return;
            }

            $this->loadSyncLogs();
            session()->flash('success', __('Orders synced: ').$log->records_success.' / '.($log->records_success + $log->records_failed));

        } catch (\Exception $e) {
            session()->flash('error', __('Sync failed: ').$e->getMessage());
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $query = Store::with(['branch', 'integration', 'syncLogs' => fn ($q) => $q->latest()->limit(1)]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('url', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter !== null) {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        $stores = $query->orderByDesc('created_at')->paginate(15);

        return view('livewire.admin.store.stores', [
            'stores' => $stores,
            'storeTypes' => $this->storeTypes,
        ]);
    }
}
