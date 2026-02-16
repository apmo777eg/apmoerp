<div class="container mx-auto px-4 py-6">
    <x-ui.page-header 
        title="{{ __('Production Timeline') }}"
        subtitle="{{ __('Visual overview of production orders and schedules') }}"
    />

    {{-- Controls --}}
    <div class="erp-card p-4 mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
        {{-- View Mode --}}
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400">{{ __('View') }}:</label>
            <select wire:model.live="viewMode" class="erp-input w-auto text-sm">
                <option value="week">{{ __('Week') }}</option>
                <option value="month">{{ __('Month') }}</option>
            </select>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousPeriod" class="erp-btn erp-btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" wire:click="goToToday" class="erp-btn erp-btn-secondary text-sm">{{ __('Today') }}</button>
            <button type="button" wire:click="nextPeriod" class="erp-btn erp-btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 ml-2">
                {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            </span>
        </div>

        {{-- Status Filter --}}
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400">{{ __('Status') }}:</label>
            <select wire:model.live="status" class="erp-input w-auto text-sm">
                <option value="">{{ __('All') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="planned">{{ __('Planned') }}</option>
                <option value="in_progress">{{ __('In Progress') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
            </select>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="erp-card mt-4 overflow-hidden">
        {{-- Days Header --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <div class="w-64 shrink-0 p-3 border-r border-gray-200 dark:border-gray-700">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Production Order') }}</span>
            </div>
            <div class="flex-1 flex">
                @foreach($this->days as $day)
                    <div @class([
                        'flex-1 text-center py-2 text-xs border-r border-gray-100 dark:border-gray-700 last:border-r-0',
                        'bg-blue-50 dark:bg-blue-900/20' => $day['is_today'],
                        'bg-gray-100 dark:bg-gray-800' => $day['is_weekend'] && !$day['is_today'],
                    ])>
                        <div class="text-gray-500 dark:text-gray-400">{{ $day['day'] }}</div>
                        <div class="font-medium {{ $day['is_today'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $day['day_num'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Orders --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($this->orders as $order)
                <div class="flex hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    {{-- Order Info --}}
                    <div class="w-64 shrink-0 p-3 border-r border-gray-200 dark:border-gray-700">
                        <a href="{{ route('app.manufacturing.orders.edit', $order['id']) }}" class="block">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ $order['order_number'] }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-xs {{ $this->getPriorityColor($order['priority']) }}">
                                    {{ substr(ucfirst($order['priority']), 0, 1) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $order['product_name'] }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order['quantity_produced'] }}/{{ $order['quantity_planned'] }}</span>
                                <div class="flex-1 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $order['progress'] >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min(100, $order['progress']) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order['progress'] }}%</span>
                            </div>
                        </a>
                    </div>

                    {{-- Timeline Bar --}}
                    <div class="flex-1 relative py-3 px-1">
                        @php
                            $position = $this->getTimelinePosition($order);
                        @endphp
                        <div class="absolute top-1/2 -translate-y-1/2 h-6 rounded flex items-center px-2 text-xs text-white shadow-sm overflow-hidden cursor-pointer hover:opacity-80 transition-opacity {{ $this->getStatusColor($order['status']) }} {{ $order['is_overdue'] ? 'ring-2 ring-red-500' : '' }}"
                             style="left: {{ $position['left'] }}%; width: {{ $position['width'] }}%;"
                             title="{{ $order['order_number'] }}: {{ $order['start_date'] }} - {{ $order['due_date'] }}">
                            <span class="truncate">{{ ucfirst(str_replace('_', ' ', $order['status'])) }}</span>
                            @if($order['is_overdue'])
                                <svg class="w-3 h-3 ml-1 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('No production orders in this period') }}</p>
                    <a href="{{ route('app.manufacturing.orders.create') }}" class="mt-4 inline-block erp-btn erp-btn-primary text-sm">
                        {{ __('Create Production Order') }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Legend --}}
    <div class="erp-card p-4 mt-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Legend') }}</h4>
        <div class="flex flex-wrap gap-4 text-xs">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-gray-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Draft') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-blue-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Planned') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-amber-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('In Progress') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-green-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Completed') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-red-400 ring-2 ring-red-500"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Overdue') }}</span>
            </div>
        </div>
    </div>
</div>
