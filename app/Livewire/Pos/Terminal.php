<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Warehouse;
use App\Services\BranchContextManager;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Terminal extends Component
{
    public int $branchId;

    public string $branchName = '';

    public ?int $warehouseId = null;

    /**
     * Indicates the authenticated user can view/switch all branches.
     */
    public bool $canViewAllBranches = false;

    protected CurrencyService $currencyService;

    protected array $rateCache = [];

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('pos.use')) {
            abort(403);
        }

        $this->canViewAllBranches = BranchContextManager::canViewAllBranches($user);

        // IMPORTANT: POS is a write-heavy module. It must never operate in an "All Branches" context.
        // Always resolve the effective branch from the current branch context (Branch Switcher).
        $this->branchId = (int) (current_branch_id() ?? 0);

        // If no branch context is set, fall back to the user's own branch.
        // This prevents 403 errors for admin users who haven't explicitly selected a branch yet.
        if ($this->branchId === 0 && $user->branch_id) {
            $this->branchId = (int) $user->branch_id;

            // Also set the session context so BranchSwitcher stays in sync
            session(['admin_branch_context' => $this->branchId]);
        }

        if ($this->branchId === 0) {
            // Try to use the first active branch the user has access to
            $firstBranch = $user->branches()->where('is_active', true)->first()
                ?? Branch::where('is_active', true)->first();

            if ($firstBranch) {
                $this->branchId = (int) $firstBranch->id;
                session(['admin_branch_context' => $this->branchId]);
            } else {
                abort(403, __('No active branch found. Please contact your administrator.'));
            }
        }

        $branch = Branch::find($this->branchId);
        $this->branchName = $branch?->name ?? __('Branch not found');

        // Resolve default warehouse for the branch (required for POS checkout)
        $this->warehouseId = Warehouse::where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->value('id');

        if (! $this->warehouseId) {
            \Illuminate\Support\Facades\Log::warning('POS Terminal: No active warehouse found for branch', [
                'branch_id' => $this->branchId,
            ]);
        }
    }

    public function render()
    {
        $currencies = Currency::active()->ordered()->get();
        $baseCurrencyModel = $currencies->firstWhere('is_base', true);
        $baseCurrency = $baseCurrencyModel?->code ?? 'EGP';

        $currencyData = [];
        $currencySymbols = [];
        $currencyRates = [$baseCurrency => 1.0];
        $targetCurrencies = $currencies->where('is_base', false)->pluck('code')->all();
        $this->rateCache = $this->rateCache ?: $this->currencyService->getRatesFor($baseCurrency, $targetCurrencies);

        foreach ($currencies as $currency) {
            $currencyData[$currency->code] = [
                'name' => $currency->name,
                'name_ar' => $currency->name_ar,
                'symbol' => $currency->symbol,
                'is_base' => $currency->is_base,
            ];
            $currencySymbols[$currency->code] = $currency->symbol;

            if (! $currency->is_base) {
                $rate = $this->rateCache[$currency->code] ?? null;
                $currencyRates[$currency->code] = $rate ?? 1.0;
            }
        }

        return view('livewire.pos.terminal', [
            'branchId' => $this->branchId,
            'branchName' => $this->branchName,
            'warehouseId' => $this->warehouseId,
            'currencies' => $currencies,
            'currencyData' => $currencyData,
            'currencySymbols' => $currencySymbols,
            'currencyRates' => $currencyRates,
            'baseCurrency' => $baseCurrency,
        ]);
    }
}
