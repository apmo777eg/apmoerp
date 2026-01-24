<?php

declare(strict_types=1);

namespace App\Livewire\Pos\Reports;

use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OfflineSales extends Component
{
    use WithPagination;

    public ?int $branchId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('pos.offline.report.view')) {
            abort(403);
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['branchId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Sale::query()
            ->where('notes', 'LIKE', '%Synced from offline POS%');

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        // V37-HIGH-02 FIX: Use sale_date (business date) instead of created_at for accurate period filtering
        // For offline sales, created_at represents sync time, not actual sale time
        if ($this->dateFrom) {
            $query->whereDate('sale_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('sale_date', '<=', $this->dateTo);
        }

        // V37-HIGH-02 FIX: Also order by sale_date for consistency with the date filtering
        $sales = $query->latest('sale_date')->paginate(25);

        $branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.pos.reports.offline-sales', [
            'sales' => $sales,
            'branches' => $branches,
        ]);
    }
}
