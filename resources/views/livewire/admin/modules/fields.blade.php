<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Module Fields') }}: {{ $module->localized_name }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Manage custom fields for products in this module') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.modules.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
                {{ __('Back to Modules') }}
            </a>
            <a href="{{ route('admin.modules.fields.create', ['module' => $module->id]) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Add Field') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Order') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Field Key') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Label') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Required') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Show in List') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($fields as $field)
                        <tr class="{{ $field->is_active ? '' : 'bg-gray-50 opacity-60' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $field->sort_order }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="px-2 py-1 bg-gray-100 rounded text-sm">{{ $field->field_key }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $field->field_label }}</div>
                                @if($field->field_label_ar)
                                    <div class="text-sm text-gray-500">{{ $field->field_label_ar }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    {{ $fieldTypes[$field->field_type] ?? $field->field_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($field->is_required)
                                    <span class="text-red-500">{{ __('Yes') }}</span>
                                @else
                                    <span class="text-gray-400">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($field->show_in_list)
                                    <span class="text-emerald-500">{{ __('Yes') }}</span>
                                @else
                                    <span class="text-gray-400">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button type="button" wire:click="toggleActive({{ $field->id }})" class="text-sm">
                                    @if($field->is_active)
                                        <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800">{{ __('Active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-800">{{ __('Inactive') }}</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.modules.fields.edit', ['module' => $module->id, 'field' => $field->id]) }}" class="text-blue-600 hover:text-blue-900 me-3">
                                    {{ __('Edit') }}
                                </a>
                                <button type="button" wire:click="delete({{ $field->id }})" wire:confirm="{{ __('Are you sure you want to delete this field?') }}" class="text-red-600 hover:text-red-900">
                                    {{ __('Delete') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                {{ __('No fields defined for this module yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
