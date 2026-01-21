<?php

declare(strict_types=1);

namespace App\Livewire\Income;

use App\Models\Income;
use App\Models\IncomeCategory;
use App\Traits\HasExport;
use App\Traits\HasSortableColumns;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use HasSortableColumns;
    use WithPagination;

    #[Url]
    public string $search = '';

    /**
     * Default sort field - overrides trait default to match income_date.
     */
    public string $sortField = 'income_date';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('income.view');
        $this->initializeExport('incomes');
    }

    public function export(): void
    {
        $user = auth()->user();

        $data = Income::query()
            ->with(['category', 'branch', 'creator'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('description', 'like', "%{$this->search}%")
                    ->orWhere('reference_number', 'like', "%{$this->search}%");
            }))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('income_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('income_date', '<=', $this->dateTo))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->get()
            ->map(fn ($income) => [
                'id' => $income->id,
                'income_date' => $income->income_date?->format('Y-m-d'),
                'category_name' => $income->category?->name ?? '-',
                'description' => $income->description,
                'amount' => $income->amount,
                'branch_name' => $income->branch?->name ?? '-',
                'created_at' => $income->created_at?->format('Y-m-d H:i'),
            ]);

        $this->performExport('incomes', $data, __('Income Export'));
    }

    #[Url]
    public string $categoryId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    /**
     * Define allowed sort columns to prevent SQL injection.
     */
    protected function allowedSortColumns(): array
    {
        return ['id', 'income_date', 'amount', 'description', 'reference_number', 'created_at', 'updated_at'];
    }

    /**
     * Get the default sort column for income.
     */
    protected function defaultSortColumn(): string
    {
        return 'income_date';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('income.delete');

        $income = Income::findOrFail($id);
        $this->ensureBranchAccess($income);
        $income->delete();
        Cache::forget('income_stats_'.(auth()->user()?->branch_id ?? 'all'));
        session()->flash('success', __('Income deleted successfully'));
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'income_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            // Use a single query with conditional aggregations to optimize DB queries
            $stats = Income::query()
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->selectRaw('
                    COUNT(*) as total_count,
                    COALESCE(SUM(amount), 0) as total_amount,
                    CASE WHEN COUNT(*) > 0 THEN AVG(amount) ELSE 0 END as avg_amount,
                    COALESCE(SUM(CASE WHEN MONTH(income_date) = ? AND YEAR(income_date) = ? THEN amount ELSE 0 END), 0) as this_month
                ', [now()->month, now()->year])
                ->first();

            return [
                'total_count' => $stats->total_count ?? 0,
                'total_amount' => $stats->total_amount ?? 0,
                'this_month' => $stats->this_month ?? 0,
                'avg_amount' => $stats->avg_amount ?? 0,
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $incomes = Income::query()
            ->with(['category', 'branch', 'creator'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('description', 'like', "%{$this->search}%")
                    ->orWhere('reference_number', 'like', "%{$this->search}%");
            }))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('income_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('income_date', '<=', $this->dateTo))
            ->orderBy($this->getSortField(), $this->getSortDirection())
            ->paginate(15);

        // V55-HIGH-03 FIX: Make cache key branch-aware to prevent data mixing between branches
        // IncomeCategory now uses HasBranch trait which automatically applies branch scope
        $branchId = $user?->branch_id ?? 'all';
        $categories = Cache::remember("income_categories_{$branchId}", 600, fn () => IncomeCategory::all());
        $stats = $this->getStatistics();

        return view('livewire.income.index', [
            'incomes' => $incomes,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    private function ensureBranchAccess(Income $income): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasAnyRole(['Super Admin', 'super-admin']);

        if ($user?->branch_id && $income->branch_id && $income->branch_id !== $user->branch_id && ! $isSuperAdmin) {
            abort(403, __('You cannot modify income records from another branch.'));
        }
    }
}
