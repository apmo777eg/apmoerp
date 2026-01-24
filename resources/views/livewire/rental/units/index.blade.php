<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Rental units') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Units and apartments under rental properties for the current branch.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-56">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search (code, type, property)...') }}"
                       class="erp-input rounded-full">
            </div>

            <div class="flex items-center gap-2">
                <select wire:model="propertyId" class="erp-input text-xs w-40">
                    <option value="">{{ __('All properties') }}</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}">{{ $property->name }}</option>
                    @endforeach
                </select>

                <select wire:model="status" class="erp-input text-xs w-32">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="available">{{ __('Available') }}</option>
                    <option value="occupied">{{ __('Occupied') }}</option>
                    <option value="maintenance">{{ __('Maintenance') }}</option>
                </select>

                <a href="{{ route('app.rental.units.create') }}"
                   class="erp-btn-primary text-xs px-3 py-2">
                    {{ __('Add unit') }}
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Code') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Property') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Type') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Rent') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Deposit') }}
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
                @forelse ($units as $unit)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100">
                            {{ $unit->code }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-700 dark:text-slate-200">
                            {{ $unit->property?->name ?? __('N/A') }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ $unit->type ?? '—' }}
                        </td>
                        {{-- V43-FINANCE-01 FIX: Use decimal_float() for proper BCMath-based rounding --}}
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-700 dark:text-slate-200">
                            {{ number_format(decimal_float($unit->rent), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-right tabular-nums text-slate-700 dark:text-slate-200">
                            {{ number_format(decimal_float($unit->deposit), 2) }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-center">
                            @php $status = $unit->status; @endphp
                            @if ($status === 'available')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                    {{ __('Available') }}
                                </span>
                            @elseif ($status === 'occupied')
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700">
                                    {{ __('Occupied') }}
                                </span>
                            @elseif ($status === 'maintenance')
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                    {{ __('Maintenance') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ ucfirst($status) ?: __('Unknown') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-end">
                            <a href="{{ route('app.rental.units.edit', $unit->id) }}"
                               class="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1 text-[11px] font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <i class="mdi mdi-pencil-outline text-[13px] mr-1"></i>
                                {{ __('Edit') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            {{ __('No rental units found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $units->links() }}
    </div>
</div>
