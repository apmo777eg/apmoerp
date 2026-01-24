<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $unitId ? __('Edit rental unit') : __('Create rental unit') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Basic details and pricing for the rental unit.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Property') }}
                    </label>
                    <x-quick-add-link 
                        :route="route('app.rental.properties.create')" 
                        label="{{ __('Add Property') }}"
                        permission="rental.properties.create" />
                </div>
                <select wire:model="form.property_id" class="erp-input">
                    @foreach($availableProperties as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('form.property_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Unit code') }}
                </label>
                <input type="text" wire:model="form.code" class="erp-input">
                @error('form.code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Type (e.g. 2BR, Studio)') }}
                </label>
                <input type="text" wire:model="form.type" class="erp-input">
                @error('form.type')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Status') }}
                </label>
                <select wire:model="form.status" class="erp-input">
                    <option value="available">{{ __('Available') }}</option>
                    <option value="occupied">{{ __('Occupied') }}</option>
                    <option value="maintenance">{{ __('Maintenance') }}</option>
                </select>
                @error('form.status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Monthly rent') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.rent" class="erp-input">
                @error('form.rent')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Security deposit') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.deposit" class="erp-input">
                @error('form.deposit')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if (! empty($dynamicSchema))
                <div class="sm:col-span-2 lg:col-span-3 space-y-2">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('Additional fields') }}
                    </h2>
                    <livewire:shared.dynamic-form
                        :schema="$dynamicSchema"
                        :data="$dynamicData"
                        wire:key="rental-unit-dynamic-form-{{ $unitId ?? 'new' }}"
                    ></livewire:shared.dynamic-form>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('app.rental.units.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $unitId ? __('Save changes') : __('Create unit') }}
            </button>
        </div>
    </form>
</div>
