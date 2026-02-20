<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $activeTab = 'accounts';

    public function mount(): void
    {
        $this->authorize('accounting.view');
    }

    #[Url]
    public string $search = '';

    #[Url]
    public string $accountType = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function getStatistics(): array
    {
        $user = auth()->user();
        $cacheKey = 'accounting_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $accountQuery = Account::query();
            $journalQuery = JournalEntry::query();

            if ($user && $user->branch_id) {
                $accountQuery->where('branch_id', $user->branch_id);
                $journalQuery->where('branch_id', $user->branch_id);
            }

            return [
                'total_accounts' => $accountQuery->count(),
                'active_accounts' => Account::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('is_active', true)->count(),
                'total_assets' => Account::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('type', 'asset')->sum('balance'),
                'total_liabilities' => Account::query()
                    ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->where('type', 'liability')->sum('balance'),
                'journal_entries' => $journalQuery->count(),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();
        $accounts = [];
        $journalEntries = [];

        if ($this->activeTab === 'accounts') {
            $accounts = Account::query()
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->when($this->search, fn ($q) => $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('account_number', 'like', "%{$this->search}%");
                }))
                ->when($this->accountType, fn ($q) => $q->where('type', $this->accountType))
                ->orderBy('account_number')
                ->paginate(15);
        } else {
            $journalEntries = JournalEntry::query()
                ->with(['branch', 'creator'])
                ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
                ->when($this->search, fn ($q) => $q->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                }))
                ->orderBy('entry_date', 'desc')
                ->paginate(15);
        }

        $stats = $this->getStatistics();

        return view('livewire.accounting.index', [
            'accounts' => $accounts,
            'journalEntries' => $journalEntries,
            'stats' => $stats,
        ]);
    }
}
