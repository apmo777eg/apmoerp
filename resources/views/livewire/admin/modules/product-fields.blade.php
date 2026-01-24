<div class="p-6">
@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp

    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ __('Module Product Fields') }}</h1>
                <p class="text-slate-500">{{ __('Define custom fields for products in each module') }}</p>
            </div>
            <a href="{{ route('admin.modules.index') }}" class="erp-btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Back to Modules') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Select Module') }}</label>
                <select wire:model.live="moduleId" class="erp-input w-full max-w-md">
                    <option value="">{{ __('Choose a module...') }}</option>
                    @foreach ($modules as $mod)
                        <option value="{{ $mod['id'] }}">
                            {{ $locale === 'ar' && !empty($mod['name_ar']) ? $mod['name_ar'] : $mod['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($moduleId)
                <a href="{{ route('admin.modules.product-fields.create', ['moduleId' => $moduleId]) }}" class="erp-btn-primary mt-6">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add Field') }}
                </a>
            @endif
        </div>
    </div>

    @if ($moduleId && $module)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-semibold text-slate-800">
                    {{ __('Fields for') }}: {{ $locale === 'ar' && $module->name_ar ? $module->name_ar : $module->name }}
                </h3>
            </div>

            @if (count($fields) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-{{ $dir === 'rtl' ? 'right' : 'left' }}">{{ __('Field') }}</th>
                                <th class="px-4 py-3 text-{{ $dir === 'rtl' ? 'right' : 'left' }}">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Required') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('In Form') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('In List') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Order') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($fields as $field)
                                <tr class="hover:bg-slate-50 transition-colors {{ !$field['is_active'] ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">
                                            {{ $locale === 'ar' && $field['field_label_ar'] ? $field['field_label_ar'] : $field['field_label'] }}
                                        </div>
                                        <div class="text-xs text-slate-500 font-mono">{{ $field['field_key'] }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-lg">
                                            {{ $fieldTypes[$field['field_type']] ?? $field['field_type'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($field['is_required'])
                                            <span class="text-red-500">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($field['show_in_form'])
                                            <span class="text-emerald-500">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($field['show_in_list'])
                                            <span class="text-emerald-500">
                                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="toggleActive({{ $field['id'] }})" class="text-sm">
                                            @if ($field['is_active'])
                                                <span class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-lg">{{ __('Active') }}</span>
                                            @else
                                                <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded-lg">{{ __('Inactive') }}</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-500">
                                        {{ $field['sort_order'] }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('admin.modules.product-fields.edit', ['moduleId' => $moduleId, 'field' => $field['id']]) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="{{ __('Edit') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button wire:click="delete({{ $field['id'] }})" wire:confirm="{{ __('Are you sure you want to delete this field?') }}" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="{{ __('Delete') }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-lg font-medium mb-2">{{ __('No fields defined yet') }}</p>
                    <p class="text-sm">{{ __('Click "Add Field" to create custom fields for this module\'s products') }}</p>
                </div>
            @endif
        </div>
    @elseif (!$moduleId)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <p class="text-lg font-medium text-slate-600 mb-2">{{ __('Select a Module') }}</p>
            <p class="text-slate-400">{{ __('Choose a module from the dropdown above to manage its product fields') }}</p>
        </div>
    @endif
</div>