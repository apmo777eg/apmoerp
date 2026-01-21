<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CurrencyManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.currency.manage')) {
            abort(403);
        }
    }

    public function render()
    {
        $currencies = Currency::orderBy('sort_order')->orderBy('code')->paginate(20);

        return view('livewire.admin.currency-manager', [
            'currencies' => $currencies,
        ]);
    }

    public function toggleActive(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('settings.currency.manage');
        
        $currency = Currency::find($id);
        if ($currency) {
            if ($currency->is_base && $currency->is_active) {
                $this->dispatch('notify', type: 'error', message: __('Cannot deactivate base currency'));

                return;
            }

            $currency->is_active = ! $currency->is_active;
            $currency->save();

            $this->currencyService->clearCurrencyCache();
            $this->dispatch('notify', type: 'success', message: $currency->is_active ? __('Currency activated') : __('Currency deactivated'));
        }
    }

    public function setAsBase(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('settings.currency.manage');
        
        $currency = Currency::find($id);
        if ($currency) {
            Currency::where('is_base', true)->update(['is_base' => false]);
            $currency->is_base = true;
            $currency->is_active = true;
            $currency->save();

            $this->currencyService->clearCurrencyCache();
            $this->dispatch('notify', type: 'success', message: __(':currency is now the base currency', ['currency' => $currency->code]));
        }
    }

    public function delete(int $id): void
    {
        // V57-HIGH-01 FIX: Explicit authorization inside mutation method
        $this->authorize('settings.currency.manage');
        
        $currency = Currency::find($id);
        if ($currency) {
            if ($currency->is_base) {
                $this->dispatch('notify', type: 'error', message: __('Cannot delete base currency'));

                return;
            }

            $currency->delete();
            $this->currencyService->clearCurrencyCache();
            $this->dispatch('notify', type: 'success', message: __('Currency deleted successfully'));
        }
    }
}
