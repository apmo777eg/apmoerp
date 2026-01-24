<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Models\Branch;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Terminal extends Component
{
    public int $branchId;

    public string $branchName = '';

    public bool $isSuperAdmin = false;

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

        $this->branchId = (int) ($user->branch_id ?? 0);
        // Use case-insensitive role check - seeder uses "Super Admin" (Title Case)
        $this->isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super-admin']);

        if (! $this->isSuperAdmin && $this->branchId === 0) {
            abort(403, __('You must be assigned to a branch to use the POS terminal.'));
        }

        $branch = $this->branchId ? Branch::find($this->branchId) : null;
        $this->branchName = $branch?->name
            ?? ($this->isSuperAdmin ? __('Super Admin (select a branch)') : __('Branch not found'));
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
            'currencies' => $currencies,
            'currencyData' => $currencyData,
            'currencySymbols' => $currencySymbols,
            'currencyRates' => $currencyRates,
            'baseCurrency' => $baseCurrency,
        ]);
    }
}
