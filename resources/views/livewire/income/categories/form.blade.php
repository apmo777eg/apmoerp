<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-2xl">📁</span>
                {{ $category ? __('Edit Income Category') : __('Add Income Category') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Create or update income categories for organizing income') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.income.categories.index') }}" class="erp-btn-secondary">{{ __('Back to Categories') }}</a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="erp-label">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model.live="name" class="erp-input mt-1 @error('name') border-red-500 @enderror" placeholder="{{ __('Category name') }}">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="erp-label">{{ __('Arabic Name') }}</label>
                <input type="text" wire:model.live="nameAr" class="erp-input mt-1" dir="rtl" placeholder="{{ __('اسم التصنيف') }}">
                @error('nameAr') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="erp-label">{{ __('Description') }}</label>
            <textarea wire:model.live="description" rows="3" class="erp-input mt-1" placeholder="{{ __('Optional description') }}"></textarea>
            @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
            <a href="{{ route('app.income.categories.index') }}" class="erp-btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="erp-btn-primary">
                <span wire:loading.remove wire:target="save">{{ $category ? __('Save Changes') : __('Create Category') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </button>
        </div>
    </form>
</div>
