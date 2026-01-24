<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $rateId ? __('Edit Currency Rate') : __('New Currency Rate') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure exchange rate between currencies.') }}
            </p>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-xl">
        <div class="erp-card p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('From Currency') }} <span class="text-red-500">*</span></label>
                    <select wire:model="fromCurrency" class="erp-input w-full" required>
                        @if(is_array($currencies))
                            @foreach($currencies as $code => $currency)
                                <option value="{{ $code }}">{{ $code }} - {{ is_array($currency) ? ($currency['name'] ?? $code) : $code }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('fromCurrency') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('To Currency') }} <span class="text-red-500">*</span></label>
                    <select wire:model="toCurrency" class="erp-input w-full" required>
                        @if(is_array($currencies))
                            @foreach($currencies as $code => $currency)
                                <option value="{{ $code }}">{{ $code }} - {{ is_array($currency) ? ($currency['name'] ?? $code) : $code }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('toCurrency') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Exchange Rate') }} <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="rate" class="erp-input w-full" step="0.000001" min="0.000001" required>
                    <p class="text-xs text-slate-500 mt-1">{{ __('1 :from = Rate × :to', ['from' => $fromCurrency, 'to' => $toCurrency]) }}</p>
                    @error('rate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Effective Date') }} <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="effectiveDate" class="erp-input w-full" required>
                    @error('effectiveDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="button" wire:click="addReverseRate" class="inline-flex items-center rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm hover:bg-blue-100">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                {{ __('Add Reverse Rate') }}
            </button>
            <a href="{{ route('admin.currency-rates.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $rateId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
