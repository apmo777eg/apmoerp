<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Currency Management') }}</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Manage supported currencies and their settings') }}</p>
        </div>
        <a href="{{ route('admin.currencies.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
            <x-icon name="plus" class="w-5 h-5" />
            {{ __('Add Currency') }}
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Code') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Arabic Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Symbol') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Decimals') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Base') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($currencies as $currency)
                        <tr wire:key="currency-{{ $currency->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $currency->code }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                {{ $currency->name }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400" dir="rtl">
                                {{ $currency->name_ar ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-lg">
                                {{ $currency->symbol }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                {{ $currency->decimal_places }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($currency->is_base)
                                    <span class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">
                                        {{ __('Base') }}
                                    </span>
                                @else
                                    <button type="button" wire:click="setAsBase({{ $currency->id }})" 
                                            wire:confirm="{{ __('Set :currency as base currency?', ['currency' => $currency->code]) }}"
                                            class="text-gray-400 hover:text-amber-500 text-xs">
                                        {{ __('Set as Base') }}
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" wire:click="toggleActive({{ $currency->id }})" class="focus:outline-none" @if($currency->is_base) disabled @endif>
                                    @if($currency->is_active)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 rounded-full">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.currencies.edit', $currency->id) }}" class="text-gray-400 hover:text-blue-500">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                    </a>
                                    @if(!$currency->is_base)
                                        <button type="button" wire:click="delete({{ $currency->id }})" 
                                                wire:confirm="{{ __('Delete this currency?') }}"
                                                class="text-gray-400 hover:text-red-500">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No currencies configured.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $currencies->links() }}
        </div>
    </div>
</div>
