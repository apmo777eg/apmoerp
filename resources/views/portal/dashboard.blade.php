@extends('layouts.guest')

@section('title', __('Portal Dashboard'))

@section('content')
@include('portal.partials.nav')

<div class="max-w-5xl mx-auto p-4">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">
        {{ __('Welcome, :name', ['name' => $customer->name ?? __('Customer')]) }}
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Total Orders') }}</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['total_orders'] ?? 0 }}</div>
        </div>
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Total Spent') }}</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float)($stats['total_spent'] ?? 0), 2) }}</div>
        </div>
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Active Orders') }}</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['active_orders'] ?? 0 }}</div>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Recent Orders') }}</h3>
            <a href="{{ route('portal.orders') }}" class="text-sm text-emerald-700 dark:text-emerald-300 hover:underline">{{ __('View all') }}</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-600 dark:text-slate-300">
                        <th class="py-2">{{ __('Reference') }}</th>
                        <th class="py-2">{{ __('Date') }}</th>
                        <th class="py-2">{{ __('Status') }}</th>
                        <th class="py-2">{{ __('Total') }}</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="py-2 text-slate-900 dark:text-slate-100">
                                {{ $order->reference_number ?? $order->id }}
                            </td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">
                                {{ optional($order->sale_date)->format('Y-m-d') ?? optional($order->created_at)->format('Y-m-d') }}
                            </td>
                            <td class="py-2">
                                <span class="px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
                                    {{ ucfirst($order->status ?? __('unknown')) }}
                                </span>
                            </td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">
                                {{ number_format((float)($order->total_amount ?? 0), 2) }}
                            </td>
                            <td class="py-2">
                                <a href="{{ route('portal.orders.details', $order->id) }}" class="text-emerald-700 dark:text-emerald-300 hover:underline">
                                    {{ __('Details') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-600 dark:text-slate-300">{{ __('No orders found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
