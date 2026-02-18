<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\BranchAccessService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Enums\SaleStatus;
use Livewire\Component;
use Livewire\WithPagination;

class DailyReport extends Component
{
    use AuthorizesRequests, WithPagination;

    public ?int $branchId = null;

    /**
     * Global branch context selected from the sidebar switcher.
     * If set, this report is locked to that branch for a simpler UX.
     */
    public ?int $branchContextId = null;

    public string $date = '';

    public array $summary = [];

    protected BranchAccessService $branchAccessService;

    public function boot(BranchAccessService $branchAccessService): void
    {
        $this->branchAccessService = $branchAccessService;
    }

    public function mount(): void
    {
        $this->authorize('pos.daily-report.view');
        $this->date = now()->format('Y-m-d');

        $this->branchContextId = current_branch_id();

        $user = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super-admin']);
        if ($this->branchContextId) {
            $this->branchId = $this->branchContextId;
        } elseif ($user->branch_id) {
            $this->branchId = (int) $user->branch_id;
        } elseif (! $isSuperAdmin) {
            $branches = $this->branchAccessService->getUserBranches($user);
            $this->branchId = $branches->first()?->id;
        }

        $this->generateReport();
    }

    public function updatedDate(): void
    {
        $this->generateReport();
    }

    public function updatedBranchId(): void
    {
        // If branch context is locked from sidebar, don't allow changing it here.
        if ($this->branchContextId) {
            $this->branchId = $this->branchContextId;
            return;
        }

        $this->generateReport();
    }

    public function generateReport(): void
    {
        // V33-CRIT-01 FIX: Use sale_date (business date) instead of created_at
        // and exclude all non-valid statuses (not just cancelled)
        // V35-MED-06 FIX: Include 'draft' in exclusion list for consistency
        $query = Sale::whereDate('sale_date', $this->date)
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses());

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $sales = $query->get();
        $saleIds = $sales->pluck('id');

        $paymentsRaw = SalePayment::whereIn('sale_id', $saleIds)
            ->get(['payment_method', 'amount']);

        $paymentBreakdown = $paymentsRaw->groupBy('payment_method')
            ->map(function ($group, $method) {
                return [
                    'payment_method' => $method,
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })
            ->toArray();

        $sessionsQuery = PosSession::whereDate('opened_at', $this->date);
        if ($this->branchId) {
            $sessionsQuery->where('branch_id', $this->branchId);
        }
        $sessions = $sessionsQuery->with(['user', 'closedBy'])->get();

        $this->summary = [
            'total_sales' => $sales->sum('total_amount'),
            'total_transactions' => $sales->count(),
            'total_discount' => $sales->sum('discount_amount'),
            'total_tax' => $sales->sum('tax_amount'),
            'average_sale' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
            'payment_breakdown' => $paymentBreakdown,
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id,
                'user_name' => $s->user?->name ?? '-',
                'opening_cash' => $s->opening_cash,
                'closing_cash' => $s->closing_cash,
                'expected_cash' => $s->expected_cash,
                'cash_difference' => $s->cash_difference,
                'total_transactions' => $s->total_transactions,
                'total_sales' => $s->total_sales,
                'status' => $s->status,
                'opened_at' => $s->opened_at?->format('H:i'),
                'closed_at' => $s->closed_at?->format('H:i'),
            ])->toArray(),
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super-admin']);
        $branches = $isSuperAdmin
            ? \App\Models\Branch::active()->get()
            : $this->branchAccessService->getUserBranches($user);

        // V33-CRIT-01 FIX: Use sale_date (business date) instead of created_at
        // and exclude all non-valid statuses (not just cancelled)
        // V35-MED-06 FIX: Include 'draft' in exclusion list for consistency
        $salesQuery = Sale::whereDate('sale_date', $this->date)
            ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
            ->with(['customer', 'payments', 'createdBy']);

        if ($this->branchId) {
            $salesQuery->where('branch_id', $this->branchId);
        }

        return view('livewire.pos.daily-report', [
            'branches' => $branches,
            'sales' => $salesQuery->latest()->paginate(20),
            'isSuperAdmin' => $isSuperAdmin,
        ])->layout('layouts.app');
    }
}
