<div class="container mx-auto px-4 py-6">
    <x-ui.page-header 
        title="{{ __('Project Gantt Chart') }}"
        subtitle="{{ __('Visual timeline of projects and tasks') }}"
    />

    {{-- Controls --}}
    <div class="erp-card p-4 mt-6 flex flex-col lg:flex-row items-center justify-between gap-4">
        {{-- View Mode --}}
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-400">{{ __('View') }}:</label>
            <select wire:model.live="viewMode" class="erp-input w-auto text-sm">
                <option value="week">{{ __('Week') }}</option>
                <option value="month">{{ __('Month') }}</option>
                <option value="quarter">{{ __('Quarter') }}</option>
            </select>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousPeriod" class="erp-btn erp-btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" wire:click="goToToday" class="erp-btn erp-btn-secondary text-sm">{{ __('Today') }}</button>
            <button type="button" wire:click="nextPeriod" class="erp-btn erp-btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 ml-2">
                {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            </span>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">{{ __('Project') }}:</label>
                <select wire:model.live="projectId" class="erp-input w-auto text-sm">
                    <option value="">{{ __('All Projects') }}</option>
                    @foreach($this->allProjects as $project)
                        <option value="{{ $project->id }}">{{ $project->code }} - {{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">{{ __('Status') }}:</label>
                <select wire:model.live="status" class="erp-input w-auto text-sm">
                    <option value="">{{ __('All') }}</option>
                    <option value="planning">{{ __('Planning') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="on_hold">{{ __('On Hold') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Gantt Chart --}}
    <div class="erp-card mt-4 overflow-x-auto">
        <div class="min-w-[1200px]">
            {{-- Timeline Header --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 sticky top-0 z-10">
                <div class="w-72 shrink-0 p-3 border-r border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Project / Task') }}</span>
                </div>
                <div class="flex-1 flex">
                    @foreach($this->timelineHeader as $header)
                        <div @class([
                            'flex-1 text-center py-2 text-xs border-r border-gray-100 dark:border-gray-700 last:border-r-0',
                            'bg-blue-50 dark:bg-blue-900/20' => $header['is_today'],
                            'bg-gray-100 dark:bg-gray-800' => $header['is_weekend'] && !$header['is_today'],
                        ])>
                            <div class="font-medium {{ $header['is_today'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $header['label'] }}</div>
                            @if($header['sub_label'])
                                <div class="text-gray-500 dark:text-gray-400 text-[10px]">{{ $header['sub_label'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Projects and Tasks --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($this->projects as $project)
                    {{-- Project Row --}}
                    <div class="flex bg-gray-50/50 dark:bg-gray-800/30">
                        {{-- Project Info --}}
                        <div class="w-72 shrink-0 p-3 border-r border-gray-200 dark:border-gray-700">
                            <a href="{{ route('app.projects.show', $project['id']) }}" class="block">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    <span class="font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $project['name'] }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] {{ $this->getPriorityColor($project['priority'] ?? 'normal') }}">
                                        {{ ucfirst($project['priority'] ?? 'N') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $project['code'] }} · {{ $project['client_name'] }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full {{ $project['progress'] >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min(100, $project['progress']) }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $project['progress'] }}%</span>
                                </div>
                            </a>
                        </div>

                        {{-- Project Timeline Bar --}}
                        <div class="flex-1 relative py-3 px-1">
                            @php
                                $position = $this->getTimelinePosition($project['start_date'], $project['end_date']);
                            @endphp
                            @if($position['width'] > 0)
                                <div class="absolute top-1/2 -translate-y-1/2 h-7 rounded-md flex items-center px-2 text-xs text-white shadow-sm overflow-hidden {{ $this->getStatusColor($project['status']) }} {{ $project['is_overdue'] ? 'ring-2 ring-red-500' : '' }}"
                                     style="left: {{ $position['left'] }}%; width: {{ $position['width'] }}%;"
                                     title="{{ $project['name'] }}: {{ $project['start_date'] }} - {{ $project['end_date'] }}">
                                    <span class="truncate font-medium">{{ $project['name'] }}</span>
                                    @if($project['is_overdue'])
                                        <svg class="w-3 h-3 ml-1 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Task Rows --}}
                    @foreach($project['tasks'] as $task)
                        <div class="flex hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            {{-- Task Info --}}
                            <div class="w-72 shrink-0 p-3 pl-8 border-r border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $task['name'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1 pl-5">
                                    <div class="flex-1 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500" style="width: {{ min(100, $task['progress']) }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $task['progress'] }}%</span>
                                </div>
                            </div>

                            {{-- Task Timeline Bar --}}
                            <div class="flex-1 relative py-2 px-1">
                                @php
                                    $taskPosition = $this->getTimelinePosition($task['start_date'], $task['end_date']);
                                @endphp
                                @if($taskPosition['width'] > 0)
                                    <div class="absolute top-1/2 -translate-y-1/2 h-4 rounded flex items-center px-1.5 text-[10px] text-white shadow-sm overflow-hidden {{ $this->getStatusColor($task['status']) }}"
                                         style="left: {{ $taskPosition['left'] }}%; width: {{ $taskPosition['width'] }}%;">
                                        <span class="truncate">{{ $task['name'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('No projects in this period') }}</p>
                        <a href="{{ route('app.projects.create') }}" class="mt-4 inline-block erp-btn erp-btn-primary text-sm">
                            {{ __('Create Project') }}
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="erp-card p-4 mt-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Legend') }}</h4>
        <div class="flex flex-wrap gap-4 text-xs">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-gray-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Planning/Draft') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-blue-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Planning') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-amber-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Active') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-orange-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('On Hold') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-green-400"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Completed') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-amber-400 ring-2 ring-red-500"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ __('Overdue') }}</span>
            </div>
        </div>
    </div>
</div>
