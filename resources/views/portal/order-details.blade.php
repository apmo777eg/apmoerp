@extends('layouts.guest')

@section('title', __('Order Details'))

@section('content')
@include('portal.partials.nav')

<div class="max-w-5xl mx-auto p-4">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ __('Order Details') }}</h2>
            <div class="text-sm text-slate-600 dark:text-slate-300">
                {{ __('Reference: :ref', ['ref' => $order->reference_number ?? $order->reference_number ?? $order->id]) }}
            </div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('portal.orders.invoice', $order->id) }}" class="erp-btn-primary" target="_blank" rel="noopener">
                {{ __('Download Invoice') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Status') }}</div>
            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ ucfirst($order->status ?? __('unknown')) }}</div>
        </div>
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Order Date') }}</div>
            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ optional($order->sale_date)->format('Y-m-d') ?? optional($order->created_at)->format('Y-m-d') }}</div>
        </div>
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Total') }}</div>
            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float)($order->total_amount ?? 0), 2) }}</div>
        </div>
    </div>

    <div class="erp-card p-4 mb-6">
        <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">{{ __('Items') }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-600 dark:text-slate-300">
                        <th class="py-2">{{ __('Item') }}</th>
                        <th class="py-2">{{ __('Qty') }}</th>
                        <th class="py-2">{{ __('Unit Price') }}</th>
                        <th class="py-2">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2 text-slate-900 dark:text-slate-100">{{ $item->product_name ?? optional($item->product)->name ?? __('Item') }}</td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ $item->qty ?? $item->quantity ?? 0 }}</td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ number_format((float)($item->unit_price ?? 0), 2) }}</td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ number_format((float)($item->line_total ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="erp-card p-4">
        <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">{{ __('Payments') }}</h3>

        @if ($payments->isEmpty())
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('No payments recorded.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-600 dark:text-slate-300">
                            <th class="py-2">{{ __('Date') }}</th>
                            <th class="py-2">{{ __('Method') }}</th>
                            <th class="py-2">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="py-2 text-slate-700 dark:text-slate-200">{{ optional($payment->created_at)->format('Y-m-d') }}</td>
                                <td class="py-2 text-slate-700 dark:text-slate-200">{{ $payment->payment_method ?? $payment->method ?? __('Payment') }}</td>
                                <td class="py-2 text-slate-700 dark:text-slate-200">{{ number_format((float)($payment->amount ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('portal.orders') }}" class="text-emerald-700 dark:text-emerald-300 hover:underline">← {{ __('Back to orders') }}</a>
    </div>
</div>
@endsection
