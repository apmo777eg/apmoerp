@props([
    'formats' => ['xlsx' => 'Excel', 'csv' => 'CSV', 'pdf' => 'PDF'],
    'exportColumns' => [],
    'selectedExportColumns' => [],
    'exportFormat' => 'xlsx',
    'exportDateFormat' => 'Y-m-d',
    'exportIncludeHeaders' => true,
    'exportRespectFilters' => true,
    'exportIncludeTotals' => false,
    'exportMaxRows' => 1000,
    'exportUseBackgroundJob' => false,
])

{{-- Use x-teleport to render modal at body level, escaping any parent CSS context --}}
<template x-teleport="body">
    <div 
        x-data="{ open: true }"
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[9000] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="export-modal-title"
        @keydown.escape.window="$wire.closeExportModal()"
    >
        {{-- Backdrop --}}
        <div 
            class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
            wire:click="closeExportModal"
            aria-hidden="true"
        ></div>
        
        {{-- Modal Content --}}
        <div 
            class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden border border-gray-200 dark:border-gray-700"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop>
        {{-- Header (Sticky) --}}
        <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
            <div>
                <h2 id="export-modal-title" class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Export Data') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Configure your export settings') }}
                </p>
            </div>
            <button 
                type="button" 
                wire:click="closeExportModal"
                aria-label="{{ __('Close modal') }}"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Alerts --}}
        @if(session()->has('error'))
            <div class="mx-6 mt-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if(session()->has('success'))
            <div class="mx-6 mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Scrollable Content Area --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4 min-h-0">
            {{-- Format & Date Format --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="export-format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Format') }}</label>
                    <select id="export-format" wire:model="exportFormat" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @foreach($formats as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="export-date-format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Date Format') }}</label>
                    <select id="export-date-format" wire:model="exportDateFormat" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="Y-m-d">2024-12-31</option>
                        <option value="d/m/Y">31/12/2024</option>
                        <option value="m/d/Y">12/31/2024</option>
                    </select>
                </div>
            </div>

            {{-- Options --}}
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="exportIncludeHeaders" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Include Headers') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="exportRespectFilters" checked class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Respect current filters') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="exportIncludeTotals" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Include totals row') }}</span>
                </label>
            </div>

            {{-- Max Rows --}}
            <div>
                <label for="export-max-rows" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Max Rows') }}</label>
                <select id="export-max-rows" wire:model="exportMaxRows" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="100">100</option>
                    <option value="500">500</option>
                    <option value="1000">1,000</option>
                    <option value="5000">5,000</option>
                    <option value="10000">10,000</option>
                    <option value="all">{{ __('All rows') }}</option>
                </select>
            </div>

            {{-- Select Columns --}}
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select Columns') }}</label>
                    @if(count($exportColumns ?? []) > 0)
                    <button 
                        type="button" 
                        wire:click="toggleAllExportColumns" 
                        class="text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                        aria-pressed="{{ count($selectedExportColumns ?? []) === count($exportColumns ?? []) ? 'true' : 'false' }}"
                    >
                        {{ count($selectedExportColumns ?? []) === count($exportColumns ?? []) ? __('Deselect All') : __('Select All') }}
                    </button>
                    @endif
                </div>
                @if(count($exportColumns ?? []) > 0)
                <div class="relative max-h-60 overflow-y-auto border-2 border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-gray-50 dark:bg-gray-700 scroll-smooth" role="group" aria-label="{{ __('Available columns') }}">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($exportColumns ?? [] as $key => $label)
                            <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white dark:hover:bg-gray-600 rounded-lg transition">
                                <input type="checkbox" wire:model.live="selectedExportColumns" value="{{ $key }}" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500 flex-shrink-0">
                                <span class="text-sm text-gray-700 dark:text-gray-300 break-words">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    {{-- Visual indicator for scrollable content --}}
                    <div class="absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-gray-50 dark:from-gray-700 to-transparent pointer-events-none rounded-b-lg"></div>
                </div>
                <p class="mt-1 text-xs text-gray-500" role="status" aria-live="polite">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        {{ count($selectedExportColumns ?? []) }} {{ __('of') }} {{ count($exportColumns ?? []) }} {{ __('columns selected') }}
                        @if(count($exportColumns ?? []) > 10)
                            · {{ __('Scroll to see all') }}
                        @endif
                    </span>
                </p>
                @else
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center bg-gray-50 dark:bg-gray-700">
                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('No exportable columns configured') }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">{{ __('Please contact support if this issue persists') }}</p>
                </div>
                @endif
            </div>

            {{-- Background Job Option --}}
            @if((int)($exportMaxRows ?? 0) > 5000 || ($exportMaxRows ?? '') === 'all')
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="exportUseBackgroundJob" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500 mt-0.5">
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 block">
                            {{ __('Process in background') }}
                        </span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('Large exports will be queued and you\'ll receive a notification when ready') }}
                        </span>
                    </div>
                </label>
            </div>
            @endif
        </div>

        {{-- Footer (Sticky) --}}
        <div class="flex-shrink-0 flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 sticky bottom-0 z-10">
            <div class="text-sm text-gray-600 dark:text-gray-400" role="status" aria-live="polite">
                @if(count($selectedExportColumns ?? []) > 0)
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ count($selectedExportColumns ?? []) }} {{ __('columns ready') }}
                    </span>
                @else
                    {{ __('No columns selected') }}
                @endif
            </div>
            <div class="flex gap-3">
                <button 
                    type="button"
                    wire:click="closeExportModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                >
                    {{ __('Cancel') }}
                </button>
                <button 
                    type="button"
                    wire:click="export"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    wire:loading.attr="disabled"
                    @if(count($selectedExportColumns ?? []) === 0) disabled @endif
                    aria-disabled="{{ count($selectedExportColumns ?? []) > 0 ? 'false' : 'true' }}"
                >
                    <svg wire:loading wire:target="export" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="export" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>{{ __('Export') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
</template>
