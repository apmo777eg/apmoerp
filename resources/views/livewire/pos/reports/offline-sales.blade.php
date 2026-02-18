<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ __('Offline POS sales') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Sales that were synced from the offline POS queue.') }}
            </p>
        </div>
    </div>


    <div class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-xs text-slate-700 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                {{ __('Branch') }}
            </label>
	            @if($branchContextId)
	                @php
	                    $lockedBranchName = optional($branches->firstWhere('id', $branchContextId))->name ?? __('Selected Branch');
	                @endphp
	                <div class="rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-900 flex items-center gap-2">
	                    <span class="font-semibold truncate">{{ $lockedBranchName }}</span>
	                    <span class="ms-auto text-[11px] bg-white/70 border border-emerald-200 rounded-full px-2 py-0.5">{{ __('Locked') }}</span>
	                </div>
	            @else
	                <select wire:model.live.debounce.500ms="branchId"
	                        class="rounded border border-slate-200 bg-white px-2 py-1 text-xs">
	                    <option value="">{{ __('All') }}</option>
	                    @foreach ($branches as $branch)
	                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
	                    @endforeach
	                </select>
	            @endif
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                {{ __('From date') }}
            </label>
            <input type="date"
                   wire:model.live.debounce.500ms="dateFrom"
                   class="rounded border border-slate-200 bg-white px-2 py-1 text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-500 mb-0.5">
                {{ __('To date') }}
            </label>
            <input type="date"
                   wire:model.live.debounce.500ms="dateTo"
                   class="rounded border border-slate-200 bg-white px-2 py-1 text-xs">
        </div>
    </div>


        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">
                        {{ __('Date') }}
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">
                        {{ __('Code') }}
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">
                        {{ __('Branch') }}
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">
                        {{ __('Customer') }}
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-600">
                        {{ __('Total') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($sales as $sale)
                    <tr>
                        <td class="px-3 py-2 text-xs text-slate-600">
                            {{ $sale->created_at?->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $sale->reference_number }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $sale->branch?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $sale->customer?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-xs text-right text-slate-800">
                            {{ number_format($sale->total_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-xs text-slate-400">
                            {{ __('No offline POS sales found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-100 bg-slate-50 px-3 py-2">
            {{ $sales->links() }}
        </div>
    </div>
</div>
