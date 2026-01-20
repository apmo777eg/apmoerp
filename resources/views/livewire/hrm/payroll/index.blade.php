{{-- resources/views/livewire/hrm/payroll/index.blade.php --}}
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Payroll') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Payroll runs and calculated salaries for employees in the current branch.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-40">
                <input type="month"
                       wire:model="period"
                       class="erp-input text-xs">
            </div>

            <div class="w-full sm:w-48">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search employee (name, code)...') }}"
                       class="erp-input rounded-full">
            </div>

            <div class="flex items-center gap-2">
                <select wire:model="status" class="erp-input text-xs w-32">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                </select>

                <a href="{{ route('app.hrm.payroll.run') }}"
                   class="erp-btn-primary text-xs px-3 py-2">
                    {{ __('Run payroll') }}
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Period') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Employee') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Basic') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Allowances') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Deductions') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Net') }}
                    </th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Status') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Paid at') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white/80 dark:bg-slate-900/60">
                @forelse ($runs as $row)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ $row->period }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100">
                            {{ $row->employee?->name ?? __('Unknown') }}
                            <span class="text-slate-400 text-[11px] ml-1">
                                {{ $row->employee?->code }}
                            </span>
                        </td>
                        {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-700 dark:text-slate-200">
                            {{ number_format(decimal_float($row->basic), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-700 dark:text-slate-200">
                            {{ number_format(decimal_float($row->allowances), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-700 dark:text-slate-200">
                            {{ number_format(decimal_float($row->deductions), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-800 dark:text-slate-100">
                            {{ number_format(decimal_float($row->net), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-center">
                            @php $status = $row->status; @endphp
                            @if ($status === 'draft')
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                    {{ __('Draft') }}
                                </span>
                            @elseif ($status === 'approved')
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700">
                                    {{ __('Approved') }}
                                </span>
                            @elseif ($status === 'paid')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                    {{ __('Paid') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ ucfirst($status) ?: __('Unknown') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ optional($row->paid_at)->format('Y-m-d H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            {{ __('No payroll records found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $runs->links() }}
    </div>
</div>
