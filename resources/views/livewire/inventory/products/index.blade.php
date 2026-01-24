<div class="space-y-4">
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

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ __('Products') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Inventory products for the current branch.') }}
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-56 relative">
                <input type="search"
                       wire:model.live.debounce.300ms="search"
                       placeholder="{{ __('Search (name, SKU, barcode)...') }}"
                       class="erp-input rounded-full pr-10">
                {{-- Search loading indicator --}}
                <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <select wire:model.live="status" class="erp-input text-xs">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                    <div wire:loading.delay wire:target="status" class="absolute right-6 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-3 w-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <div class="relative">
                    <select wire:model.live="type" class="erp-input text-xs">
                        <option value="">{{ __('All types') }}</option>
                        <option value="stock">{{ __('Stock') }}</option>
                        <option value="service">{{ __('Service') }}</option>
                    </select>
                    <div wire:loading.delay wire:target="type" class="absolute right-6 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-3 w-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <div class="relative">
                    <select wire:model.live="moduleId" class="erp-input text-xs">
                        <option value="">{{ __('All modules') }}</option>
                        @foreach($dataModules as $module)
                            <option value="{{ $module->id }}">{{ $module->icon }} {{ app()->getLocale() === 'ar' ? $module->name_ar : $module->name }}</option>
                        @endforeach
                    </select>
                    <div wire:loading.delay wire:target="moduleId" class="absolute right-6 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-3 w-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <button type="button" wire:click="openExportModal"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ __('Export') }}
                </button>
                <a href="{{ route('app.inventory.products.create') }}"
                   class="erp-btn-primary text-xs px-3 py-2">
                    {{ __('Add product') }}
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">#</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Name') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Module') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('SKU') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Barcode') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Price') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Cost') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Type') }}</th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">{{ __('Status') }}</th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr class="hover:bg-emerald-50/40">
                        <td class="px-3 py-2 text-xs text-slate-500">
                            {{ $product->id }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-800">
                            {{ $product->name }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            @if($product->module)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[0.7rem] font-medium text-slate-700">
                                    {{ $product->module->icon ?? '📦' }}
                                    {{ app()->getLocale() === 'ar' ? ($product->module->name_ar ?? $product->module->name) : $product->module->name }}
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $product->sku }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $product->barcode }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-800">
                            {{ number_format($product->default_price ?? 0, 2) }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-800">
                            {{ number_format($product->cost ?? 0, 2) }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            @if(($product->type ?? 'stock') === 'service')
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[0.7rem] font-medium text-sky-700">
                                    {{ __('Service') }}
                                </span>
                            @else
                                <span class="erp-badge">
                                    {{ __('Stock') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            @if(($product->status ?? 'active') === 'active')
                                <span class="erp-badge">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[0.7rem] font-medium text-slate-600">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-end">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('app.inventory.products.store-mappings', $product->id) }}"
                                   class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-[0.7rem] font-semibold text-blue-700 hover:bg-blue-100"
                                   title="{{ __('Store Mappings') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </a>
                                <a href="{{ route('app.inventory.products.edit', $product->id) }}"
                                   class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-[0.7rem] font-semibold text-emerald-700 hover:bg-emerald-100">
                                    {{ __('Edit') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-3 py-4 text-center text-xs text-slate-500">
                            {{ __('No products found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $products->links() }}
    </div>

    @if($showExportModal)
        <x-export-modal 
            :exportColumns="$exportColumns"
            :selectedExportColumns="$selectedExportColumns"
            :exportFormat="$exportFormat"
            :exportDateFormat="$exportDateFormat"
            :exportIncludeHeaders="$exportIncludeHeaders"
            :exportRespectFilters="$exportRespectFilters"
            :exportIncludeTotals="$exportIncludeTotals"
            :exportMaxRows="$exportMaxRows"
            :exportUseBackgroundJob="$exportUseBackgroundJob"
        />
    @endif
</div>
