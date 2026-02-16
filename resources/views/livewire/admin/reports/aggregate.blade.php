<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Aggregate Reports') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('View combined reports across all branches') }}</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
            {{ __('Back to Reports') }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('From Date') }}</label>
                <input type="date" wire:model="dateFrom" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('To Date') }}</label>
                <input type="date" wire:model="dateTo" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="generateReport" class="w-full px-6 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                    {{ __('Update Report') }}
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white">
            <p class="text-emerald-100 text-sm">{{ __('Total Sales') }}</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($totals['sales'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
            <p class="text-blue-100 text-sm">{{ __('Total Purchases') }}</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($totals['purchases'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white">
            <p class="text-red-100 text-sm">{{ __('Total Expenses') }}</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($totals['expenses'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
            <p class="text-purple-100 text-sm">{{ __('Net Profit') }}</p>
            <p class="text-3xl font-bold mt-1 {{ ($totals['profit'] ?? 0) < 0 ? 'text-red-200' : '' }}">
                {{ number_format($totals['profit'] ?? 0, 2) }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-800">{{ __('Branch Performance') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Branch') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Sales') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Purchases') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Expenses') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Profit') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($aggregateData as $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ $data['branch']['name'] ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ $data['branch']['code'] ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-emerald-600">{{ number_format($data['sales_total'], 2) }}</div>
                                <div class="text-xs text-gray-500">{{ $data['sales_count'] }} {{ __('transactions') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-blue-600">{{ number_format($data['purchases_total'], 2) }}</div>
                                <div class="text-xs text-gray-500">{{ $data['purchases_count'] }} {{ __('orders') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-red-600">{{ number_format($data['expenses_total'], 2) }}</div>
                                <div class="text-xs text-gray-500">{{ $data['expenses_count'] }} {{ __('entries') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold {{ $data['profit'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ number_format($data['profit'], 2) }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                {{ __('No data available for the selected period') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(!empty($aggregateData))
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td class="px-6 py-4">{{ __('Total') }}</td>
                            <td class="px-6 py-4 text-emerald-600">{{ number_format($totals['sales'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-blue-600">{{ number_format($totals['purchases'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-red-600">{{ number_format($totals['expenses'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 {{ ($totals['profit'] ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ number_format($totals['profit'] ?? 0, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
