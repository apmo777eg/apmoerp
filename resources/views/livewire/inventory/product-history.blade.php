<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-2xl">📜</span>
                {{ __('Product History') }}
            </h1>
            @if($product)
                <p class="text-sm text-slate-500">{{ __('Transaction history for') }}: <span class="font-semibold">{{ $product->name }}</span></p>
            @endif
        </div>
        <a href="{{ route('app.inventory.products.index') }}" class="erp-btn-secondary">
            <svg class="w-5 h-5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('Back to Products') }}
        </a>
    </div>
    @if($product)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="erp-card p-4 bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                <p class="text-sm text-blue-600 mb-1">{{ __('Product') }}</p>
                <p class="text-lg font-bold text-blue-800">{{ $product->name }}</p>
                <p class="text-xs text-blue-600 mt-1">{{ __('SKU') }}: {{ $product->sku ?: '-' }}</p>
            </div>
            <div class="erp-card p-4 bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
                <p class="text-sm text-emerald-600 mb-1">{{ __('Current Stock') }}</p>
                <p class="text-2xl font-bold text-emerald-800">{{ number_format($currentStock, 2) }}</p>
                <p class="text-xs text-emerald-600 mt-1">{{ $product->uom ?: __('Units') }}</p>
            </div>
            <div class="erp-card p-4 bg-gradient-to-br from-amber-50 to-amber-100 border-amber-200">
                <p class="text-sm text-amber-600 mb-1">{{ __('Price') }}</p>
                <p class="text-2xl font-bold text-amber-800">{{ number_format($product->default_price ?? 0, 2) }}</p>
            </div>
            <div class="erp-card p-4 bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
                <p class="text-sm text-purple-600 mb-1">{{ __('Last Updated') }}</p>
                <p class="text-lg font-bold text-purple-800">{{ $product->updated_at->format('M d, Y') }}</p>
                <p class="text-xs text-purple-600 mt-1">{{ $product->updated_at->format('H:i') }}</p>
            </div>
        </div>

        <div class="erp-card p-4">
            <div class="flex flex-wrap items-center gap-4 mb-4">
                <div>
                    <label class="erp-label">{{ __('Activity Type') }}</label>
                    <select wire:model.live="filterType" class="erp-input mt-1">
                        @foreach($movementTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">{{ __('From Date') }}</label>
                    <input type="date" wire:model.live="dateFrom" class="erp-input mt-1">
                </div>
                <div>
                    <label class="erp-label">{{ __('To Date') }}</label>
                    <input type="date" wire:model.live="dateTo" class="erp-input mt-1">
                </div>
                <div class="flex items-end">
                    <button wire:click="clearFilters" class="erp-btn-secondary mt-1">
                        {{ __('Clear Filters') }}
                    </button>
                </div>
            </div>
        </div>

        @if($filterType !== 'audit' && $stockMovements && $stockMovements->count() > 0)
            <div class="erp-card">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                    <h3 class="font-semibold text-slate-800">{{ __('Stock Movements') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Direction') }}</th>
                                <th class="text-right">{{ __('Quantity') }}</th>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockMovements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        {{ $movement->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ match($movement->type) {
                                                'sale' => 'bg-red-100 text-red-800',
                                                'purchase' => 'bg-green-100 text-green-800',
                                                'transfer' => 'bg-blue-100 text-blue-800',
                                                'adjustment' => 'bg-amber-100 text-amber-800',
                                                'return' => 'bg-purple-100 text-purple-800',
                                                default => 'bg-slate-100 text-slate-800'
                                            } }}">
                                            {{ ucfirst($movement->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($movement->direction === 'in')
                                            <span class="text-emerald-600 font-medium">↑ {{ __('In') }}</span>
                                        @else
                                            <span class="text-red-600 font-medium">↓ {{ __('Out') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-mono {{ $movement->direction === 'in' ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $movement->direction === 'in' ? '+' : '-' }}{{ number_format($movement->qty, 2) }}
                                    </td>
                                    <td class="text-slate-600">{{ $movement->reference ?: '-' }}</td>
                                    <td>{{ $movement->user?->name ?: '-' }}</td>
                                    <td class="text-slate-500 text-sm max-w-xs truncate">{{ $movement->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t">
                    {{ $stockMovements->links() }}
                </div>
            </div>
        @endif

        @if(($filterType === 'all' || $filterType === 'audit') && $auditLogs && $auditLogs->count() > 0)
            <div class="erp-card">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                    <h3 class="font-semibold text-slate-800">{{ __('Audit Logs') }}</h3>
                </div>
                <div class="divide-y divide-slate-200">
                    @foreach($auditLogs as $log)
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        {{ match($log->event) {
                                            'created' => 'bg-emerald-100 text-emerald-800',
                                            'updated' => 'bg-blue-100 text-blue-800',
                                            'deleted' => 'bg-red-100 text-red-800',
                                            default => 'bg-slate-100 text-slate-800'
                                        } }}">
                                        {{ ucfirst($log->event) }}
                                    </span>
                                    <span class="ltr:ml-2 rtl:mr-2 text-sm text-slate-600">
                                        {{ __('by') }} <span class="font-medium">{{ $log->user?->name ?: __('System') }}</span>
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500">{{ $log->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            @if($log->old_values || $log->new_values)
                                <div class="mt-2 text-sm text-slate-600">
                                    @if($log->old_values)
                                        <div class="bg-red-50 p-2 rounded mb-1">
                                            <span class="text-red-600 font-medium">{{ __('Old') }}:</span>
                                            <span class="font-mono text-xs">{{ json_encode($log->old_values, JSON_UNESCAPED_UNICODE) }}</span>
                                        </div>
                                    @endif
                                    @if($log->new_values)
                                        <div class="bg-emerald-50 p-2 rounded">
                                            <span class="text-emerald-600 font-medium">{{ __('New') }}:</span>
                                            <span class="font-mono text-xs">{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if((!$stockMovements || $stockMovements->count() === 0) && (!$auditLogs || $auditLogs->count() === 0))
            <div class="erp-card p-8 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-slate-500 text-lg">{{ __('No activity found for this product') }}</p>
                <p class="text-slate-400 text-sm mt-1">{{ __('Transactions will appear here once the product is used in sales, purchases, or adjustments.') }}</p>
            </div>
        @endif
    @else
        <div class="erp-card p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-slate-500 text-lg">{{ __('Product not found') }}</p>
            <a href="{{ route('app.inventory.products.index') }}" class="mt-4 inline-flex erp-btn-primary">
                {{ __('Go to Products') }}
            </a>
        </div>
    @endif
</div>
