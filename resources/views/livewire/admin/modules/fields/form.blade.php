<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $fieldId ? __('Edit Field') : __('New Field') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Configure custom field for module: :module', ['module' => $module->display_name]) }}
            </p>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6 max-w-3xl">
        <div class="erp-card p-6 space-y-4">
            <h3 class="font-medium text-slate-800 dark:text-slate-200 border-b pb-2">{{ __('Basic Information') }}</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Label (English)') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="field_label" class="erp-input w-full" required>
                    @error('field_label') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Label (Arabic)') }}</label>
                    <input type="text" wire:model="field_label_ar" class="erp-input w-full" dir="rtl">
                    @error('field_label_ar') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Field Key') }} 
                        @if(!$fieldId)
                            <span class="text-xs text-slate-400 font-normal">({{ __('auto-generated from label') }})</span>
                        @else
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <input type="text" wire:model="field_key" 
                           class="erp-input w-full font-mono text-sm {{ !$fieldId ? 'bg-slate-50 dark:bg-slate-800' : '' }}" 
                           pattern="[a-z_]+" 
                           required 
                           {{ $fieldId ? 'readonly' : '' }}
                           placeholder="{{ __('Auto-generated...') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ __('Lowercase letters and underscores only') }}</p>
                    @error('field_key') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Field Type') }} <span class="text-red-500">*</span></label>
                    <select wire:model.live="field_type" class="erp-input w-full" required>
                        @foreach($fieldTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('field_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Placeholder (English)') }}</label>
                    <input type="text" wire:model="placeholder" class="erp-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Placeholder (Arabic)') }}</label>
                    <input type="text" wire:model="placeholder_ar" class="erp-input w-full" dir="rtl">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Default Value') }}</label>
                    <input type="text" wire:model="default_value" class="erp-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Field Group') }}</label>
                    <input type="text" wire:model="field_group" class="erp-input w-full">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Validation') }}</label>
                    <div class="space-y-2 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="is_required" class="rounded border-slate-300">
                            <span class="text-slate-700 dark:text-slate-300">{{ __('Required field') }}</span>
                        </label>
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-600">
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Additional rules') }} <span class="text-amber-600">({{ __('advanced') }})</span></label>
                            <input type="text" wire:model="validation_rules" class="erp-input w-full font-mono text-xs" placeholder="e.g. max:255|min:3">
                            <p class="text-xs text-slate-400 mt-1">{{ __('Optional: Laravel validation syntax for developers') }}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Sort Order') }}</label>
                    <input type="number" wire:model="sort_order" class="erp-input w-full" min="0">
                </div>
            </div>
        </div>

        @if(in_array($field_type, ['select', 'multiselect', 'radio']))
        <div class="erp-card p-6 space-y-4">
            <h3 class="font-medium text-slate-800 dark:text-slate-200 border-b pb-2">{{ __('Options') }}</h3>
            
            <div class="space-y-2">
                @forelse($field_options as $key => $value)
                    <div class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-800 rounded">
                        <span class="font-mono text-sm text-slate-600 dark:text-slate-400 w-24">{{ $key }}</span>
                        <span class="text-slate-700 dark:text-slate-300 flex-1">{{ $value }}</span>
                        <button type="button" wire:click="removeOption('{{ $key }}')" class="text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No options added yet') }}</p>
                @endforelse
            </div>

            <div class="flex items-end gap-2 pt-2 border-t">
                <div class="flex-1">
                    <label class="block text-xs text-slate-500 mb-1">{{ __('Key') }}</label>
                    <input type="text" wire:model="newOptionKey" class="erp-input w-full text-sm font-mono" placeholder="{{ __('option_key') }}">
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-slate-500 mb-1">{{ __('Value') }}</label>
                    <input type="text" wire:model="newOptionValue" class="erp-input w-full text-sm" placeholder="{{ __('Option Label') }}">
                </div>
                <button type="button" wire:click="addOption" class="px-3 py-2 bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
        </div>
        @endif

        <div class="erp-card p-6 space-y-4">
            <h3 class="font-medium text-slate-800 dark:text-slate-200 border-b pb-2">{{ __('Display Settings') }}</h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_required" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Required') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_searchable" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Searchable') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_filterable" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Filterable') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="show_in_list" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Show in List') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="show_in_form" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Show in Form') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.modules.fields', ['module' => $module->id]) }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $fieldId ? __('Update') : __('Create') }}
            </button>
        </div>
    </form>
</div>
