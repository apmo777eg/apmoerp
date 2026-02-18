<div class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $project->name }}</h1>
            <p class="text-sm text-slate-500">{{ $project->reference_number }}</p>
        </div>
        <div class="flex gap-2">
            @can('projects.edit')
            <a href="{{ route('app.projects.edit', $project->id) }}" class="erp-btn erp-btn-secondary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ __('Edit') }}
            </a>
            @endcan
        </div>
    </div>

    {{-- Project Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Status') }}</p>
            <p class="text-lg font-semibold text-slate-900">{{ ucfirst($project->status) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Progress') }}</p>
            <p class="text-lg font-semibold text-slate-900">{{ number_format($project->progress ?? 0) }}%</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Budget') }}</p>
            <p class="text-lg font-semibold text-slate-900">{{ number_format($project->budget ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Tasks') }}</p>
            <p class="text-lg font-semibold text-slate-900">{{ $project->tasks_count ?? 0 }}</p>
        </div>
    </div>

    {{-- Project Details --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Project Details') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-slate-500">{{ __('Description') }}</p>
                <p class="text-slate-900">{{ $project->description ?? __('N/A') }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('Start Date') }}</p>
                <p class="text-slate-900">{{ $project->start_date?->format('Y-m-d') ?? __('N/A') }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('End Date') }}</p>
                <p class="text-slate-900">{{ $project->end_date?->format('Y-m-d') ?? __('N/A') }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('Manager') }}</p>
                <p class="text-slate-900">{{ $project->manager?->name ?? __('N/A') }}</p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="border-b border-slate-200">
            <nav class="flex -mb-px">
                <button type="button" wire:click="$set('activeTab', 'tasks')" 
                        class="px-6 py-3 text-sm font-medium {{ $activeTab === 'tasks' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-slate-500 hover:text-slate-700' }}">
                    {{ __('Tasks') }}
                </button>
                <button type="button" wire:click="$set('activeTab', 'time-logs')" 
                        class="px-6 py-3 text-sm font-medium {{ $activeTab === 'time-logs' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-slate-500 hover:text-slate-700' }}">
                    {{ __('Time Logs') }}
                </button>
                <button type="button" wire:click="$set('activeTab', 'expenses')" 
                        class="px-6 py-3 text-sm font-medium {{ $activeTab === 'expenses' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-slate-500 hover:text-slate-700' }}">
                    {{ __('Expenses') }}
                </button>
            </nav>
        </div>
        <div class="p-6">
            @if($activeTab === 'tasks')
                <livewire:projects.tasks :project-id="$project->id" />
            @elseif($activeTab === 'time-logs')
                <livewire:projects.time-logs :project-id="$project->id" />
            @elseif($activeTab === 'expenses')
                <livewire:projects.expenses :project-id="$project->id" />
            @endif
        </div>
    </div>
</div>
