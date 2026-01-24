<div class="space-y-4">
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
@endphp

    {{-- Filters Bar --}}
    @if ($showSearch || $showFilters || !empty($filters))
        <div class="erp-card p-4">
            <div class="flex flex-wrap items-center gap-3 {{ $dir === 'rtl' ? 'flex-row-reverse' : '' }}">
                {{-- Search Input --}}
                @if ($showSearch)
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <span class="absolute {{ $dir === 'rtl' ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input type="search" 
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="{{ __('Search...') }}"
                                   class="erp-input {{ $dir === 'rtl' ? 'pr-10' : 'pl-10' }} rounded-full">
                        </div>
                    </div>
                @endif

                {{-- Filter Dropdowns --}}
                @if ($showFilters && !empty($filters))
                    @foreach ($filters as $filter)
                        @php
                            $filterName = $filter['name'] ?? '';
                            $filterLabel = $filter['label'] ?? $filterName;
                            $filterOptions = $filter['options'] ?? [];
                        @endphp
                        @if ($filterName)
                            <div class="min-w-[150px]">
                                <select wire:model.live="filterValues.{{ $filterName }}" class="erp-input text-sm">
                                    <option value="">{{ __($filterLabel) }}</option>
                                    @foreach ($filterOptions as $value => $label)
                                        <option value="{{ $value }}">{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endforeach
                @endif

                {{-- Clear Filters Button --}}
                @if ($search || array_filter($filterValues ?? []))
                    <button wire:click="clearFilters" class="erp-btn-secondary text-xs px-3 py-2">
                        <svg class="w-4 h-4 {{ $dir === 'rtl' ? 'ml-1' : 'mr-1' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ __('Clear') }}
                    </button>
                @endif

                {{-- Per Page Selector --}}
                @if ($showPerPage ?? true)
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>{{ __('Show') }}</span>
                        <select wire:model.live="perPage" class="erp-input w-20 text-sm py-1.5">
                            @foreach ($perPageOptions ?? [10, 25, 50, 100] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Bulk Actions --}}
    @if (($selectable ?? false) && count($selected ?? []) > 0)
        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-xl border border-emerald-200">
            <span class="text-sm font-medium text-emerald-700">
                {{ count($selected) }} {{ __('selected') }}
            </span>
            @foreach ($actions ?? [] as $action)
                @if (($action['bulk'] ?? false))
                    <button wire:click="executeBulkAction('{{ $action['name'] }}')" 
                            class="text-sm font-medium {{ $action['class'] ?? 'text-emerald-600 hover:text-emerald-800' }}">
                        {{ __($action['label'] ?? $action['name']) }}
                    </button>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm shadow-emerald-500/10 bg-white dark:bg-slate-900">
        <div class="overflow-x-auto max-h-[70vh]">
            <table class="erp-table min-w-full {{ $tableClass ?? '' }}">
                {{-- Sticky Header --}}
                <thead class="sticky top-0 z-10 bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        {{-- Checkbox Column --}}
                        @if ($selectable ?? false)
                            <th class="w-12 px-4 py-3">
                                <input type="checkbox" 
                                       wire:click="toggleSelectAll"
                                       class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                       @if (count($selected ?? []) === count($rows ?? [])) checked @endif>
                            </th>
                        @endif

                        {{-- Column Headers --}}
                        @foreach ($columns as $column)
                            @php
                                $colName = $column['name'] ?? '';
                                $colLabel = $column['label'] ?? $colName;
                                $sortable = $column['sortable'] ?? true;
                                $width = $column['width'] ?? '';
                            @endphp
                            <th class="px-4 py-3 {{ $dir === 'rtl' ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider"
                                style="{{ $width ? 'width: ' . $width : '' }}">
                                @if ($sortable && $colName)
                                    <button wire:click="sortBy('{{ $colName }}')" 
                                            class="flex items-center gap-1.5 hover:text-emerald-600 transition-colors {{ $dir === 'rtl' ? 'flex-row-reverse' : '' }}">
                                        <span>{{ __($colLabel) }}</span>
                                        <span class="flex flex-col">
                                            <svg class="w-3 h-3 {{ ($sortField ?? '') === $colName && ($sortDirection ?? 'asc') === 'asc' ? 'text-emerald-600' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 5l-8 8h16z"/>
                                            </svg>
                                            <svg class="w-3 h-3 -mt-1 {{ ($sortField ?? '') === $colName && ($sortDirection ?? 'asc') === 'desc' ? 'text-emerald-600' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 19l8-8H4z"/>
                                            </svg>
                                        </span>
                                    </button>
                                @else
                                    {{ __($colLabel) }}
                                @endif
                            </th>
                        @endforeach

                        {{-- Actions Column --}}
                        @if (!empty($actions ?? []))
                            <th class="px-4 py-3 {{ $dir === 'rtl' ? 'text-left' : 'text-right' }} text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        @endif
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @php $displayRows = $displayRows ?? $rows ?? []; @endphp
                    @forelse ($displayRows as $index => $row)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/50 dark:bg-slate-800/30' }} hover:bg-emerald-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            {{-- Checkbox --}}
                            @if ($selectable ?? false)
                                <td class="px-4 py-3">
                                    <input type="checkbox" 
                                           wire:model.live="selected"
                                           value="{{ $row['id'] ?? '' }}"
                                           class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                </td>
                            @endif

                            {{-- Data Cells --}}
                            @foreach ($columns as $column)
                                @php
                                    $colName = $column['name'] ?? null;
                                    $colType = $column['type'] ?? 'text';
                                    $colFormat = $column['format'] ?? null;
                                    $value = $colName !== null ? ($row[$colName] ?? '') : '';
                                @endphp
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 {{ $dir === 'rtl' ? 'text-right' : 'text-left' }}">
                                    @switch($colType)
                                        @case('badge')
                                            @php
                                                $badgeColors = $column['colors'] ?? [];
                                                $badgeClass = $badgeColors[$value] ?? 'bg-slate-100 text-slate-700';
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                                {{ __($value) }}
                                            </span>
                                            @break

                                        @case('boolean')
                                            @if ($value)
                                                <span class="inline-flex items-center text-green-600">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-slate-400">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                            @endif
                                            @break

                                        @case('date')
                                            @if ($value)
                                                {{ \Carbon\Carbon::parse($value)->format($colFormat ?? 'M d, Y') }}
                                            @endif
                                            @break

                                        @case('datetime')
                                            @if ($value)
                                                {{ \Carbon\Carbon::parse($value)->format($colFormat ?? 'M d, Y H:i') }}
                                            @endif
                                            @break

                                        @case('currency')
                                        @case('money')
                                            @php
                                                $currency = $column['currency'] ?? '$';
                                            @endphp
                                            {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                                            <span class="font-medium">{{ $currency }}{{ number_format(decimal_float($value), 2) }}</span>
                                            @break

                                        @case('image')
                                            @if ($value)
                                                <img src="{{ $value }}" alt="" class="w-10 h-10 rounded-lg object-cover">
                                            @else
                                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            @break

                                        @case('link')
                                            <a href="{{ $value }}" class="erp-link" target="_blank">
                                                {{ $column['link_text'] ?? $value }}
                                            </a>
                                            @break

                                        @default
                                            {{ e($value) }}
                                    @endswitch
                                </td>
                            @endforeach

                            {{-- Action Buttons --}}
                            @if (!empty($actions ?? []))
                                <td class="px-4 py-3 {{ $dir === 'rtl' ? 'text-left' : 'text-right' }}">
                                    <div class="flex items-center justify-end gap-1.5 {{ $dir === 'rtl' ? 'flex-row-reverse' : '' }}">
                                        @foreach ($actions as $action)
                                            @if (!($action['bulk'] ?? false))
                                                @php
                                                    $actionName = $action['name'] ?? '';
                                                    $actionLabel = $action['label'] ?? $actionName;
                                                    $actionIcon = $action['icon'] ?? '';
                                                    $actionClass = $action['class'] ?? 'erp-btn-icon';
                                                    $actionConfirm = $action['confirm'] ?? false;
                                                @endphp
                                                <button wire:click="executeAction('{{ $actionName }}', '{{ $row['id'] ?? '' }}')"
                                                        @if ($actionConfirm) wire:confirm="{{ __($actionConfirm) }}" @endif
                                                        class="{{ $actionClass }}"
                                                        title="{{ __($actionLabel) }}">
                                                    @if ($actionIcon)
                                                        {{-- SECURITY: $actionIcon is from developer-defined schema array --}}
                                                        {{-- It is not user input. sanitize_svg_icon provides defense-in-depth --}}
                                                        {!! sanitize_svg_icon($actionIcon) !!}
                                                    @else
                                                        {{ __($actionLabel) }}
                                                    @endif
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + (($selectable ?? false) ? 1 : 0) + (!empty($actions ?? []) ? 1 : 0) }}" 
                                class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-slate-500">{{ $emptyMessage ?? __('No records found.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if (($showPagination ?? true) && ($totalPages ?? 0) > 1)
        <div class="flex flex-wrap items-center justify-between gap-4 px-2">
            {{-- Results Info --}}
            <div class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Showing') }} 
                <span class="font-medium">{{ ((($currentPage ?? 1) - 1) * ($perPage ?? 10)) + 1 }}</span>
                {{ __('to') }}
                <span class="font-medium">{{ min(($currentPage ?? 1) * ($perPage ?? 10), $totalRows ?? 0) }}</span>
                {{ __('of') }}
                <span class="font-medium">{{ $totalRows ?? 0 }}</span>
                {{ __('results') }}
            </div>

            {{-- Pagination Buttons --}}
            <div class="flex items-center gap-1">
                {{-- Previous --}}
                <button wire:click="previousPage" 
                        class="erp-btn-icon {{ ($currentPage ?? 1) <= 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                        @if (($currentPage ?? 1) <= 1) disabled @endif>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Page Numbers --}}
                @for ($i = max(1, ($currentPage ?? 1) - 2); $i <= min($totalPages ?? 1, ($currentPage ?? 1) + 2); $i++)
                    <button wire:click="gotoPage({{ $i }})"
                            class="w-9 h-9 rounded-lg text-sm font-medium transition-all {{ $i === ($currentPage ?? 1) ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $i }}
                    </button>
                @endfor

                {{-- Next --}}
                <button wire:click="nextPage"
                        class="erp-btn-icon {{ ($currentPage ?? 1) >= ($totalPages ?? 1) ? 'opacity-50 cursor-not-allowed' : '' }}"
                        @if (($currentPage ?? 1) >= ($totalPages ?? 1)) disabled @endif>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>