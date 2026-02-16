<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Low Stock Alerts') }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ __('Monitor and manage low stock items') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $stats['total_active'] }}</div>
            <div class="text-sm text-red-600 dark:text-red-400">{{ __('Active Alerts') }}</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['total_acknowledged'] }}</div>
            <div class="text-sm text-yellow-600 dark:text-yellow-400">{{ __('Acknowledged') }}</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['total_resolved_today'] }}</div>
            <div class="text-sm text-green-600 dark:text-green-400">{{ __('Resolved Today') }}</div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['critical_count'] }}</div>
            <div class="text-sm text-purple-600 dark:text-purple-400">{{ __('Critical') }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-4">
                <input type="text" wire:model.live.debounce.300ms="search" 
                    placeholder="{{ __('Search products...') }}"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                
                <select wire:model.live="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="acknowledged">{{ __('Acknowledged') }}</option>
                    <option value="resolved">{{ __('Resolved') }}</option>
                    <option value="all">{{ __('All') }}</option>
                </select>
            </div>
            
            <button type="button" wire:click="refreshAlerts" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                {{ __('Refresh Alerts') }}
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Product') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Warehouse') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Current Qty') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Min Qty') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($alerts as $alert)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $alert->product?->name }}</div>
                            <div class="text-sm text-gray-500">{{ $alert->product?->sku }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $alert->warehouse?->name ?? __('All') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-bold {{ $alert->current_qty <= $alert->min_qty * 0.25 ? 'text-red-600' : 'text-yellow-600' }}">
                                {{ $alert->current_qty }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $alert->min_qty }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($alert->status === 'active')
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Active') }}</span>
                            @elseif($alert->status === 'acknowledged')
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ __('Acknowledged') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Resolved') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($alert->status === 'active')
                                <button type="button" wire:click="acknowledgeAlert({{ $alert->id }})" class="text-yellow-600 hover:text-yellow-800 mr-2">
                                    {{ __('Acknowledge') }}
                                </button>
                            @endif
                            @if($alert->status !== 'resolved')
                                <button type="button" wire:click="resolveAlert({{ $alert->id }})" class="text-green-600 hover:text-green-800">
                                    {{ __('Resolve') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No alerts found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $alerts->links() }}
        </div>
    </div>
</div>
