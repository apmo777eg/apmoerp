<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branches;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: 15)]
    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Clear search and filters
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branches.view')) {
            abort(403);
        }
    }

    public function render()
    {
        $query = Branch::query()
            ->withCount(['users', 'modules' => fn ($q) => $q->where('enabled', true)])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->orderByDesc('is_main')
            ->orderBy('name');

        $branches = $query->paginate($this->perPage);

        // Statistics
        $totalBranches = Branch::count();
        $activeBranches = Branch::where('is_active', true)->count();
        $inactiveBranches = $totalBranches - $activeBranches;
        $totalUsers = User::count();

        return view('livewire.admin.branches.index', [
            'branches' => $branches,
            'totalBranches' => $totalBranches,
            'activeBranches' => $activeBranches,
            'inactiveBranches' => $inactiveBranches,
            'totalUsers' => $totalUsers,
        ]);
    }
}
