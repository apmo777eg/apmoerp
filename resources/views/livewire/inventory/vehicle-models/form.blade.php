<div class="mx-auto max-w-2xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ $vehicleModelId ? __('Edit Vehicle Model') : __('Add Vehicle Model') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Manage vehicle models for spare parts compatibility.') }}</p>
        </div>
        <a href="{{ route('app.inventory.vehicle-models.index') }}" 
           wire:navigate
           class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('Back to List') }}
        </a>
    </div>

    {{-- Form Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-emerald-500/10 p-6">
        <form wire:submit.prevent="save" class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Brand') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="brand" class="erp-input" placeholder="{{ __('e.g., Toyota') }}">
                    @error('brand') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Model Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="model" class="erp-input" placeholder="{{ __('e.g., Corolla') }}">
                    @error('model') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Year From') }}</label>
                    <input type="number" wire:model="year_from" class="erp-input" min="1900" max="2100" placeholder="{{ __('e.g., 2015') }}">
                    @error('year_from') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Year To') }}</label>
                    <input type="number" wire:model="year_to" class="erp-input" min="1900" max="2100" placeholder="{{ __('e.g., 2020') }}">
                    @error('year_to') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Category') }}</label>
                    <input type="text" wire:model="category" class="erp-input" placeholder="{{ __('e.g., Sedan, SUV, Truck') }}">
                    @error('category') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">{{ __('Engine Type') }}</label>
                    <input type="text" wire:model="engine_type" class="erp-input" placeholder="{{ __('e.g., 1.6L, 2.0L Turbo') }}">
                    @error('engine_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="is_active" class="text-sm text-slate-700">{{ __('Active') }}</label>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button"
                        wire:click="cancel"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ __('Cancel') }}
                </button>
                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    {{ $vehicleModelId ? __('Update') : __('Create') }}
                </button>
            </div>
        </form>
    </div>
</div>
