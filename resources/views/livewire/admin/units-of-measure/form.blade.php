<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $unitId ? __('Edit Unit of Measure') : __('New Unit of Measure') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure unit of measure settings.') }}
            </p>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-2xl">
        <div class="erp-card p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name (English)') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="erp-input w-full" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name (Arabic)') }}</label>
                    <input type="text" wire:model="nameAr" class="erp-input w-full" dir="rtl">
                    @error('nameAr') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Symbol') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="symbol" class="erp-input w-full" required>
                    @error('symbol') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Type') }} <span class="text-red-500">*</span></label>
                    <select wire:model="type" class="erp-input w-full" required>
                        @if(is_array($unitTypes))
                            @foreach($unitTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="isBaseUnit" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('This is a base unit') }}</span>
                </label>
            </div>

            @if(!$isBaseUnit)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Base Unit') }}</label>
                    <select wire:model="baseUnitId" class="erp-input w-full">
                        <option value="">{{ __('Select base unit') }}</option>
                        @if(is_array($baseUnits) || is_object($baseUnits))
                            @foreach($baseUnits as $baseUnit)
                                @if(is_object($baseUnit))
                                    <option value="{{ $baseUnit->id ?? '' }}">{{ $baseUnit->name ?? '' }} ({{ $baseUnit->symbol ?? '' }})</option>
                                @elseif(is_array($baseUnit))
                                    <option value="{{ $baseUnit['id'] ?? '' }}">{{ $baseUnit['name'] ?? '' }} ({{ $baseUnit['symbol'] ?? '' }})</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('baseUnitId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Conversion Factor') }}</label>
                    <input type="number" wire:model="conversionFactor" class="erp-input w-full" step="0.000001" min="0.000001">
                    <p class="text-xs text-slate-500 mt-1">{{ __('How many base units equal 1 of this unit') }}</p>
                    @error('conversionFactor') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Decimal Places') }}</label>
                    <input type="number" wire:model="decimalPlaces" class="erp-input w-full" min="0" max="6">
                    @error('decimalPlaces') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Sort Order') }}</label>
                    <input type="number" wire:model="sortOrder" class="erp-input w-full" min="0">
                    @error('sortOrder') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.inventory.units.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $unitId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
