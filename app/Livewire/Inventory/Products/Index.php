<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Products;

use App\Models\Module;
use App\Models\Product;
use App\Traits\HasExport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use HasExport;
    use WithPagination;

    public string $search = '';

    public ?string $status = null;

    public ?string $type = null;

    #[Url]
    public ?int $moduleId = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.products.view')) {
            abort(403);
        }

        $this->initializeExport('products');
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $user?->branch_id;

        // Get data modules for filter dropdown
        $dataModules = Module::query()
            ->where('supports_items', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $query = Product::query()
            ->with(['category', 'unit', 'module', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->when($this->status !== null && $this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== null && $this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->moduleId !== null, fn ($q) => $q->where('module_id', $this->moduleId))
            ->orderByDesc('id');

        $products = $query->paginate(20);

        return view('livewire.inventory.products.index', [
            'products' => $products,
            'dataModules' => $dataModules,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingModuleId(): void
    {
        $this->resetPage();
    }

    public function export()
    {
        $user = auth()->user();
        $branchId = $user?->branch_id;

        $query = Product::query()
            ->leftJoin('modules', 'products.module_id', '=', 'modules.id')
            ->leftJoin('branches', 'products.branch_id', '=', 'branches.id')
            ->when($branchId, fn ($q) => $q->where('products.branch_id', $branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('products.name', 'like', $term)
                        ->orWhere('products.sku', 'like', $term)
                        ->orWhere('products.barcode', 'like', $term);
                });
            })
            ->when($this->status !== null && $this->status !== '', fn ($q) => $q->where('products.status', $this->status))
            ->when($this->type !== null && $this->type !== '', fn ($q) => $q->where('products.type', $this->type))
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.type',
                'products.cost as standard_cost',
                'products.default_price as default_price',
                'products.min_stock',
                'products.status',
                'modules.name as module_name',
                'branches.name as branch_name',
                'products.created_at',
            ])
            ->orderByDesc('products.id');

        $maxRows = is_numeric($this->exportMaxRows) ? (int) $this->exportMaxRows : 1000;
        $collection = collect();

        // Use products.id to avoid ambiguous column name with joins
        $query->chunkById(500, function ($chunk) use (&$collection, $maxRows) {
            if ($collection->count() >= $maxRows) {
                return false;
            }

            $remaining = $maxRows - $collection->count();
            $collection = $collection->merge($chunk->take($remaining));
        }, 'products.id', 'id');

        return $this->performExport('products', $collection, __('Products Export'));
    }
}
