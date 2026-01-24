<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $warehouseId ? __('Edit Location') : __('Add Location') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure warehouse location settings.') }}
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
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="erp-input w-full" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Code') }} @if($warehouseId)<span class="text-red-500">*</span>@endif</label>
                    <input type="text" wire:model="code" class="erp-input w-full" {{ $warehouseId ? 'required' : '' }}>
                    @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Type') }} <span class="text-red-500">*</span></label>
                    <select wire:model="type" class="erp-input w-full" required>
                        <option value="main">{{ __('Main') }}</option>
                        <option value="secondary">{{ __('Secondary') }}</option>
                        <option value="virtual">{{ __('Virtual') }}</option>
                    </select>
                    @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Status') }} <span class="text-red-500">*</span></label>
                    <select wire:model="status" class="erp-input w-full" required>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Address') }}</label>
                <input type="text" wire:model="address" class="erp-input w-full">
                @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Notes') }}</label>
                <textarea wire:model="notes" rows="3" class="erp-input w-full"></textarea>
                @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.warehouse.locations.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $warehouseId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
