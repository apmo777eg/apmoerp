<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductStoreMappings extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    protected string $paginationTheme = 'tailwind';

    public ?int $productId = null;

    public ?Product $product = null;

    public string $search = '';

    public ?int $storeFilter = null;

    /**
     * @var array<int, array{id:int,name:string,type:string}>
     */
    public array $stores = [];

    /**
     * IMPORTANT:
     * Route parameter name is {product} (see routes/web.php).
     * Livewire injects route params into mount() by name.
     */
    public function mount(?int $product = null): void
    {
        $this->authorize('inventory.products.view');

        if (! $product) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->product = Product::with('category')->findOrFail($this->productId);

        $this->loadStores();
    }

    protected function loadStores(): void
    {
        if (! $this->product) {
            $this->stores = [];

            return;
        }

        // Stores are branch-owned. Only show stores for the product's branch.
        $stores = Store::query()
            ->where('branch_id', $this->product->branch_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $this->stores = $stores->map(fn (Store $store): array => [
            'id' => (int) $store->id,
            'name' => (string) $store->name,
            'type' => (string) ($store->type ?? ''),
        ])->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStoreFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        // Store mappings are edited as part of product updates
        $this->authorize('inventory.products.update');

        $mapping = ProductStoreMapping::query()
            ->where('product_id', $this->productId)
            ->findOrFail($id);

        $mapping->delete();

        session()->flash('success', __('Store mapping deleted successfully.'));
    }

    public function render()
    {
        $query = ProductStoreMapping::query()
            ->with('store')
            ->where('product_id', $this->productId);

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('external_id', 'like', '%'.$search.'%')
                    ->orWhere('external_sku', 'like', '%'.$search.'%');
            });
        }

        if ($this->storeFilter) {
            $query->where('store_id', $this->storeFilter);
        }

        $mappings = $query->orderByDesc('created_at')->paginate(15);

        return view('livewire.inventory.product-store-mappings', [
            'mappings' => $mappings,
            'product' => $this->product,
            'productId' => $this->productId,
            'stores' => $this->stores,
        ]);
    }
}
