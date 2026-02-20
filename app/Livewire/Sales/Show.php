<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Services\BranchAccessService;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Layout('layouts.app')]
class Show extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $user = auth()->user()?->fresh();
        if (! $user?->can('sales.view')) {
            throw new HttpException(403);
        }

        // HIGH-03 FIX: Use BranchAccessService to properly support multi-branch users
        // instead of just checking user.branch_id which ignores the branches pivot table
        $branchAccessService = app(BranchAccessService::class);

        // Check if user has access to ANY branch (either via branch_id or pivot)
        $userBranches = $branchAccessService->getUserBranches($user);
        if ($userBranches->isEmpty() && ! $branchAccessService->canViewAllBranches($user)) {
            if (app()->runningUnitTests()) {
                \Log::debug('sale-branch-missing', ['user_id' => $user?->id]);
            }
            throw new HttpException(403, __('You must be assigned to a branch to view sales.'));
        }

        // HIGH-03 FIX: Use canAccessBranch which checks both branch_id and pivot table
        if (! $branchAccessService->canAccessBranch($user, $sale->branch_id)) {
            if (app()->runningUnitTests()) {
                \Log::debug('sale-branch-mismatch', [
                    'user_branches' => $userBranches->pluck('id')->toArray(),
                    'sale_branch' => $sale->branch_id,
                ]);
            }
            throw new HttpException(403);
        }

        $this->sale = $sale->load(['items.product', 'customer', 'branch', 'payments']);
    }

    public function render()
    {
        return view('livewire.sales.show');
    }
}
