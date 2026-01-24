<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-2xl">🏷️</span>
                {{ __('Print Barcodes/QR Codes') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Generate and print barcode labels for products') }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="erp-card p-4">
                <h3 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    {{ __('Search Products') }}
                </h3>
                <div class="relative mb-4">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="{{ __('Search by name, SKU, or barcode...') }}"
                           class="erp-input ltr:pl-10 rtl:pr-10 w-full">
                    <svg class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                @if($search)
                    <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl">
                        @forelse($products as $product)
                            <div class="flex items-center justify-between p-3 hover:bg-slate-50 border-b last:border-b-0 {{ in_array($product->id, $selectedProducts) ? 'bg-emerald-50' : '' }}">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                    <p class="text-sm text-slate-500">
                                        {{ __('SKU') }}: {{ $product->sku ?: '-' }} | 
                                        {{ __('Barcode') }}: {{ $product->barcode ?: '-' }}
                                    </p>
                                </div>
                                @if(!in_array($product->id, $selectedProducts))
                                    <button wire:click="addProduct({{ $product->id }})" 
                                            class="erp-btn-primary text-sm px-3 py-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        {{ __('Add') }}
                                    </button>
                                @else
                                    <span class="text-emerald-600 text-sm font-medium">{{ __('Added') }} ✓</span>
                                @endif
                            </div>
                        @empty
                            <div class="p-4 text-center text-slate-500">{{ __('No products found') }}</div>
                        @endforelse
                    </div>
                @endif
            </div>

            @if(count($selectedProducts) > 0)
                <div class="erp-card p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            {{ __('Selected Products') }} ({{ count($selectedProducts) }})
                        </h3>
                        <button wire:click="clearAll" class="text-sm text-red-600 hover:text-red-700">
                            {{ __('Clear All') }}
                        </button>
                    </div>

                    <div class="space-y-2">
                        @foreach($selectedProductDetails as $product)
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                                <div class="flex-1">
                                    <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $product->barcode ?: $product->sku }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-slate-600">{{ __('Qty') }}:</label>
                                        <input type="number" 
                                               value="{{ $printQuantities[$product->id] ?? 1 }}"
                                               wire:change="updateQuantity({{ $product->id }}, $event.target.value)"
                                               min="1" max="100"
                                               class="erp-input w-20 text-center">
                                    </div>
                                    <button wire:click="removeProduct({{ $product->id }})" 
                                            class="text-red-500 hover:text-red-700 p-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t flex items-center justify-between">
                        <p class="text-sm text-slate-600">
                            {{ __('Total Labels') }}: <span class="font-bold text-emerald-600">{{ $this->totalLabels }}</span>
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="erp-card p-4">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ __('Label Settings') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="erp-label">{{ __('Code Type') }}</label>
                        <select wire:model="barcodeType" class="erp-input mt-1">
                            <option value="barcode">{{ __('Barcode (1D)') }}</option>
                            <option value="qrcode">{{ __('QR Code (2D)') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="erp-label">{{ __('Label Size') }}</label>
                        <select wire:model="labelSize" class="erp-input mt-1">
                            <option value="small">{{ __('Small (30x20mm)') }}</option>
                            <option value="medium">{{ __('Medium (50x30mm)') }}</option>
                            <option value="large">{{ __('Large (70x40mm)') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="erp-label">{{ __('Include on Label') }}</label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="showName" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-slate-700">{{ __('Product Name') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="showSku" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-slate-700">{{ __('SKU') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="showPrice" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-slate-700">{{ __('Price') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="erp-card p-4">
                <button wire:click="togglePreview" 
                        class="w-full erp-btn-secondary mb-3"
                        @if(count($selectedProducts) === 0) disabled @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ __('Preview Labels') }}
                </button>

                <button onclick="window.print()" 
                        class="w-full erp-btn-primary"
                        @if(count($selectedProducts) === 0) disabled @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    {{ __('Print Labels') }}
                </button>
            </div>
        </div>
    </div>

    @if($showPreview && count($selectedProducts) > 0)
        <div class="z-modal fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="togglePreview">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('Label Preview') }}</h3>
                    <button wire:click="togglePreview" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div class="grid grid-cols-3 gap-4">
                        @foreach($selectedProductDetails as $product)
                            @for($i = 0; $i < ($printQuantities[$product->id] ?? 1); $i++)
                                <div class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center {{ $labelSize === 'small' ? 'text-xs' : ($labelSize === 'large' ? 'text-base' : 'text-sm') }}">
                                    @if($showName)
                                        <p class="font-bold truncate mb-1">{{ $product->name }}</p>
                                    @endif
                                    <div class="my-2 flex justify-center">
                                        @if($barcodeType === 'barcode')
                                            <div class="bg-slate-100 px-4 py-2 rounded font-mono text-xs">
                                                ||||| {{ $product->barcode ?: $product->sku ?: 'NO-CODE' }} |||||
                                            </div>
                                        @else
                                            <div class="w-16 h-16 bg-slate-100 rounded flex items-center justify-center">
                                                <span class="text-xs text-slate-400">[QR]</span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($showSku)
                                        <p class="text-slate-600">{{ $product->sku ?: '-' }}</p>
                                    @endif
                                    @if($showPrice)
                                        <p class="font-bold text-emerald-600">{{ number_format($product->default_price ?? 0, 2) }}</p>
                                    @endif
                                </div>
                            @endfor
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
