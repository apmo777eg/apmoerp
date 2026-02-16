<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Stock Transfers') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage stock transfers between warehouses') }}</p>
        </div>
        @can('warehouse.manage')
        <a href="{{ route('app.warehouse.transfers.create') }}" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('New Transfer') }}
        </a>
        @endcan
    </div>

    @if(isset($stats))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">{{ __('Pending') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['pending']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Completed') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['completed']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">{{ __('Cancelled') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['cancelled']) }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search transfers...') }}" class="erp-input">
            </div>
            <select wire:model.live="statusFilter" class="erp-input lg:w-48">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="in_transit">{{ __('In Transit') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
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
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('From') }}</th>
                        <th>{{ __('To') }}</th>
                        <th>{{ __('Items') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers ?? [] as $transfer)
                        <tr class="hover:bg-slate-50">
                            <td class="text-sm text-slate-500">{{ $transfer->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="font-medium">{{ $transfer->fromWarehouse->name ?? '-' }}</td>
                            <td class="font-medium">{{ $transfer->toWarehouse->name ?? '-' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                    {{ $transfer->items->count() }} {{ __('items') }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($transfer->status === 'completed') bg-emerald-100 text-emerald-700
                                    @elseif($transfer->status === 'cancelled') bg-red-100 text-red-700
                                    @elseif($transfer->status === 'in_transit') bg-blue-100 text-blue-700
                                    @else bg-amber-100 text-amber-700
                                    @endif">
                                    {{ ucfirst($transfer->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($transfer->status === 'pending')
                                        <button type="button" wire:click="approve({{ $transfer->id }})" class="text-emerald-600 hover:text-emerald-800" title="{{ __('Approve') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button type="button" wire:click="cancel({{ $transfer->id }})" class="text-amber-600 hover:text-amber-800" title="{{ __('Cancel') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="delete({{ $transfer->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-800" title="{{ __('Delete') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="text-slate-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <p class="text-lg font-medium">{{ __('No transfers found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($transfers))
            <div class="mt-4">{{ $transfers->links() }}</div>
        @endif
    </div>
</div>
