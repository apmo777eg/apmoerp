<?php

declare(strict_types=1);

namespace App\Livewire\FixedAssets;

use App\Models\FixedAsset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Depreciation extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $period = 'current_month';

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('fixed-assets.view')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriod(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('fixed-assets.view')) {
            abort(403);
        }

        // Get depreciation schedule
        $query = FixedAsset::query()
            ->with(['branch', 'depreciations' => function ($q) {
                $q->orderBy('depreciation_date', 'desc')->limit(5);
            }])
            ->where('status', 'active')
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('asset_code', 'like', $term);
                });
            });

        $assets = $query->paginate(20);

        // Calculate summary statistics
        $stats = FixedAsset::query()
            ->where('status', 'active')
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->select([
                DB::raw('COUNT(*) as total_assets'),
                DB::raw('SUM(purchase_cost) as total_purchase_cost'),
                DB::raw('SUM(accumulated_depreciation) as total_depreciation'),
                DB::raw('SUM(book_value) as total_book_value'),
            ])
            ->first();

        return view('livewire.fixed-assets.depreciation', [
            'assets' => $assets,
            'stats' => $stats,
        ]);
    }
}
