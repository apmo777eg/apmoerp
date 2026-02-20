<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Currency;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    public ?int $currencyId = null;

    public string $code = '';

    public string $name = '';

    public string $nameAr = '';

    public string $symbol = '';

    public int $decimalPlaces = 2;

    public int $sortOrder = 0;

    public bool $isActive = true;

    public bool $isBase = false;

    protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(?int $currency = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.currency.manage')) {
            abort(403);
        }

        if ($currency) {
            $this->currencyId = $currency;
            $this->loadCurrency();
        } else {
            $this->sortOrder = Currency::max('sort_order') + 1;
        }
    }

    protected function loadCurrency(): void
    {
        $currency = Currency::findOrFail($this->currencyId);
        $this->code = $currency->code;
        $this->name = $currency->name;
        $this->nameAr = $currency->name_ar ?? '';
        $this->symbol = $currency->symbol;
        $this->decimalPlaces = $currency->decimal_places;
        $this->sortOrder = $currency->sort_order;
        $this->isActive = $currency->is_active;
        $this->isBase = $currency->is_base;
    }

    protected function rules(): array
    {
        return [
            // Removed 'alpha' validation to support non-Latin currency codes (e.g., Arabic)
            'code' => ['required', 'string', 'size:3', 'regex:/^[\p{L}\p{M}]+$/u', $this->currencyId ? 'unique:currencies,code,'.$this->currencyId : 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:100'],
            'nameAr' => ['nullable', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'decimalPlaces' => ['required', 'integer', 'min:0', 'max:6'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ];
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.currency.manage')) {
            abort(403);
        }

        $this->validate();

        $data = [
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'name_ar' => $this->nameAr ?: null,
            'symbol' => $this->symbol,
            'decimal_places' => $this->decimalPlaces,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->currencyId) {
            $currency = Currency::findOrFail($this->currencyId);
            if ($this->isBase && ! $currency->is_base) {
                Currency::where('is_base', true)->update(['is_base' => false]);
                $data['is_base'] = true;
            } elseif (! $this->isBase && $currency->is_base) {
                session()->flash('error', __('Cannot unset base currency. Set another currency as base first.'));

                return;
            }

            $currency->update($data);
            session()->flash('success', __('Currency updated successfully'));
        } else {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['created_by'] = actual_user_id();

            if ($this->isBase) {
                Currency::where('is_base', true)->update(['is_base' => false]);
                $data['is_base'] = true;
            }

            Currency::create($data);
            session()->flash('success', __('Currency created successfully'));
        }

        $this->currencyService->clearCurrencyCache();

        $this->redirectRoute('admin.currencies.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.currency.form');
    }
}
