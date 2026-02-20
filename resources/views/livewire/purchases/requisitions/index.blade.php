<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ __('Purchase Requisitions') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Manage purchase requisitions') }}</p>
        </div>
        @can('purchases.requisitions.create')
            <a href="{{ route('app.purchases.requisitions.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('New Requisition') }}
            </a>
        @endcan
    </div>

    @if(isset($statistics))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-blue-100 text-sm">{{ __('Total') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['total_requisitions'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-amber-100 text-sm">{{ __('Pending') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['pending'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-emerald-100 text-sm">{{ __('Approved') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['approved'] ?? 0) }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                <p class="text-purple-100 text-sm">{{ __('Converted') }}</p>
                <p class="text-2xl font-bold">{{ number_format($statistics['converted'] ?? 0) }}</p>
            </div>
        </div>
    @endif

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search...') }}" class="erp-input">
            </div>
            <select wire:model.live="status" class="erp-input lg:w-48">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="converted">{{ __('Converted') }}</option>
            </select>
            <select wire:model.live="priority" class="erp-input lg:w-48">
                <option value="">{{ __('All Priorities') }}</option>
                <option value="low">{{ __('Low') }}</option>
                <option value="normal">{{ __('Normal') }}</option>
                <option value="high">{{ __('High') }}</option>
                <option value="urgent">{{ __('Urgent') }}</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="cursor-pointer" wire:click="sortBy('code')">{{ __('Code') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('subject')">{{ __('Subject') }}</th>
                        <th>{{ __('Requested By') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('status')">{{ __('Status') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('priority')">{{ __('Priority') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('created_at')">{{ __('Created') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requisitions as $requisition)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="font-medium">{{ $requisition->reference_number }}</td>
                            <td>{{ $requisition->subject ?? '-' }}</td>
                            <td>{{ $requisition->requestedBy?->name ?? '-' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($requisition->status === 'approved') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif($requisition->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300
                                    @elseif($requisition->status === 'pending') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200
                                    @endif">
                                    {{ ucfirst($requisition->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($requisition->priority === 'urgent') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300
                                    @elseif($requisition->priority === 'high') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @elseif($requisition->priority === 'normal') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200
                                    @endif">
                                    {{ ucfirst($requisition->priority ?? 'normal') }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $requisition->created_at?->format('Y-m-d') }}</td>
                            <td class="text-end">
                                <div class="inline-flex items-center gap-2">
                                    @can('purchases.requisitions.manage')
                                        <a href="{{ route('app.purchases.requisitions.edit', $requisition->id) }}" class="text-blue-600 hover:text-blue-800" title="{{ __('Edit') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan

                                    @can('purchases.requisitions.approve')
                                        @if($requisition->status === 'pending')
                                            <button type="button" wire:click="approve({{ $requisition->id }})" class="text-emerald-600 hover:text-emerald-800" title="{{ __('Approve') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
	                                            <button type="button" wire:click="reject({{ $requisition->id }}, @js(__('Rejected')))" class="text-red-600 hover:text-red-800" title="{{ __('Reject') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728"/></svg>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('purchases.requisitions.manage')
	                                        <button type="button" onclick="return confirm(@js(__('Are you sure?')))" wire:click="delete({{ $requisition->id }})" class="text-slate-600 hover:text-slate-900" title="{{ __('Delete') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="text-slate-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium">{{ __('No requisitions found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $requisitions->links() }}</div>
    </div>
</div>
