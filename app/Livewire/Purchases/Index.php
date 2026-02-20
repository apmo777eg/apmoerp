<?php

declare(strict_types=1);

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Traits\HasExport;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use HasSortableColumns;
    use WithPagination;

    #[Url]
    public string $search = '';

    // V34-HIGH-04 FIX: Default to purchase_date for business date sorting
    public string $sortField = 'purchase_date';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('purchases.view');
        $this->initializeExport('purchases');
    }

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    /**
     * Define allowed sort columns to prevent SQL injection.
     * Use actual migration column names.
     * V34-HIGH-04 FIX: Added purchase_date to allowed sort columns for business date sorting
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'reference_number', 'total_amount', 'paid_amount', 'status', 'purchase_date', 'created_at', 'updated_at'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'purchases_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $query = Purchase::query();

            if ($user && $user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            // Use correct migration column names
            $totalAmount = $query->sum('total_amount') ?? 0;
            $paidAmount = $query->sum('paid_amount') ?? 0;

            return [
                'total_purchases' => $query->count(),
                'total_amount' => $totalAmount,
                'total_paid' => $paidAmount,
                'total_due' => max(0, $totalAmount - $paidAmount),
            ];
        });
    }

    public function export()
    {
        $user = auth()->user();
        $sortField = $this->getSortField();
        $sortDirection = $this->getSortDirection();

        $data = Purchase::query()
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
            ->when($user && $user->branch_id, fn ($q) => $q->where('purchases.branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('purchases.reference_number', 'like', "%{$this->search}%")
                    ->orWhere('suppliers.name', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('purchases.status', $this->status))
            // V34-HIGH-04 FIX: Use purchase_date instead of created_at for date filtering
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchases.purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchases.purchase_date', '<=', $this->dateTo))
            ->orderBy('purchases.'.$sortField, $sortDirection)
            ->select([
                'purchases.id',
                // APMOERP68-FIX: Use column names matching ExportService expectations
                'purchases.reference_number as reference_number',
                'purchases.purchase_date as purchase_date',
                'suppliers.name as supplier_name',
                'purchases.total_amount as total_amount',
                'purchases.paid_amount as amount_paid',
                // SECURITY (V58-SQL-01): DB::raw uses hardcoded column names, no user input
                DB::raw('(purchases.total_amount - purchases.paid_amount) as amount_due'),
                'purchases.status',
                'purchases.payment_status as payment_status',
                'branches.name as branch_name',
                'purchases.created_at',
            ])
            ->get();

        return $this->performExport('purchases', $data, __('Purchases Export'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $purchases = Purchase::query()
            ->with(['supplier', 'branch', 'warehouse', 'createdBy'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('reference_number', 'like', "%{$this->search}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            // V34-HIGH-04 FIX: Use purchase_date instead of created_at for date filtering
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'stats' => $stats,
        ]);
    }
}
