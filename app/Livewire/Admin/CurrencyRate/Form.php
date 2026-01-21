<?php

declare(strict_types=1);

namespace App\Livewire\Admin\CurrencyRate;

use App\Models\CurrencyRate;
use App\Services\CurrencyService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    public ?int $rateId = null;

    public string $fromCurrency = 'EGP';

    public string $toCurrency = 'USD';

    public float $rate = 0;

    public string $effectiveDate = '';

    protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(?int $currencyRate = null): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('settings.manage')) {
            abort(403, __('Unauthorized access to currency rates'));
        }

        $this->effectiveDate = now()->format('Y-m-d');

        if ($currencyRate) {
            $this->rateId = $currencyRate;
            $this->loadRate();
        }
    }

    protected function loadRate(): void
    {
        $rate = CurrencyRate::findOrFail($this->rateId);
        $this->fromCurrency = $rate->from_currency;
        $this->toCurrency = $rate->to_currency;
        // V51-CRIT-01 FIX: Use decimal_float() with scale 6 to match CurrencyRate::rate's decimal:6 cast
        $this->rate = decimal_float($rate->rate, 6);
        $this->effectiveDate = $rate->effective_date->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'fromCurrency' => 'required|string|size:3',
            'toCurrency' => 'required|string|size:3|different:fromCurrency',
            'rate' => 'required|numeric|min:0.000001',
            'effectiveDate' => 'required|date',
        ];
    }

    public function save(): mixed
    {
        $user = auth()->user();
        if (! $user || ! $user->can('settings.manage')) {
            abort(403);
        }

        $this->validate();

        if ($this->fromCurrency === $this->toCurrency) {
            session()->flash('error', __('From and To currencies must be different'));

            return null;
        }

        $this->currencyService->setRate(
            $this->fromCurrency,
            $this->toCurrency,
            $this->rate,
            $this->effectiveDate
        );

        session()->flash('success', $this->rateId
            ? __('Currency rate updated successfully')
            : __('Currency rate added successfully'));

        $this->redirectRoute('admin.currency-rates.index', navigate: true);
    }

    public function addReverseRate(): void
    {
        if ($this->rate > 0) {
            $reverseRate = 1 / $this->rate;

            $this->currencyService->setRate(
                $this->toCurrency,
                $this->fromCurrency,
                $reverseRate,
                $this->effectiveDate
            );

            session()->flash('success', __('Reverse rate added successfully'));
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $currencies = $this->currencyService->getSupportedCurrencies();

        return view('livewire.admin.currency-rate.form', [
            'currencies' => $currencies,
        ]);
    }
}
