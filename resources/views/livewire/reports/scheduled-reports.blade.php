<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Scheduled Reports') }}</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Automate report generation and delivery') }}</p>
        </div>
        <a href="{{ route('admin.reports.scheduled.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
            <x-icon name="plus" class="w-5 h-5" />
            {{ __('New Schedule') }}
        </a>
    </div>

    <!-- Schedules Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Schedule Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Report Template') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Frequency') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Time') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Format') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Last Run') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($schedules as $schedule)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $schedule->name }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                {{ $schedule->template_name ?? __('Unknown') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full capitalize">
                                    {{ $schedule->frequency }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-sm">
                                {{ $schedule->time_of_day }}
                                @if($schedule->frequency === 'weekly')
                                    <span class="text-xs text-gray-400">({{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$schedule->day_of_week ?? 0] }})</span>
                                @elseif($schedule->frequency === 'monthly')
                                    <span class="text-xs text-gray-400">({{ __('Day') }} {{ $schedule->day_of_month }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 uppercase text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $schedule->format }}
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="toggleActive({{ $schedule->id }})" class="focus:outline-none">
                                    @if($schedule->is_active)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 rounded-full">
                                            {{ __('Paused') }}
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                                @if($schedule->last_run_at)
                                    {{ \Carbon\Carbon::parse($schedule->last_run_at)->diffForHumans() }}
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="runNow({{ $schedule->id }})" 
                                            class="text-gray-400 hover:text-emerald-500" title="{{ __('Run Now') }}">
                                        <x-icon name="play" class="w-4 h-4" />
                                    </button>
                                    <a href="{{ route('admin.reports.scheduled.edit', ['schedule' => $schedule->id]) }}" class="text-gray-400 hover:text-blue-500">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                    </a>
                                    <button type="button" wire:click="delete({{ $schedule->id }})" 
                                            wire:confirm="{{ __('Are you sure you want to delete this schedule?') }}"
                                            class="text-gray-400 hover:text-red-500">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <x-icon name="calendar" class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" />
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('No Scheduled Reports') }}</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('Create a schedule to automatically generate and send reports') }}</p>
                                    <a href="{{ route('admin.reports.scheduled.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                                        {{ __('Create Schedule') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</div>
