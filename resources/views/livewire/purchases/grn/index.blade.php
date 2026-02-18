<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Goods Received Notes') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage goods received notes (GRN)') }}</p>
        </div>
        @can('purchases.manage')
            <a href="{{ route('app.purchases.grn.create') }}" class="erp-btn erp-btn-primary" wire:navigate>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('New GRN') }}
            </a>
        @endcan
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-8 gap-3">
        @php
                        $cards = [
                ['key' => 'total', 'label' => __('Total')],
                ['key' => 'draft', 'label' => __('Draft')],
                ['key' => 'pending', 'label' => __('Pending')],
                ['key' => 'inspecting', 'label' => __('Inspecting')],
                ['key' => 'approved', 'label' => __('Approved')],
                ['key' => 'partial', 'label' => __('Partial')],
                ['key' => 'complete', 'label' => __('Complete')],
                ['key' => 'rejected', 'label' => __('Rejected')],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="erp-card p-4">
                <div class="text-xs text-slate-500">{{ $c['label'] }}</div>
                <div class="text-2xl font-semibold text-slate-800 tabular-nums">{{ $stats[$c['key']] ?? 0 }}</div>
            </div>
        @endforeach
    </div>

    <div class="erp-card p-4">
        <div class="flex flex-col lg:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search GRNs...') }}" class="erp-input">
            </div>
            <div class="w-full lg:w-60">
                <select wire:model.live="statusFilter" class="erp-input">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="inspecting">{{ __('Inspecting') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="partial">{{ __('Partial') }}</option>
                    <option value="complete">{{ __('Complete') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="cursor-pointer" wire:click="sortBy('reference_number')">
                            {{ __('GRN #') }}
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('purchase_id')">
                            {{ __('Purchase Order') }}
                        </th>
                        <th>{{ __('Supplier') }}</th>
                        <th class="cursor-pointer" wire:click="sortBy('received_date')">
                            {{ __('Date') }}
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('status')">
                            {{ __('Status') }}
                        </th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $grn)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $grn->reference_number }}</td>
                            <td>{{ $grn->purchase?->reference_number ?? '—' }}</td>
                            <td>{{ $grn->supplier?->name ?? '—' }}</td>
                            <td class="tabular-nums">{{ optional($grn->received_date)->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @php
                                    $status = $grn->status;
                                    $badge = 'bg-slate-100 text-slate-700';
                                    if ($status === 'approved') $badge = 'bg-emerald-100 text-emerald-700';
                                    elseif ($status === 'rejected') $badge = 'bg-red-100 text-red-700';
                                    elseif ($status === 'partial') $badge = 'bg-amber-100 text-amber-700';
                                    elseif ($status === 'pending' || $status === 'inspecting') $badge = 'bg-blue-100 text-blue-700';
                                    elseif ($status === 'draft') $badge = 'bg-slate-100 text-slate-600';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    @can('grn.inspect')
                                        <a href="{{ route('app.purchases.grn.inspection', $grn->id) }}" class="erp-btn erp-btn-secondary" wire:navigate>
                                            {{ __('Inspect') }}
                                        </a>
                                    @endcan

                                    @can('purchases.manage')
                                        <a href="{{ route('app.purchases.grn.edit', $grn->id) }}" class="erp-btn erp-btn-secondary" wire:navigate>
                                            {{ __('Edit') }}
                                        </a>
                                    @endcan

                                    @can('grn.update')
                                        @if(in_array($grn->status, ['approved','partial'], true))
                                            <button type="button" wire:click="markComplete({{ $grn->id }})" class="erp-btn erp-btn-primary">
                                                {{ __('Mark Complete') }}
                                            </button>
                                        @endif
                                    @endcan

                                    @can('grn.delete')
                                        <button type="button" wire:click="delete({{ $grn->id }})" class="erp-btn erp-btn-danger">
                                            {{ __('Delete') }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="text-slate-400">
                                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                    <p class="text-lg font-medium">{{ __('No GRNs found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $grns->links() }}
        </div>
    </div>
</div>
