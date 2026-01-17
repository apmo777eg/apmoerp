@props([
    'headers' => [],
    'rows' => [],
    'sortable' => true,
    'filterable' => true,
    'exportable' => true,
    'selectable' => false,
    'actions' => null,
    'emptyMessage' => 'No data available',
    'emptyIcon' => '📋',
])

<div 
    x-data="{
        sortColumn: null,
        sortDirection: 'asc',
        search: '',
        selectedRows: [],
        
        toggleSort(column) {
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }
        },
        
        toggleSelectAll() {
            if (this.selectedRows.length === @json(count($rows))) {
                this.selectedRows = [];
            } else {
                this.selectedRows = @json(array_keys($rows));
            }
        },
        
        isSelected(index) {
            return this.selectedRows.includes(index);
        },
        
        toggleRow(index) {
            if (this.isSelected(index)) {
                this.selectedRows = this.selectedRows.filter(i => i !== index);
            } else {
                this.selectedRows.push(index);
            }
        }
    }"
    class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden"
>
    <!-- Table Header with Actions -->
    @if($filterable || $exportable || $actions)
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Search -->
            @if($filterable)
            <div class="flex-1 max-w-lg">
                <div class="relative">
                    <input 
                        type="text" 
                        x-model="search"
                        placeholder="{{ __('Search...') }}"
                        class="w-full px-4 py-2 pl-10 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                @if($exportable)
                <button 
                    @click="$dispatch('export-data')"
                    class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 rtl:ml-2 rtl:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('Export') }}
                    </span>
                </button>
                @endif

                @if($actions)
                {{ $actions }}
                @endif
            </div>
        </div>

        <!-- Bulk Actions -->
        <div x-show="selectedRows.length > 0" x-cloak class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-center justify-between">
                <span class="text-sm text-blue-700 dark:text-blue-300">
                    <span x-text="selectedRows.length"></span> {{ __('item(s) selected') }}
                </span>
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <button 
                        @click="$dispatch('bulk-action', {action: 'delete', ids: selectedRows})"
                        class="px-3 py-1 text-sm text-red-700 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                    >
                        {{ __('Delete') }}
                    </button>
                    <button 
                        @click="selectedRows = []"
                        class="px-3 py-1 text-sm text-gray-700 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-200"
                    >
                        {{ __('Clear') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Table -->
    @if(count($rows) > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    @if($selectable)
                    <th scope="col" class="px-6 py-3 text-left">
                        <input 
                            type="checkbox"
                            @click="toggleSelectAll"
                            :checked="selectedRows.length === @json(count($rows))"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                    </th>
                    @endif

                    @foreach($headers as $key => $header)
                    <th 
                        scope="col" 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider {{ $sortable ? 'cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-800' : '' }}"
                        @if($sortable) @click="toggleSort('{{ $key }}')" @endif
                    >
                        <div class="flex items-center space-x-1 rtl:space-x-reverse">
                            <span>{{ $header }}</span>
                            @if($sortable)
                            <svg 
                                x-show="sortColumn === '{{ $key }}' && sortDirection === 'asc'"
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            <svg 
                                x-show="sortColumn === '{{ $key }}' && sortDirection === 'desc'"
                                class="w-4 h-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($rows as $index => $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    @if($selectable)
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input 
                            type="checkbox"
                            @click="toggleRow({{ $index }})"
                            :checked="isSelected({{ $index }})"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                    </td>
                    @endif

                    @foreach($headers as $key => $header)
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $row[$key] ?? '-' }}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <!-- Empty State -->
    <div class="px-6 py-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
            <span class="text-3xl">{{ $emptyIcon }}</span>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            {{ __('No Data') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $emptyMessage }}
        </p>
    </div>
    @endif
</div>

<style>
[x-cloak] { display: none !important; }
</style>
