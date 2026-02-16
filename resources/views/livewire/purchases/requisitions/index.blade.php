<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Purchase Requisitions') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage purchase requisitions') }}</p>
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
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($statistics['total_requisitions'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">{{ __('Pending Approval') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($statistics['pending_approval'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Approved') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($statistics['approved'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">{{ __('Converted to PO') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($statistics['converted_to_po'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search requisitions...') }}" class="erp-input">
            </div>
            <select wire:model.live="status" class="erp-input lg:w-48">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="pending_approval">{{ __('Pending Approval') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="converted">{{ __('Converted') }}</option>
            </select>
        </div>

        @if(session()->has('success'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Employee') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requisitions ?? [] as $requisition)
                        <tr class="hover:bg-slate-50">
                            <td class="font-medium">{{ $requisition->requisition_code }}</td>
                            <td>{{ $requisition->subject }}</td>
                            <td>{{ $requisition->employee?->name ?? '-' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($requisition->status === 'approved') bg-emerald-100 text-emerald-700
                                    @elseif($requisition->status === 'rejected') bg-red-100 text-red-700
                                    @elseif($requisition->status === 'pending_approval') bg-amber-100 text-amber-700
                                    @else bg-slate-100 text-slate-700
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $requisition->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($requisition->priority === 'high') bg-red-100 text-red-700
                                    @elseif($requisition->priority === 'medium') bg-amber-100 text-amber-700
                                    @else bg-blue-100 text-blue-700
                                    @endif">
                                    {{ ucfirst($requisition->priority ?? 'normal') }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-500">{{ $requisition->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @can('purchases.requisitions.approve')
                                        @if($requisition->status === 'pending_approval')
                                            <button type="button" wire:click="approve({{ $requisition->id }})" class="text-emerald-600 hover:text-emerald-800" title="{{ __('Approve') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        @endif
                                    @endcan
                                    @can('purchases.requisitions.create')
                                        <button type="button" wire:click="delete({{ $requisition->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-800" title="{{ __('Delete') }}">
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

        @if(isset($requisitions))
            <div class="mt-4">{{ $requisitions->links() }}</div>
        @endif
    </div>
</div>
