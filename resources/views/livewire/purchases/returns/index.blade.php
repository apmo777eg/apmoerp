<div class="lw-component">
    @section('page-title', __('Purchase Returns'))

    <div class="erp-page-container">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white">{{ __('Purchase Returns') }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Manage returns to suppliers') }}</p>
            </div>
            @can('purchases.return')
                <button type="button" wire:click="openReturnModal()" class="erp-btn-primary">
                    <span class="mr-1">↩️</span> {{ __('New Return') }}
                </button>
            @endcan
        </div>

        <div class="erp-card mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="erp-input w-full" placeholder="{{ __('Search by reference number...') }}">
                </div>
                <div class="w-full md:w-48">
                    <input type="date" wire:model.live="dateFrom" class="erp-input w-full" placeholder="{{ __('From Date') }}">
                </div>
                <div class="w-full md:w-48">
                    <input type="date" wire:model.live="dateTo" class="erp-input w-full" placeholder="{{ __('To Date') }}">
                </div>
            </div>
        </div>

        <div class="erp-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Purchase Ref') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td class="font-mono text-sm">#{{ $return->id }}</td>
                                <td>
                                    @if($return->purchase)
                                        <a href="{{ route('app.purchases.edit', $return->purchase_id) }}" class="text-emerald-600 hover:text-emerald-800 hover:underline">
                                            {{ $return->purchase->reference_no ?: '#' . $return->purchase_id }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td>{{ $return->purchase?->supplier?->name ?? '-' }}</td>
                                <td class="max-w-xs truncate">{{ $return->reason ?? '-' }}</td>
                                {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                                <td class="font-mono text-orange-600">{{ number_format(decimal_float($return->total), 2) }}</td>
                                <td class="text-sm text-slate-500">{{ $return->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-center">
                                    @can('purchases.return')
                                        <button type="button" wire:click="deleteReturn({{ $return->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this return note?') }}"
                                                class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-500">
                                    {{ __('No purchase returns found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>

    @if($showReturnModal)
        <div class="z-modal fixed inset-0 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeReturnModal">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ __('Process Purchase Return') }}</h3>
                    <button type="button" wire:click="closeReturnModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="p-4 overflow-y-auto max-h-[60vh]">
                    @if(!$selectedPurchase)
                        <div class="mb-4">
                            <label class="erp-label">{{ __('Select Purchase') }}</label>
                            <select wire:model="selectedPurchaseId" wire:change="loadPurchase" class="erp-select w-full">
                                <option value="">{{ __('Choose a purchase...') }}</option>
                                @foreach($purchases as $purchase)
                                    <option value="{{ $purchase->id }}">
                                        {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                                        {{ $purchase->reference_no ?: '#' . $purchase->id }} - 
                                        {{ $purchase->supplier?->name ?? __('Unknown Supplier') }} - 
                                        {{ number_format(decimal_float($purchase->grand_total), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="mb-4 p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                            <p class="text-sm"><strong>{{ __('Reference') }}:</strong> {{ $selectedPurchase->reference_no ?: '#' . $selectedPurchase->id }}</p>
                            <p class="text-sm"><strong>{{ __('Supplier') }}:</strong> {{ $selectedPurchase->supplier?->name ?? __('Unknown Supplier') }}</p>
                            {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                            <p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format(decimal_float($selectedPurchase->grand_total), 2) }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="erp-label">{{ __('Items to Return') }}</label>
                            <div class="space-y-2">
                                @foreach($returnItems as $index => $item)
                                    <div class="flex items-center gap-3 p-2 border border-slate-200 dark:border-slate-600 rounded-lg">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $item['product_name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ __('Max') }}: {{ $item['max_qty'] }} × {{ number_format($item['cost'], 2) }}</p>
                                        </div>
                                        <div class="w-24">
                                            <input type="number" wire:model="returnItems.{{ $index }}.qty"
                                                   min="0" max="{{ $item['max_qty'] }}" step="0.01"
                                                   class="erp-input w-full text-sm text-center" placeholder="0">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="erp-label">{{ __('Return Reason') }}</label>
                            <textarea wire:model="returnReason" rows="2" class="erp-input w-full"
                                      placeholder="{{ __('Enter reason for return...') }}"></textarea>
                        </div>
                    @endif
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2">
                    <button type="button" wire:click="closeReturnModal" class="erp-btn-secondary">{{ __('Cancel') }}</button>
                    @if($selectedPurchase)
                        <button type="button" wire:click="processReturn" class="erp-btn-primary">
                            <span wire:loading.remove wire:target="processReturn">↩️ {{ __('Process Return') }}</span>
                            <span wire:loading wire:target="processReturn">{{ __('Processing...') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
