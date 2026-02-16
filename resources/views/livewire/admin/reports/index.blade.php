<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Reports Center') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Generate and export reports for your data') }}</p>
        </div>
        @if($isSuperAdmin)
            <a href="{{ route('admin.reports.aggregate') }}" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                {{ __('Aggregate Reports') }}
            </a>
        @endif
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Report Filters') }}</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            @if($isSuperAdmin)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Branch') }}</label>
                    <select wire:model.live="selectedBranchId" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        <option value="">{{ __('All Branches') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Module') }}</label>
                <select wire:model.live="selectedModuleId" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    <option value="">{{ __('All Modules') }}</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->id }}">{{ $module->localized_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Report Type') }}</label>
                <select wire:model.live="selectedReport" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    <option value="">{{ __('Select Report') }}</option>
                    @foreach($reports as $report)
                        <option value="{{ $report->report_key }}">{{ $report->localized_name }}</option>
                    @endforeach
                    <option value="sales">{{ __('Sales Report') }}</option>
                    <option value="purchases">{{ __('Purchases Report') }}</option>
                    <option value="expenses">{{ __('Expenses Report') }}</option>
                    <option value="income">{{ __('Income Report') }}</option>
                    <option value="customers">{{ __('Customers Report') }}</option>
                    <option value="suppliers">{{ __('Suppliers Report') }}</option>
                    <option value="inventory">{{ __('Inventory Report') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('From Date') }}</label>
                <input type="date" wire:model="dateFrom" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('To Date') }}</label>
                <input type="date" wire:model="dateTo" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button type="button" wire:click="generateReport" class="px-6 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('Generate Report') }}
            </button>

            @if(!empty($reportData))
                <button type="button" wire:click="openExportModal" class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ __('Export') }}
                </button>
            @endif
        </div>
    </div>

    @if(!empty($summary))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach($summary as $key => $value)
                @if(!is_array($value))
                    <div class="bg-white rounded-xl shadow p-4">
                        <p class="text-sm text-gray-500">{{ __(ucwords(str_replace('_', ' ', $key))) }}</p>
                        <p class="text-2xl font-bold text-gray-800">
                            @if(str_contains($key, 'amount') || str_contains($key, 'total') || str_contains($key, 'value') || str_contains($key, 'cost'))
                                {{ number_format($value, 2) }}
                            @else
                                {{ $value }}
                            @endif
                        </p>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if(!empty($reportData))
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <p class="text-sm text-gray-600">{{ __('Showing :count records', ['count' => count($reportData)]) }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(!empty($reportData[0]))
                                @foreach(array_keys((array)$reportData[0]) as $column)
                                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                        {{ __(ucwords(str_replace('_', ' ', $column))) }}
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reportData as $row)
                            <tr class="hover:bg-gray-50">
                                @foreach((array)$row as $value)
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        @if(is_numeric($value) && (strpos((string)$value, '.') !== false))
                                            {{ number_format($value, 2) }}
                                        @else
                                            {{ $value ?? '-' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($selectedReport)
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500">{{ __('Click "Generate Report" to view results') }}</p>
        </div>
    @endif

    @if($showExportModal)
        <div class="z-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">{{ __('Export Settings') }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('Choose columns and format for export') }}</p>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Export Format') }}</label>
                        <select wire:model="exportFormat" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                            <option value="xlsx">{{ __('Excel (.xlsx)') }}</option>
                            <option value="csv">{{ __('CSV (.csv)') }}</option>
                            <option value="pdf">{{ __('PDF (.pdf)') }}</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Select Columns') }}</label>
                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto border border-gray-200 rounded-xl p-3">
                            @foreach($availableColumns as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 rounded-lg">
                                    <input type="checkbox" wire:model="selectedColumns" value="{{ $key }}" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                                    <span class="text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" wire:click="closeExportModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="export" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                        {{ __('Download') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
