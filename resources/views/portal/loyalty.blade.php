@extends('layouts.guest')

@section('title', __('Loyalty Points'))

@section('content')
@include('portal.partials.nav')

<div class="max-w-5xl mx-auto p-4">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">{{ __('Loyalty Points') }}</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Current Balance') }}</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ (int)($customer->loyalty_points ?? 0) }}</div>
        </div>
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Tier') }}</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ ucfirst($customer->loyalty_tier ?? __('none')) }}</div>
        </div>
        <div class="erp-card p-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">{{ __('Total Transactions') }}</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $transactions->total() }}</div>
        </div>
    </div>

    <div class="erp-card p-4">
        <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">{{ __('Transactions') }}</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-600 dark:text-slate-300">
                        <th class="py-2">{{ __('Date') }}</th>
                        <th class="py-2">{{ __('Type') }}</th>
                        <th class="py-2">{{ __('Points') }}</th>
                        <th class="py-2">{{ __('Description') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($transactions as $tx)
                        <tr>
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ optional($tx->created_at)->format('Y-m-d') }}</td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ ucfirst($tx->type ?? __('unknown')) }}</td>
                            <td class="py-2 text-slate-900 dark:text-slate-100">{{ (int)($tx->points ?? 0) }}</td>
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ $tx->description ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-600 dark:text-slate-300">{{ __('No transactions found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
