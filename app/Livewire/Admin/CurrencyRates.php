<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\CurrencyRate;
use App\Services\CurrencyService;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('layouts.app')]
class CurrencyRates extends Component
{
    use AuthorizesRequests, WithPagination;
    public string $baseCurrency = 'EGP';

    public float $convertAmount = 100;

    public string $convertTo = 'USD';

    public ?float $convertedResult = null;

    protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(): void
    {
        // Authorization check - must have settings.view permission
        $user = auth()->user();
        if (! $user || ! $user->can('settings.view')) {
            abort(403, __('Unauthorized access to currency rates'));
        }
    }

    public function render()
    {
        $rates = CurrencyRate::with('creator')
            ->orderByDesc('effective_date')
            ->orderBy('from_currency')
            ->paginate(20);

        $currencies = $this->currencyService->getSupportedCurrencies();

        return view('livewire.admin.currency-rates', [
            'rates' => $rates,
            'currencies' => $currencies,
        ]);
    }

    public function deactivate(int $id): void
    {
        $this->authorize('settings.manage');

        $this->currencyService->deactivateRate($id);
        $this->dispatch('notify', type: 'success', message: __('Currency rate deactivated'));
    }

    public function activate(int $id): void
    {
        $this->authorize('settings.manage');

        $rate = CurrencyRate::find($id);
        if ($rate) {
            $rate->is_active = true;
            $rate->save();
            $this->dispatch('notify', type: 'success', message: __('Currency rate activated'));
        }
    }

    public function convert(): void
    {
        $result = $this->currencyService->convert(
            $this->convertAmount,
            $this->baseCurrency,
            $this->convertTo
        );

        if ($result !== null) {
            $this->convertedResult = $result;
        } else {
            $this->convertedResult = null;
            $this->dispatch('notify', type: 'error', message: __('No exchange rate found for this currency pair'));
        }
    }
}
