{{-- resources/views/livewire/rental/contracts/index.blade.php --}}
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Rental contracts') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Contracts between tenants and rental units for the current branch.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-56">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search tenant, phone, unit code...') }}"
                       class="erp-input rounded-full">
            </div>

            <div class="flex items-center gap-2">
                <select wire:model="status" class="erp-input text-xs w-32">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="ended">{{ __('Ended') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="date" wire:model="fromDate" class="erp-input text-xs w-36">
                <span class="text-xs text-slate-400">—</span>
                <input type="date" wire:model="toDate" class="erp-input text-xs w-36">
            </div>

            <a href="{{ route('app.rental.contracts.create') }}"
               class="erp-btn-primary text-xs px-3 py-2">
                {{ __('Add contract') }}
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Tenant') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Unit') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Property') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Start date') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('End date') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Rent') }}
                    </th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Status') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white/80 dark:bg-slate-900/60">
                @forelse ($contracts as $row)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100">
                            {{ $row->tenant?->name ?? __('Unknown') }}
                            <span class="block text-[11px] text-slate-400">
                                {{ $row->tenant?->phone }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ $row->unit?->code ?? __('N/A') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ $row->unit?->property?->name ?? __('N/A') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ optional($row->start_date)->format('Y-m-d') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ optional($row->end_date)->format('Y-m-d') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-800 dark:text-slate-100">
                            {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                            {{ number_format(decimal_float($row->rent), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-center">
                            @php $status = $row->status; @endphp
                            @if ($status === 'draft')
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                    {{ __('Draft') }}
                                </span>
                            @elseif ($status === 'active')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                    {{ __('Active') }}
                                </span>
                            @elseif ($status === 'ended')
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                    {{ __('Ended') }}
                                </span>
                            @elseif ($status === 'cancelled')
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                    {{ __('Cancelled') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ ucfirst($status) ?: __('Unknown') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-end">
                            <a href="{{ route('app.rental.contracts.edit', $row->id) }}"
                               class="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1 text-[11px] font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <i class="mdi mdi-pencil-outline text-[13px] mr-1"></i>
                                {{ __('Edit') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            {{ __('No rental contracts found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $contracts->links() }}
    </div>
</div>
