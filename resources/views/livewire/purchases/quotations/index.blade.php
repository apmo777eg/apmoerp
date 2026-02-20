<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ __('Supplier Quotations') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Review and compare supplier quotations') }}</p>
        </div>

        @can('purchases.manage')
            <div class="flex items-center gap-2">
                <a href="{{ route('app.purchases.quotations.compare') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Compare') }}
                </a>
                <a href="{{ route('app.purchases.quotations.create') }}" class="erp-btn erp-btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New Quotation') }}
                </a>
            </div>
        @endcan
    </div>

    @if(isset($statistics))
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-blue-100 text-sm">{{ __('Total') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['total'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-amber-100 text-sm">{{ __('Pending') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['pending'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-emerald-100 text-sm">{{ __('Accepted') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['accepted'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-red-100 text-sm">{{ __('Rejected') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['rejected'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-slate-500 to-slate-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-slate-100 text-sm">{{ __('Expired') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['expired'] ?? 0) }}</p>
            </div>
        </div>
    @endif

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search...') }}" class="erp-input">
            </div>
            <select wire:model.live="status" class="erp-input lg:w-56">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="accepted">{{ __('Accepted') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="cursor-pointer" wire:click="sortBy('reference_number')">{{ __('Reference') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Requisition') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('quotation_date')">{{ __('Date') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('valid_until')">{{ __('Valid Until') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('total_amount')">{{ __('Total') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('status')">{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $quotation)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="font-medium">{{ $quotation->reference_number ?? $quotation->reference_number }}</td>
                            <td>{{ $quotation->supplier?->name ?? '-' }}</td>
                            <td>{{ $quotation->requisition?->reference_number ?? '-' }}</td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $quotation->valid_until?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ number_format((float) ($quotation->total_amount ?? $quotation->total_amount ?? 0), 2) }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($quotation->status === 'accepted') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif($quotation->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300
                                    @elseif($quotation->status === 'pending') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @elseif($quotation->status === 'expired') bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200
                                    @endif">
                                    {{ ucfirst($quotation->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="inline-flex items-center gap-2">
                                    @can('purchases.manage')
                                        <a href="{{ route('app.purchases.quotations.edit', $quotation->id) }}" class="text-blue-600 hover:text-blue-800" title="{{ __('Edit') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan

                                    <a href="{{ route('app.purchases.quotations.compare', ['requisition' => $quotation->requisition_id]) }}" class="text-slate-600 hover:text-slate-900" title="{{ __('Compare') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m4 0V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10m18 0a2 2 0 01-2 2H5a2 2 0 01-2-2"/></svg>
                                    </a>

                                    @can('purchases.manage')
                                        @if($quotation->status === 'pending')
                                            <button type="button" wire:click="accept({{ $quotation->id }})" class="text-emerald-600 hover:text-emerald-800" title="{{ __('Accept') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
	                                            <button type="button" wire:click="reject({{ $quotation->id }}, @js(__('Rejected')))" class="text-red-600 hover:text-red-800" title="{{ __('Reject') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728"/></svg>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12">
                                <div class="text-slate-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium">{{ __('No quotations found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $quotations->links() }}</div>
    </div>
</div>
