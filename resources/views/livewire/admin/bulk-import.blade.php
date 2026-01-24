<div class="lw-component">
    @section('page-header')
        <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Bulk Import') }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('Import data from Excel, CSV files, or Google Sheets') }}</p>
    @endsection

    <div class="max-w-4xl mx-auto">
        {{-- Entity Type Selection --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm mb-6">
            <div class="p-6">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    {{ __('Select Data Type to Import') }}
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($this->entities as $key => $entity)
                        <button
                            wire:click="$set('entityType', '{{ $key }}')"
                            class="p-4 rounded-xl border-2 transition-all text-start {{ $entityType === $key ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-emerald-300' }}"
                        >
                            <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $entity['name'] }}</span>
                            @if(isset($entity['supports_modules']) && $entity['supports_modules'])
                                <span class="block text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ __('Module-aware') }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Module Selection for Products --}}
                @if($entityType === 'products' && count($this->modules) > 0)
                <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        {{ __('Select Module (Optional)') }}
                        <span class="text-slate-500 text-xs">{{ __('- Choose a module to include its custom fields in the import') }}</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <button
                            wire:click="$set('selectedModuleId', null)"
                            class="p-3 rounded-xl border-2 transition-all text-start {{ $selectedModuleId === null ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-blue-300' }}"
                        >
                            <span class="text-2xl">📦</span>
                            <span class="block text-sm font-medium text-slate-900 dark:text-slate-100 mt-1">{{ __('General Products') }}</span>
                            <span class="block text-xs text-slate-500">{{ __('No custom fields') }}</span>
                        </button>
                        @foreach($this->modules as $module)
                            <button
                                wire:click="$set('selectedModuleId', {{ $module['id'] }})"
                                class="p-3 rounded-xl border-2 transition-all text-start {{ $selectedModuleId === $module['id'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-blue-300' }}"
                            >
                                <span class="text-2xl">{{ $module['icon'] ?? '📦' }}</span>
                                <span class="block text-sm font-medium text-slate-900 dark:text-slate-100 mt-1">
                                    {{ app()->getLocale() === 'ar' && !empty($module['name_ar']) ? $module['name_ar'] : $module['name'] }}
                                </span>
                                <span class="block text-xs text-slate-500">{{ __('With custom fields') }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($entityType)
        {{-- Step Progress --}}
        <div class="flex items-center justify-center gap-4 mb-6">
            @foreach([1 => __('Upload'), 2 => __('Preview'), 3 => __('Import')] as $step => $label)
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm
                        {{ $currentStep >= $step ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">
                        @if($currentStep > $step)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $step }}
                        @endif
                    </div>
                    <span class="text-sm {{ $currentStep >= $step ? 'text-slate-900 dark:text-slate-100 font-medium' : 'text-slate-500 dark:text-slate-400' }}">
                        {{ $label }}
                    </span>
                </div>
                @if($step < 3)
                    <div class="w-12 h-0.5 {{ $currentStep > $step ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                @endif
            @endforeach
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm">
            @if($currentStep === 1)
            {{-- Step 1: Upload --}}
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Import Source') }}</h3>
                    <button wire:click="downloadTemplate" class="text-sm text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        {{ __('Download Template') }}
                    </button>
                </div>

                {{-- Import Source Selection --}}
                <div class="flex gap-4 p-1 bg-slate-100 dark:bg-slate-700 rounded-lg">
                    <button 
                        wire:click="$set('importSource', 'file')"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition {{ $importSource === 'file' ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-slate-100 shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}"
                    >
                        <svg class="w-5 h-5 inline-block me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('Upload File') }}
                    </button>
                    <button 
                        wire:click="$set('importSource', 'google_sheet')"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition {{ $importSource === 'google_sheet' ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-slate-100 shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}"
                    >
                        <svg class="w-5 h-5 inline-block me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        {{ __('Google Sheets') }}
                    </button>
                </div>

                @if($importSource === 'file')
                {{-- File Upload --}}
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-8 text-center">
                    <input type="file" wire:model="importFile" accept=".csv,.xlsx,.xls" class="hidden" id="import-file-input">
                    <label for="import-file-input" class="cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                            {{ __('Click to upload or drag and drop') }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ __('CSV, XLSX or XLS (MAX. 10MB)') }}
                        </p>
                    </label>
                </div>

                @if($importFile)
                <div class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm text-emerald-700 dark:text-emerald-300">{{ $importFile->getClientOriginalName() }}</span>
                </div>
                @endif
                @else
                {{-- Google Sheets URL Input --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Google Sheets URL') }}</label>
                        <div class="flex gap-2">
                            <input 
                                type="url" 
                                wire:model="googleSheetUrl" 
                                placeholder="https://docs.google.com/spreadsheets/d/..."
                                class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <button 
                                wire:click="loadGoogleSheet" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition flex items-center gap-2"
                            >
                                <svg wire:loading wire:target="loadGoogleSheet" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('Load Sheet') }}
                            </button>
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>{{ __('Note:') }}</strong> {{ __('The Google Sheet must be shared with "Anyone with the link" to allow import.') }}
                        </p>
                    </div>
                </div>

                @if(!empty($previewData))
                <div class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('Google Sheet loaded successfully') }}</span>
                </div>
                @endif
                @endif

                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="hasHeaders" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('First row contains headers') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="updateExisting" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Update existing records (match by unique fields)') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="skipDuplicates" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Skip duplicate records') }}</span>
                    </label>
                </div>

                @php
                    $entityConfig = $this->entities[$entityType] ?? [];
                @endphp
                @if(!empty($entityConfig['required_columns']))
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">{{ __('Required columns:') }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">{{ implode(', ', $entityConfig['required_columns']) }}</p>
                    @if(!empty($entityConfig['optional_columns']))
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mt-3 mb-2">{{ __('Optional columns:') }}</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">{{ implode(', ', $entityConfig['optional_columns']) }}</p>
                    @endif
                </div>
                @endif
            </div>

            @elseif($currentStep === 2)
            {{-- Step 2: Preview --}}
            <div class="p-6 space-y-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Preview Data') }}</h3>
                
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        {{ __('Showing first 10 rows. Please verify the data before proceeding.') }}
                    </p>
                </div>

                @if(!empty($previewData))
                <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-lg">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">#</th>
                                @foreach($previewData[0] ?? [] as $index => $cell)
                                    <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">
                                        {{ $columnMapping[$index] ?? __('Column') . ' ' . ($index + 1) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($previewData as $rowIndex => $row)
                            <tr>
                                <td class="px-4 py-2 text-sm text-slate-500">{{ $rowIndex + 1 }}</td>
                                @foreach($row as $cell)
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-slate-100">{{ $cell }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-slate-500">
                    {{ __('No preview data available') }}
                </div>
                @endif
            </div>

            @elseif($currentStep === 3)
            {{-- Step 3: Import --}}
            <div class="p-6 space-y-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Confirm Import') }}</h3>

                @if(empty($importResult))
                <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-6 text-center">
                    <svg class="mx-auto h-16 w-16 text-emerald-600 dark:text-emerald-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h4 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">
                        {{ __('Ready to Import') }}
                    </h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Click the button below to start importing your data.') }}
                    </p>
                </div>
                @else
                {{-- Import Results --}}
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-emerald-600">{{ $importResult['imported'] ?? 0 }}</span>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('Imported') }}</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-red-600">{{ $importResult['failed'] ?? 0 }}</span>
                            <p class="text-sm text-red-700 dark:text-red-300">{{ __('Failed') }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-slate-600 dark:text-slate-400">
                                {{ ($importResult['imported'] ?? 0) + ($importResult['failed'] ?? 0) }}
                            </span>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Total') }}</p>
                        </div>
                    </div>

                    @if(!empty($importResult['errors']))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 max-h-60 overflow-y-auto">
                        <h5 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">{{ __('Errors') }}:</h5>
                        <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                            @foreach($importResult['errors'] as $error)
                                <li>{{ __('Row') }} {{ $error['row'] }}: {{ implode(', ', $error['errors']) }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

            {{-- Footer Actions --}}
            <div class="border-t border-slate-200 dark:border-slate-700 px-6 py-4 flex justify-between">
                @if($currentStep > 1)
                <button wire:click="previousStep" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    {{ __('Back') }}
                </button>
                @else
                <div></div>
                @endif

                @if($currentStep < 3)
                <button wire:click="nextStep" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                    {{ __('Next') }}
                </button>
                @elseif(empty($importResult))
                <button wire:click="runImport" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition flex items-center gap-2" wire:loading.attr="disabled">
                    <svg wire:loading wire:target="runImport" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Start Import') }}
                </button>
                @else
                <a href="{{ route('admin.bulk-import') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                    {{ __('Import More') }}
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
