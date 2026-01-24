{{-- Customizable Dashboard with Drag-and-Drop Widget System --}}
<div class="space-y-6">
    {{-- Loading Overlay --}}
    <div wire:loading wire:target="refreshData" class="loading-overlay flex items-center justify-center bg-slate-900/50">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-xl flex flex-col items-center gap-4">
            <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-slate-600 dark:text-slate-300 font-medium">{{ __('Refreshing data...') }}</p>
        </div>
    </div>

    {{-- Header with Customization Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">
                {{ __('Dashboard') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Quick overview of your business today.') }}
            </p>
        </div>
        
        <div class="flex items-center gap-2">
            {{-- Layout Mode Selector --}}
            <div class="hidden sm:flex items-center gap-1 bg-slate-100 dark:bg-slate-700 rounded-lg p-1">
                <button 
                    wire:click="setLayoutMode('default')"
                    class="p-2 rounded {{ $layoutMode === 'default' ? 'bg-white dark:bg-slate-600 shadow' : '' }} text-slate-600 dark:text-slate-300 hover:text-slate-900"
                    title="{{ __('Default Layout') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button 
                    wire:click="setLayoutMode('compact')"
                    class="p-2 rounded {{ $layoutMode === 'compact' ? 'bg-white dark:bg-slate-600 shadow' : '' }} text-slate-600 dark:text-slate-300 hover:text-slate-900"
                    title="{{ __('Compact Layout') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </button>
                <button 
                    wire:click="setLayoutMode('expanded')"
                    class="p-2 rounded {{ $layoutMode === 'expanded' ? 'bg-white dark:bg-slate-600 shadow' : '' }} text-slate-600 dark:text-slate-300 hover:text-slate-900"
                    title="{{ __('Expanded Layout') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                </button>
            </div>

            {{-- Customize Button --}}
            <button 
                wire:click="toggleEditMode"
                class="inline-flex items-center gap-2 px-3 py-2 {{ $isEditing ? 'bg-amber-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }} rounded-xl hover:bg-amber-500 hover:text-white transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="hidden sm:inline">{{ $isEditing ? __('Done') : __('Customize') }}</span>
            </button>

            {{-- Refresh Button --}}
            <button 
                wire:click="refreshData" 
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50"
            >
                <svg wire:loading.class="animate-spin" wire:target="refreshData" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span class="hidden sm:inline" wire:loading.remove wire:target="refreshData">{{ __('Refresh') }}</span>
            </button>
        </div>
    </div>

    {{-- Edit Mode Panel --}}
    @if($isEditing)
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium text-amber-800 dark:text-amber-200">{{ __('Customization Mode') }}</span>
            </div>
            <button 
                wire:click="resetDashboard"
                class="text-sm text-amber-700 dark:text-amber-300 hover:underline"
            >
                {{ __('Reset to Default') }}
            </button>
        </div>
        
        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
            {{ __('Drag widgets to reorder them. Click the eye icon to show/hide widgets.') }}
        </p>
        
        <div class="flex flex-wrap gap-2">
            @foreach($widgets as $widget)
            <button 
                wire:click="toggleWidget('{{ $widget['key'] }}')"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm transition-colors {{ $widget['visible'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}"
            >
                @if($widget['visible'])
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                @endif
                {{ __($widget['title']) }}
            </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Widgets Container (Sortable) --}}
    <div 
        id="widgets-container"
        class="space-y-6"
    >
        @foreach($widgets as $widget)
            @if($widget['visible'])
                <div 
                    class="widget-item {{ $isEditing ? 'cursor-move ring-2 ring-dashed ring-amber-300 dark:ring-amber-600' : '' }}"
                    data-widget="{{ $widget['key'] }}"
                    wire:key="widget-{{ $widget['key'] }}"
                >
                    @switch($widget['key'])
                        @case('quick_actions')
                            @include('livewire.dashboard.partials.quick-actions-widget')
                            @break
                        @case('stats_cards')
                            @include('livewire.dashboard.partials.stats-widget')
                            @break
                        @case('performance')
                            @include('livewire.dashboard.partials.performance-widget')
                            @break
                        @case('sales_chart')
                            @include('livewire.dashboard.partials.sales-chart-widget')
                            @break
                        @case('inventory_chart')
                            @include('livewire.dashboard.partials.inventory-chart-widget')
                            @break
                        @case('payment_mix')
                            @include('livewire.dashboard.partials.payment-mix-widget')
                            @break
                        @case('low_stock')
                            @include('livewire.dashboard.partials.low-stock-widget')
                            @break
                        @case('recent_sales')
                            @include('livewire.dashboard.partials.recent-sales-widget')
                            @break
                        @case('recent_activity')
                            @include('livewire.dashboard.partials.recent-activity-widget')
                            @break
                        @case('quick_stats')
                            @include('livewire.dashboard.partials.quick-stats-widget')
                            @break
                        @case('motorcycle_stats')
                        @case('spares_stats')
                        @case('rental_stats')
                        @case('manufacturing_stats')
                        @case('wood_stats')
                            @include('livewire.dashboard.partials.module-stats-widget', ['widgetConfig' => $widget])
                            @break
                    @endswitch
                </div>
            @endif
        @endforeach
    </div>
</div>

@script
// UNFIXED-01 FIX: Use @script block for proper Livewire 4 component-scoped JavaScript
const componentId = 'customizable-dashboard-' + ($wire.__instance?.id ?? Math.random().toString(36).substr(2, 9));

// Initialize global storages if not exists
window.__lwCharts = window.__lwCharts || {};
window.__lwSortables = window.__lwSortables || {};

// Destroy any existing charts for this component
['sales', 'inventory'].forEach(type => {
    if (window.__lwCharts[componentId + ':' + type]) {
        window.__lwCharts[componentId + ':' + type].destroy();
        delete window.__lwCharts[componentId + ':' + type];
    }
});

// Destroy existing sortable if any
if (window.__lwSortables[componentId]) {
    window.__lwSortables[componentId].destroy();
    delete window.__lwSortables[componentId];
}

function initDashboardCharts() {
    const isRTL = document.documentElement.dir === 'rtl';
    
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        window.__lwCharts[componentId + ':sales'] = new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($salesChartData['labels'] ?? []),
                datasets: [{
                    label: '{{ __("Sales") }}',
                    data: @json($salesChartData['data'] ?? []),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rtl: isRTL,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Inventory Chart
    const inventoryCtx = document.getElementById('inventoryChart');
    if (inventoryCtx) {
        window.__lwCharts[componentId + ':inventory'] = new Chart(inventoryCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['{{ __("In Stock") }}', '{{ __("Low Stock") }}', '{{ __("Out of Stock") }}'],
                datasets: [{
                    data: @json($inventoryChartData['data'] ?? [0, 0, 0]),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rtl: isRTL,
                plugins: {
                    legend: { position: 'bottom', rtl: isRTL, labels: { usePointStyle: true, padding: 20 } }
                },
                cutout: '65%'
            }
        });
    }
}

function initSortable() {
    const container = document.getElementById('widgets-container');
    if (!container || typeof Sortable === 'undefined') return;
    
    window.__lwSortables[componentId] = new Sortable(container, {
        animation: 300,
        handle: '.widget-item',
        ghostClass: 'opacity-50',
        disabled: !@json($isEditing),
        onEnd: (evt) => {
            const order = Array.from(container.querySelectorAll('.widget-item'))
                .map(el => el.dataset.widget);
            $wire.updateWidgetOrder(order);
        }
    });
}

// Load dependencies and initialize
function initAll() {
    initDashboardCharts();
    initSortable();
}

// Load Chart.js and Sortable if not already loaded
let scriptsLoaded = 0;
const scriptsNeeded = 2;

function checkAllLoaded() {
    scriptsLoaded++;
    if (scriptsLoaded >= scriptsNeeded) {
        initAll();
    }
}

if (typeof Chart === 'undefined') {
    const scriptEl = document.createElement('script');
    scriptEl.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    scriptEl.onload = checkAllLoaded;
    document.head.appendChild(scriptEl);
} else {
    checkAllLoaded();
}

if (typeof Sortable === 'undefined') {
    const sortableScript = document.createElement('script');
    sortableScript.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
    sortableScript.onload = checkAllLoaded;
    document.head.appendChild(sortableScript);
} else {
    checkAllLoaded();
}

// Clean up when navigating away
document.addEventListener('livewire:navigating', () => {
    ['sales', 'inventory'].forEach(type => {
        if (window.__lwCharts[componentId + ':' + type]) {
            window.__lwCharts[componentId + ':' + type].destroy();
            delete window.__lwCharts[componentId + ':' + type];
        }
    });
    if (window.__lwSortables[componentId]) {
        window.__lwSortables[componentId].destroy();
        delete window.__lwSortables[componentId];
    }
}, { once: true });
@endscript
