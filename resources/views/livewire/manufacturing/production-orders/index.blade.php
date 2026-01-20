<div class="space-y-6">
    {{-- Global Loading Overlay --}}
    <div wire:loading.delay class="loading-overlay bg-slate-900/20 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl p-6 flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-slate-700 font-medium">{{ __('Loading...') }}</span>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Production Orders') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage manufacturing production orders') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('manufacturing.create')
            <a href="{{ route('app.manufacturing.orders.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('New Production Order') }}
            </a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total Orders') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_orders']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">{{ __('In Progress') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['in_progress']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Completed') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['completed']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">{{ __('Planned Qty') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['planned_quantity']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-rose-100 text-sm">{{ __('Produced Qty') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['produced_quantity']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by order number, product or BOM...') }}" class="erp-input pr-10">
                <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            <div class="relative">
                <select wire:model.live="status" class="erp-input w-full lg:w-40">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="confirmed">{{ __('Confirmed') }}</option>
                    <option value="released">{{ __('Released') }}</option>
                    <option value="in_progress">{{ __('In Progress') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>
            <div class="relative">
                <select wire:model.live="priority" class="erp-input w-full lg:w-40">
                    <option value="">{{ __('All Priorities') }}</option>
                    <option value="low">{{ __('Low') }}</option>
                    <option value="normal">{{ __('Normal') }}</option>
                    <option value="high">{{ __('High') }}</option>
                    <option value="urgent">{{ __('Urgent') }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th wire:click="sortBy('order_number')" class="cursor-pointer hover:bg-slate-100">
                            {{ __('Order Number') }}
                            @if($sortField === 'order_number')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('BOM') }}</th>
                        <th>{{ __('Planned') }}</th>
                        <th>{{ __('Produced') }}</th>
                        <th>{{ __('Progress') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Dates') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="font-medium">{{ $order->order_number }}</td>
                            <td>
                                <div class="font-medium">{{ $order->product->name ?? '-' }}</div>
                                @if($order->product->sku)
                                    <div class="text-xs text-slate-500">{{ $order->product->sku }}</div>
                                @endif
                            </td>
                            <td>{{ $order->bom->bom_number ?? '-' }}</td>
                            {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                            <td>{{ number_format(decimal_float($order->quantity_planned), 2) }}</td>
                            <td>{{ number_format(decimal_float($order->quantity_produced), 2) }}</td>
                            <td>
                                @php
                                    $progress = $order->quantity_planned > 0 
                                        ? ($order->quantity_produced / $order->quantity_planned) * 100 
                                        : 0;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 transition-all" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-600 min-w-[3rem]">{{ number_format($progress, 1) }}%</span>
                                </div>
                            </td>
                            <td>
                                @if($order->priority === 'urgent')
                                    <span class="erp-badge erp-badge-danger">{{ __('Urgent') }}</span>
                                @elseif($order->priority === 'high')
                                    <span class="erp-badge erp-badge-warning">{{ __('High') }}</span>
                                @elseif($order->priority === 'normal')
                                    <span class="erp-badge erp-badge-info">{{ __('Normal') }}</span>
                                @else
                                    <span class="erp-badge erp-badge-secondary">{{ __('Low') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($order->status === 'completed')
                                    <span class="erp-badge erp-badge-success">{{ __('Completed') }}</span>
                                @elseif($order->status === 'in_progress')
                                    <span class="erp-badge erp-badge-primary">{{ __('In Progress') }}</span>
                                @elseif($order->status === 'released')
                                    <span class="erp-badge erp-badge-info">{{ __('Released') }}</span>
                                @elseif($order->status === 'confirmed')
                                    <span class="erp-badge erp-badge-secondary">{{ __('Confirmed') }}</span>
                                @elseif($order->status === 'draft')
                                    <span class="erp-badge erp-badge-warning">{{ __('Draft') }}</span>
                                @else
                                    <span class="erp-badge erp-badge-danger">{{ __('Cancelled') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-xs">
                                    <div><strong>{{ __('Start:') }}</strong> {{ $order->planned_start_date?->format('Y-m-d') ?? '-' }}</div>
                                    <div><strong>{{ __('End:') }}</strong> {{ $order->planned_end_date?->format('Y-m-d') ?? '-' }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @can('manufacturing.edit')
                                    <a href="{{ route('app.manufacturing.orders.edit', $order) }}" class="text-emerald-600 hover:text-emerald-900" title="{{ __('Edit') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-500">
                                {{ __('No production orders found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
