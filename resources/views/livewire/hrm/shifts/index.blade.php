<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Shifts') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Manage work shifts for employees.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-56">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search shifts...') }}"
                       class="erp-input rounded-full">
            </div>

            <select wire:model.live="status" class="erp-input text-xs w-32">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>

            @can('hrm.manage')
            <a href="{{ route('app.hrm.shifts.create') }}" class="erp-btn-primary text-xs px-3 py-2">
                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Add Shift') }}
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Name') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Code') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Time') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Duration') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Working Days') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Status') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($shifts as $shift)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-200">
                            {{ $shift->name }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ $shift->reference_number }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ $shift->start_time }} - {{ $shift->end_time }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ number_format($shift->shift_duration, 1) }} hrs
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            @if($shift->working_days)
                                {{ implode(', ', array_map('ucfirst', $shift->working_days)) }}
                            @else
                                {{ __('All days') }}
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @can('hrm.manage')
                            <button type="button" wire:click="toggleActive({{ $shift->id }})" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shift->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ $shift->is_active ? __('Active') : __('Inactive') }}
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shift->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ $shift->is_active ? __('Active') : __('Inactive') }}
                            </span>
                            @endcan
                        </td>
                        <td class="px-3 py-2 text-end">
                            @can('hrm.manage')
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('app.hrm.shifts.edit', $shift->id) }}" class="text-blue-600 hover:text-blue-800 p-1" title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button type="button" wire:click="delete({{ $shift->id }})" wire:confirm="{{ __('Are you sure you want to delete this shift?') }}" class="text-red-600 hover:text-red-800 p-1" title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500 dark:text-slate-400">
                            {{ __('No shifts found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $shifts->links() }}
    </div>
</div>
