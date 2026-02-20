<?php

declare(strict_types=1);

namespace App\Livewire\Banking\Accounts;

use App\Models\BankAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $currency = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * Hard allow-list of sortable fields to avoid SQL injection via orderBy.
     */
    protected array $allowedSortFields = [
        'created_at',
        'account_name',
        'account_number',
        'bank_name',
        'current_balance',
        'status',
        'currency',
    ];

    public function mount(): void
    {
        $this->authorize('banking.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSortFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getStatistics(): array
    {
        // Optimize with single query using conditional aggregations
        // NOTE: BankAccount is branch-scoped. We intentionally DO NOT add manual branch filters here
        // so that the report respects the current branch context (branch switcher).
        $stats = BankAccount::query()
            ->selectRaw('
                COUNT(*) as total_accounts,
                COUNT(CASE WHEN status = ? THEN 1 END) as active_accounts,
                SUM(CASE WHEN status = ? THEN current_balance ELSE 0 END) as total_balance,
                COUNT(DISTINCT currency) as currencies
            ', ['active', 'active'])
            ->first();

        return [
            'total_accounts' => $stats->total_accounts ?? 0,
            'active_accounts' => $stats->active_accounts ?? 0,
            'total_balance' => $stats->total_balance ?? 0,
            'currencies' => $stats->currencies ?? 0,
        ];
    }

    public function render()
    {
        $query = BankAccount::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('account_name', 'like', "%{$this->search}%")
                    ->orWhere('account_number', 'like', "%{$this->search}%")
                    ->orWhere('bank_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        // Defensive: ensure sortField is always safe (e.g. when set from query params).
        if (! in_array($this->sortField, $this->allowedSortFields, true)) {
            $this->sortField = 'created_at';
        }
        $query->orderBy($this->sortField, $this->sortDirection);

        $accounts = $query->paginate(15);
        $statistics = $this->getStatistics();

        $currencies = BankAccount::query()
            ->select('currency')
            ->distinct()
            ->pluck('currency');

        return view('livewire.banking.accounts.index', [
            'accounts' => $accounts,
            'statistics' => $statistics,
            'currencies' => $currencies,
        ]);
    }
}
