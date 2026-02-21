<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $accountId ? __('Edit Account') : __('New Account') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Manage chart of accounts') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="erp-card p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Account Number') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="form.account_number" @disabled($readOnly) class="erp-input" required>
                    @error('form.account_number')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Account Type') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="form.type" @disabled($readOnly) class="erp-input" required>
                        <option value="asset">{{ __('Asset') }}</option>
                        <option value="liability">{{ __('Liability') }}</option>
                        <option value="equity">{{ __('Equity') }}</option>
                        <option value="revenue">{{ __('Revenue') }}</option>
                        <option value="expense">{{ __('Expense') }}</option>
                    </select>
                    @error('form.type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Account Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="form.name" @disabled($readOnly) class="erp-input" required>
                    @error('form.name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Account Name (Arabic)') }}
                    </label>
                    <input type="text" wire:model="form.name_ar" @disabled($readOnly) class="erp-input">
                    @error('form.name_ar')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Currency') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="form.currency_code" @disabled($readOnly) class="erp-input" required>
                        @if(is_array($currencies) || is_object($currencies))
                            @foreach($currencies as $currency)
                                @if(is_object($currency))
                                    <option value="{{ $currency->code ?? '' }}">{{ $currency->code ?? '' }} - {{ $currency->name ?? '' }}</option>
                                @elseif(is_array($currency))
                                    <option value="{{ $currency['code'] ?? '' }}">{{ $currency['code'] ?? '' }} - {{ $currency['name'] ?? '' }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('form.currency_code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Account Category') }}
                    </label>
                    <input type="text" wire:model="form.account_category" @disabled($readOnly) class="erp-input" placeholder="{{ __('e.g., Cash, Bank, Receivables') }}">
                    @error('form.account_category')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Parent Account') }}
                    </label>
                    <select wire:model="form.parent_id" @disabled($readOnly) class="erp-input">
                        <option value="">{{ __('None (Top Level)') }}</option>
                        @if(is_array($parentAccounts) || is_object($parentAccounts))
                            @foreach($parentAccounts as $parent)
                                @if(is_object($parent))
                                    <option value="{{ $parent->id ?? '' }}">{{ $parent->account_number ?? '' }} - {{ $parent->name ?? '' }}</option>
                                @elseif(is_array($parent))
                                    <option value="{{ $parent['id'] ?? '' }}">{{ $parent['account_number'] ?? '' }} - {{ $parent['name'] ?? '' }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('form.parent_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-6">
                        <input type="checkbox" wire:model="form.is_active" @disabled($readOnly) class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                        <span>{{ __('Active') }}</span>
                    </label>
                    @error('form.is_active')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Description') }}
                    </label>
                    <textarea wire:model="form.description" rows="3" @disabled($readOnly) class="erp-input"></textarea>
                    @error('form.description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('app.accounting.index') }}" class="erp-btn erp-btn-secondary">
                {{ __('Cancel') }}
            </a>
            @unless($readOnly)
            <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $accountId ? __('Update Account') : __('Create Account') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </button>
            @endunless
        </div>
    </form>
</div>
