<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $propertyId ? __('Edit Property') : __('Add Property') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Manage rental property information.') }}
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
            <div>
                <label class="erp-label">{{ __('Property Name') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="name" class="erp-input mt-1 @error('name') border-red-500 @enderror" placeholder="{{ __('Property name') }}" required>
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label class="erp-label">{{ __('Address') }}</label>
                <input type="text" wire:model="address" class="erp-input mt-1 @error('address') border-red-500 @enderror" placeholder="{{ __('Property address') }}">
                @error('address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label class="erp-label">{{ __('Notes') }}</label>
                <textarea wire:model="notes" rows="3" class="erp-input mt-1 @error('notes') border-red-500 @enderror" placeholder="{{ __('Optional notes') }}"></textarea>
                @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('app.rental.properties.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $propertyId ? __('Update Property') : __('Create Property') }}
                </span>
                <span wire:loading wire:target="save">
                    <svg class="animate-spin h-5 w-5 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Saving...') }}
                </span>
            </button>
        </div>
    </form>
</div>
