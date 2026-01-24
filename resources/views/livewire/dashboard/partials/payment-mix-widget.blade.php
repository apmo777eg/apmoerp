<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
@php
    $totalPayments = array_sum($paymentMethodsData['data'] ?? []);
    $totalPaymentAmount = array_sum($paymentMethodsData['totals'] ?? []);
@endphp

    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ __('Payment mix (This month)') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Total') }}: {{ number_format($totalPaymentAmount ?? 0, 2) }} {{ __('EGP') }}</span>
    </div>
    @if($totalPayments > 0)
        <div class="space-y-4">
            @foreach($paymentMethodsData['labels'] ?? [] as $index => $method)
                @php
                    $methodCount = $paymentMethodsData['data'][$index] ?? 0;
                    $methodTotal = $paymentMethodsData['totals'][$index] ?? 0;
                    $percent = $totalPayments > 0 ? round(($methodCount / $totalPayments) * 100) : 0;
                @endphp
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm font-medium text-slate-700 dark:text-slate-300">
                        <span>{{ $method }}</span>
                        <span class="text-slate-500 dark:text-slate-400">{{ $percent }}% · {{ $methodCount }} {{ __('payments') }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Amount') }}: {{ number_format($methodTotal, 2) }} {{ __('EGP') }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No payments recorded for this period.') }}</p>
    @endif
</div>