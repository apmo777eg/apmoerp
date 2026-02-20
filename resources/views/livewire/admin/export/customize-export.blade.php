<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Export Settings') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('Customize and save your export layouts') }}</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">
            {{ __('Back to Reports') }}
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-100 border border-emerald-300 text-emerald-700 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Saved Layouts') }}</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Data Type') }}</label>
                <select wire:model.live="entityType" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    @foreach($entityTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                @forelse($savedLayouts as $layout)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer" wire:click="loadLayout({{ $layout->id }})">
                        <div>
                            <p class="font-medium text-gray-800">{{ $layout->layout_name }}</p>
                            <p class="text-xs text-gray-500">{{ count($layout->selected_columns) }} {{ __('columns') }} - {{ strtoupper($layout->export_format) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($layout->is_default)
                                <span class="px-2 py-1 text-xs bg-emerald-100 text-emerald-800 rounded-full">{{ __('Default') }}</span>
                            @endif
                            <button type="button" wire:click.stop="deleteLayout({{ $layout->id }})" class="text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm py-4 text-center">{{ __('No saved layouts for this type') }}</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Configure Layout') }}</h2>

            <form wire:submit="saveLayout" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Layout Name') }} *</label>
                        <input type="text" wire:model="layoutName" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500" placeholder="{{ __('My Export Layout') }}">
                        @error('layoutName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Export Format') }}</label>
                        <select wire:model="exportFormat" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                            <option value="xlsx">{{ __('Excel (.xlsx)') }}</option>
                            <option value="csv">{{ __('CSV (.csv)') }}</option>
                            <option value="pdf">{{ __('PDF (.pdf)') }}</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Date Format') }}</label>
                        <select wire:model="dateFormat" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                            @foreach($dateFormats as $format => $example)
                                <option value="{{ $format }}">{{ $example }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex items-end gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="includeHeaders" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                            <span class="text-sm text-gray-700">{{ __('Include Headers') }}</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="isDefault" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                            <span class="text-sm text-gray-700">{{ __('Default') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Select Columns') }} *</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto border border-gray-200 rounded-xl p-3">
                        @foreach($availableColumns as $key => $label)
                            <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 rounded-lg">
                                <input type="checkbox" wire:model.live="selectedColumns" value="{{ $key }}" class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedColumns') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Column Order') }}</label>
                    <div class="space-y-1 max-h-40 overflow-y-auto border border-gray-200 rounded-xl p-3">
                        @foreach($columnOrder as $column)
                            @if(in_array($column, $selectedColumns))
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                    <span class="text-sm">{{ $availableColumns[$column] ?? $column }}</span>
                                    <div class="flex gap-1">
                                        <button type="button" wire:click="moveColumnUp(@js($column))" class="p-1 hover:bg-gray-200 rounded">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        </button>
                                        <button type="button" wire:click="moveColumnDown(@js($column))" class="p-1 hover:bg-gray-200 rounded">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition">
                        {{ __('Save Layout') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
