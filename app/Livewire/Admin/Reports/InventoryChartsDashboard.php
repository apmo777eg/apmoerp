<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\Branch;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class InventoryChartsDashboard extends Component
{
    public ?int $branchId = null;

    public ?string $branchLabel = null;

    
    protected function syncBranchContext(): void
    {
        // Follow the global branch context (selected from the sidebar switcher).
        $this->branchId = current_branch_id();

        if ($this->branchId) {
            $branch = Branch::query()->find($this->branchId);

            $this->branchLabel = $branch
                ? ((app()->getLocale() === 'ar' && ! empty($branch->name_ar)) ? $branch->name_ar : $branch->name)
                : ('#'.$this->branchId);
        } else {
            $this->branchLabel = null; // All branches
        }
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.inventory.charts')) {
            abort(403);
        }

        $this->syncBranchContext();
    }

#[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.inventory.charts')) {
            abort(403);
        }

        $query = Product::query()
            ->select('products.id', 'products.sku', 'products.name')
            ->selectRaw(StockService::getStockCalculationExpression().' as current_stock');

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $products = $query->orderBy('current_stock')->get();

        $totalProducts = $products->count();
        // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
        $totalStock = decimal_float($products->sum('current_stock'), 4);

        $lowStock = $products->sortBy('current_stock')->take(20);

        $labels = [];
        $values = [];

        foreach ($lowStock as $product) {
            $labels[] = $product->sku ?: $product->name;
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $values[] = decimal_float($product->current_stock, 4);
        }

        $chartLowStock = [
            'labels' => $labels,
            'values' => $values,
        ];

        $this->dispatch('inventory-charts-update', chartData: [
            'lowStock' => $chartLowStock,
        ]);

        return view('livewire.admin.reports.inventory-charts-dashboard', [
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'branchLabel' => $this->branchLabel,
        ]);
    }
}
