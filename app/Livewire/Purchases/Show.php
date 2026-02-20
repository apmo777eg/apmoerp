<?php

declare(strict_types=1);

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Services\BranchAccessService;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Layout('layouts.app')]
class Show extends Component
{
    public Purchase $purchase;

    public function mount(Purchase $purchase): void
    {
        $user = auth()->user()?->fresh();
        if (! $user?->can('purchases.view')) {
            throw new HttpException(403);
        }

        // HIGH-03 FIX: Use BranchAccessService to properly support multi-branch users
        // instead of just checking user.branch_id which ignores the branches pivot table
        $branchAccessService = app(BranchAccessService::class);

        // Check if user has access to ANY branch (either via branch_id or pivot)
        $userBranches = $branchAccessService->getUserBranches($user);
        if ($userBranches->isEmpty() && ! $branchAccessService->canViewAllBranches($user)) {
            if (app()->runningUnitTests()) {
                \Log::debug('purchase-branch-missing', ['user_id' => $user?->id]);
            }
            throw new HttpException(403, __('You must be assigned to a branch to view purchases.'));
        }

        // HIGH-03 FIX: Use canAccessBranch which checks both branch_id and pivot table
        if (! $branchAccessService->canAccessBranch($user, $purchase->branch_id)) {
            if (app()->runningUnitTests()) {
                \Log::debug('purchase-branch-mismatch', [
                    'user_branches' => $userBranches->pluck('id')->toArray(),
                    'purchase_branch' => $purchase->branch_id,
                ]);
            }
            throw new HttpException(403);
        }

        $this->purchase = $purchase->load(['items.product', 'supplier', 'branch', 'payments']);
    }

    public function render()
    {
        return view('livewire.purchases.show');
    }
}
