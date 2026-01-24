<div class="lw-component">
    @if($receiptData)
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg max-w-sm mx-auto p-4">
            {{-- Receipt Header --}}
            <div class="text-center border-b border-dashed border-slate-300 dark:border-slate-600 pb-4 mb-4">
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $receiptData['branch'] }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Receipt') }} #{{ $receiptData['receipt_number'] }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $receiptData['date'] }}</p>
            </div>

            {{-- Customer --}}
            <div class="mb-4 text-sm">
                <span class="text-slate-500 dark:text-slate-400">{{ __('Customer') }}:</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $receiptData['customer'] }}</span>
            </div>

            {{-- Items --}}
            <div class="border-b border-dashed border-slate-300 dark:border-slate-600 pb-4 mb-4">
                @foreach($receiptData['items'] as $item)
                    <div class="flex justify-between text-sm py-1">
                        <div class="flex-1">
                            <span class="text-slate-800 dark:text-slate-200">{{ $item['name'] }}</span>
                            <span class="text-slate-500 dark:text-slate-400 text-xs ml-1">x{{ $item['qty'] }}</span>
                        </div>
                        <span class="text-slate-800 dark:text-slate-200">{{ number_format($item['total'], 2) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Totals --}}
            <div class="space-y-1 text-sm border-b border-dashed border-slate-300 dark:border-slate-600 pb-4 mb-4">
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}</span>
                    <span class="text-slate-800 dark:text-slate-200">{{ number_format($receiptData['subtotal'] ?? 0, 2) }}</span>
                </div>
                @if(($receiptData['discount'] ?? 0) > 0)
                    <div class="flex justify-between text-red-600">
                        <span>{{ __('Discount') }}</span>
                        <span>-{{ number_format($receiptData['discount'], 2) }}</span>
                    </div>
                @endif
                @if(($receiptData['tax'] ?? 0) > 0)
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Tax') }}</span>
                        <span class="text-slate-800 dark:text-slate-200">{{ number_format($receiptData['tax'], 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold text-lg pt-2">
                    <span class="text-slate-900 dark:text-slate-100">{{ __('Total') }}</span>
                    <span class="text-emerald-600">{{ number_format($receiptData['total'] ?? 0, 2) }}</span>
                </div>
            </div>

            {{-- Payments --}}
            @if(count($receiptData['payments'] ?? []) > 0)
                <div class="mb-4">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">{{ __('Payment Method') }}</p>
                    @foreach($receiptData['payments'] as $payment)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-300">{{ $payment['method'] }}</span>
                            <span class="text-slate-800 dark:text-slate-200">{{ number_format($payment['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex gap-2 mt-4">
                <button wire:click="print" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg">
                    {{ __('Print') }}
                </button>
                <button wire:click="close" class="flex-1 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    @else
        <div class="text-center py-8 px-4">
            <div class="text-4xl mb-2">🧾</div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No receipt to preview') }}</p>
        </div>
    @endif
</div>
