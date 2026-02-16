<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Rental Periods') }}: {{ $module->localized_name }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Configure rental period options for this module') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.modules.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
                {{ __('Back to Modules') }}
            </a>
            <a href="{{ route('admin.modules.rental-periods.create', ['module' => $module->id]) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Add Period') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 p-4 bg-amber-100 border border-amber-300 text-amber-700 rounded-xl">
            {{ session('warning') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Order') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Period Key') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Duration') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Price Multiplier') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Default') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($periods as $period)
                        <tr class="{{ $period->is_active ? '' : 'bg-gray-50 opacity-60' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $period->sort_order }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="px-2 py-1 bg-gray-100 rounded text-sm">{{ $period->period_key }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $period->period_name }}</div>
                                @if($period->period_name_ar)
                                    <div class="text-sm text-gray-500">{{ $period->period_name_ar }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    {{ $periodTypes[$period->period_type] ?? $period->period_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $period->duration_value }} {{ $durationUnits[$period->duration_unit] ?? $period->duration_unit }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">
                                x{{ number_format($period->price_multiplier, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($period->is_default)
                                    <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">{{ __('Default') }}</span>
                                @else
                                    <button type="button" wire:click="setDefault({{ $period->id }})" class="text-sm text-gray-500 hover:text-emerald-600">
                                        {{ __('Set Default') }}
                                    </button>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button type="button" wire:click="toggleActive({{ $period->id }})" class="text-sm">
                                    @if($period->is_active)
                                        <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800">{{ __('Active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-800">{{ __('Inactive') }}</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.modules.rental-periods.edit', ['module' => $module->id, 'period' => $period->id]) }}" class="text-blue-600 hover:text-blue-900 me-3">
                                    {{ __('Edit') }}
                                </a>
                                <button type="button" wire:click="delete({{ $period->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-900">
                                    {{ __('Delete') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                {{ __('No rental periods defined for this module yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
