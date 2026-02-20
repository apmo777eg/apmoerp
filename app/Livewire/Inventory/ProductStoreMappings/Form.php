<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\ProductStoreMappings;

use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Store;
use App\Rules\BranchScopedExists;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?int $mappingId = null;

    public ?int $productId = null;

    public ?Product $product = null;

    public ?int $store_id = null;

    public string $external_id = '';

    public string $external_sku = '';

    /**
     * @var array<int, array{id:int,name:string,type:string}>
     */
    public array $stores = [];

    public function mount(?int $product = null, ?int $mapping = null): void
    {
        $this->authorize('inventory.products.view');

        if (! $product) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->product = Product::findOrFail($this->productId);

        if ($mapping) {
            $this->mappingId = (int) $mapping;
            $this->loadMapping();
        }

        $this->loadStores();
    }

    protected function loadStores(): void
    {
        if (! $this->product) {
            $this->stores = [];

            return;
        }

        // Stores are branch-owned. Only show stores for the product's branch.
        $this->stores = Store::query()
            ->where('is_active', true)
            ->where('branch_id', $this->product->branch_id)
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->toArray();
    }

    protected function loadMapping(): void
    {
        $mapping = ProductStoreMapping::query()
            ->where('product_id', $this->productId)
            ->findOrFail($this->mappingId);

        $this->store_id = $mapping->store_id;
        $this->external_id = $mapping->external_id ?? '';
        $this->external_sku = $mapping->external_sku ?? '';
    }

    protected function rules(): array
    {
        // Validate store belongs to the SAME branch as the product.
        $branchId = $this->product?->branch_id;

        return [
            'store_id' => ['required', new BranchScopedExists('stores', 'id', $branchId)],
            'external_id' => ['required', 'string', 'max:255'],
            'external_sku' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): mixed
    {
        // Re-authorize on mutation
        $this->authorize($this->mappingId ? 'inventory.products.update' : 'inventory.products.create');

        if (! $this->product) {
            abort(422, __('Product is required'));
        }

        $this->validate();

        $data = [
            'product_id' => $this->productId,
            'store_id' => $this->store_id,
            'external_id' => $this->external_id,
            'external_sku' => $this->external_sku ?: null,
        ];

        if ($this->mappingId) {
            $mapping = ProductStoreMapping::query()
                ->where('product_id', $this->productId)
                ->findOrFail($this->mappingId);

            $mapping->update($data);
            session()->flash('success', __('Mapping updated successfully'));
        } else {
            try {
                DB::transaction(function () use ($data) {
                    ProductStoreMapping::firstOrCreate(
                        [
                            'product_id' => $data['product_id'],
                            'store_id' => $data['store_id'],
                        ],
                        [
                            'external_id' => $data['external_id'],
                            'external_sku' => $data['external_sku'],
                        ]
                    );
                });
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $this->addError('store_id', __('This product is already mapped to this store'));

                return null;
            } catch (\Illuminate\Database\QueryException $e) {
                // Fallback for older drivers that don't throw UniqueConstraintViolationException
                $errorCode = $e->errorInfo[1] ?? 0;
                if ($errorCode === 1062 || $errorCode === 19 || $errorCode === 2627) {
                    $this->addError('store_id', __('This product is already mapped to this store'));

                    return null;
                }

                throw $e;
            }

            session()->flash('success', __('Mapping created successfully'));
        }

        $this->redirectRoute('app.inventory.products.store-mappings', ['product' => $this->productId], navigate: true);
    }

    public function render()
    {
        return view('livewire.inventory.product-store-mappings.form');
    }
}
