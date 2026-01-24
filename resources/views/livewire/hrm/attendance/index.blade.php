<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Attendance') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Daily attendance records for employees in the current branch.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-48">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search employee (name, code)...') }}"
                       class="erp-input rounded-full">
            </div>

            <div class="flex items-center gap-2">
                <select wire:model="status" class="erp-input text-xs w-32">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="present">{{ __('Present') }}</option>
                    <option value="absent">{{ __('Absent') }}</option>
                    <option value="leave">{{ __('On leave') }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="date" wire:model="fromDate" class="erp-input text-xs w-36">
                <span class="text-xs text-slate-400">—</span>
                <input type="date" wire:model="toDate" class="erp-input text-xs w-36">
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Date') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Employee') }}
                    </th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Status') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Check in') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Check out') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Approved at') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white/80 dark:bg-slate-900/60">
                @forelse ($records as $row)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ optional($row->date)->format('Y-m-d') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100">
                            {{ $row->employee?->name ?? __('Unknown') }}
                            <span class="text-slate-400 text-[11px] ml-1">
                                {{ $row->employee?->code }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-center">
                            @php $status = $row->status; @endphp
                            @if ($status === 'present')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                    {{ __('Present') }}
                                </span>
                            @elseif ($status === 'absent')
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                    {{ __('Absent') }}
                                </span>
                            @elseif ($status === 'leave')
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                    {{ __('On leave') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ ucfirst($status) ?: __('Unknown') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ optional($row->check_in)->format('H:i') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ optional($row->check_out)->format('H:i') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ optional($row->approved_at)->format('Y-m-d H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            {{ __('No attendance records found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $records->links() }}
    </div>
</div>
