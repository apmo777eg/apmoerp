<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Module Report') }}: {{ $module->localized_name }}</h1>
            <p class="text-gray-600 mt-1">{{ __('View inventory and product data for this module') }}</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
            {{ __('Back to Reports') }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if($isSuperAdmin)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Branch') }}</label>
	                    @if($branchContextId)
	                        <div class="w-full px-4 py-2 border border-emerald-200 rounded-xl bg-emerald-50">
	                            <div class="flex items-center justify-between gap-2">
	                                <div class="min-w-0">
	                                    <p class="text-sm font-semibold text-emerald-900 truncate">
	                                        {{ $branchContextName ?? __('Selected Branch') }}
	                                    </p>
	                                    <p class="text-xs text-emerald-700">
	                                        {{ __('Using global branch context') }}
	                                    </p>
	                                </div>
	                                <span class="text-[11px] text-emerald-700 bg-white/70 border border-emerald-200 rounded-full px-2 py-1">
	                                    {{ __('Locked') }}
	                                </span>
	                            </div>
	                        </div>
	                    @else
	                        <select wire:model.live="selectedBranchId" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
	                            <option value="">{{ __('All Branches') }}</option>
	                            @foreach($branches as $branch)
	                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
	                            @endforeach
	                        </select>
	                    @endif
                </div>
            @endif

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

    @if(!empty($summary))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-sm text-gray-500">{{ __('Total Products') }}</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_products'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-sm text-gray-500">{{ __('Total Value') }}</p>
                <p class="text-2xl font-bold text-emerald-600">{{ number_format($summary['total_value'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-sm text-gray-500">{{ __('Total Cost') }}</p>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <p class="text-sm text-gray-500">{{ __('Potential Profit') }}</p>
                <p class="text-2xl font-bold text-purple-600">{{ number_format(($summary['total_value'] ?? 0) - ($summary['total_cost'] ?? 0), 2) }}</p>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <p class="text-sm text-gray-600">{{ __('Showing :count products', ['count' => count($reportData)]) }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Code') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Cost') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Price') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('Created') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reportData as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $product['code'] ?? '-' }}</code>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $product['name'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($product['standard_cost'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-emerald-600">
                                {{ number_format($product['default_price'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ ($product['status'] ?? '') === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($product['status'] ?? '-') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($product['created_at'])->format('Y-m-d') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                {{ __('No products found for this module') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
