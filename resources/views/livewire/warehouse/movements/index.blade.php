<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Stock Movements') }}</h1>
            <p class="text-sm text-slate-500">{{ __('View all warehouse stock movements') }}</p>
        </div>
    </div>

    @if(isset($stats))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total Movements') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Inbound') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['in']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">{{ __('Outbound') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['out']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">{{ __('Total Value') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_value'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search movements...') }}" class="erp-input">
            </div>
            <select wire:model.live="directionFilter" class="erp-input lg:w-40">
                <option value="">{{ __('All Directions') }}</option>
                <option value="in">{{ __('Inbound') }}</option>
                <option value="out">{{ __('Outbound') }}</option>
            </select>
            <select wire:model.live="warehouseFilter" class="erp-input lg:w-48">
                <option value="">{{ __('All Warehouses') }}</option>
                @foreach($warehouses ?? [] as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th>{{ __('Direction') }}</th>
                        <th>{{ __('Quantity') }}</th>
                        <th>{{ __('Value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements ?? [] as $movement)
                        <tr class="hover:bg-slate-50">
                            <td class="text-sm text-slate-500">{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="font-mono text-sm">{{ $movement->reference_number }}</td>
                            <td class="font-medium">{{ $movement->product?->name ?? '-' }}</td>
                            <td>{{ $movement->warehouse->name ?? '-' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full {{ $movement->direction === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $movement->direction === 'in' ? '↓ ' . __('In') : '↑ ' . __('Out') }}
                                </span>
                            </td>
                            <td class="font-medium">{{ number_format($movement->qty, 2) }} {{ $movement->uom }}</td>
                            <td class="text-sm">{{ number_format($movement->valuated_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="text-slate-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                    <p class="text-lg font-medium">{{ __('No movements found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($movements))
            <div class="mt-4">{{ $movements->links() }}</div>
        @endif
    </div>
</div>
