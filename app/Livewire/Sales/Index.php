<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Models\Sale;
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

    // V34-HIGH-04 FIX: Default to sale_date for business date sorting
    public string $sortField = 'sale_date';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('sales.view');
        $this->initializeExport('sales');
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
     * V34-HIGH-04 FIX: Added sale_date to allowed sort columns for business date sorting
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'reference_number', 'total_amount', 'paid_amount', 'status', 'sale_date', 'created_at', 'updated_at'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'sales_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $query = Sale::query();

            if ($user && $user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            // Use correct migration column names
            $totalAmount = $query->sum('total_amount') ?? 0;
            $paidAmount = $query->sum('paid_amount') ?? 0;

            return [
                'total_sales' => $query->count(),
                'total_revenue' => (string) $totalAmount,
                'total_paid' => (string) $paidAmount,
                'total_due' => (string) max(0, $totalAmount - $paidAmount),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $sales = Sale::query()
            ->with(['customer', 'branch', 'warehouse', 'createdBy'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('reference_number', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            // V34-HIGH-04 FIX: Use sale_date instead of created_at for date filtering
            ->when($this->dateFrom, fn ($q) => $q->whereDate('sale_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('sale_date', '<=', $this->dateTo))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.sales.index', [
            'sales' => $sales,
            'stats' => $stats,
        ]);
    }

    public function export()
    {
        $user = auth()->user();
        $sortField = $this->getSortField();
        $sortDirection = $this->getSortDirection();

        $data = Sale::query()
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->when($user && $user->branch_id, fn ($q) => $q->where('sales.branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('sales.reference_number', 'like', "%{$this->search}%")
                    ->orWhere('customers.name', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('sales.status', $this->status))
            // V34-HIGH-04 FIX: Use sale_date instead of created_at for date filtering
            ->when($this->dateFrom, fn ($q) => $q->whereDate('sales.sale_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('sales.sale_date', '<=', $this->dateTo))
            ->orderBy('sales.'.$sortField, $sortDirection)
            ->select([
                'sales.id',
                // APMOERP68-FIX: Use column names matching ExportService expectations
                'sales.reference_number as reference_number',
                'sales.sale_date as sale_date',
                'customers.name as customer_name',
                'sales.total_amount as total_amount',
                'sales.paid_amount as amount_paid',
                // SECURITY (V58-SQL-01): DB::raw uses hardcoded column names, no user input
                DB::raw('(sales.total_amount - sales.paid_amount) as amount_due'),
                'sales.status',
                'sales.payment_status as payment_status',
                'branches.name as branch_name',
                'sales.created_at',
            ])
            ->get();

        return $this->performExport('sales', $data, __('Sales Export'));
    }
}
