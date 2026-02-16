<div class="space-y-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Projects') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage projects and tasks') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('projects.create')
            <a href="{{ route('app.projects.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Project') }}
            </a>
            @endcan
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total Projects') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Active') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['active']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">{{ __('Completed') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['completed']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">{{ __('Overdue') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['overdue']) }}</p>
                </div>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Search') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                       class="erp-input" placeholder="{{ __('Search projects...') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Status') }}</label>
                <select wire:model.live="status" class="erp-input">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="planning">{{ __('Planning') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="on_hold">{{ __('On Hold') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Projects Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('code')">
                            {{ __('Code') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                            {{ __('Name') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Client') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Manager') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Progress') }}
                        </th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($projects as $project)
                    <tr wire:key="project-{{ $project->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                            {{ $project->code }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-900">
                            <div>
                                <p class="font-medium">{{ $project->name }}</p>
                                <p class="text-slate-500 text-xs">{{ Str::limit($project->description, 50) }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ $project->client?->name ?? __('N/A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ $project->manager?->name ?? __('N/A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($project->status === 'active') bg-green-100 text-green-800
                                @elseif($project->status === 'completed') bg-blue-100 text-blue-800
                                @elseif($project->status === 'on_hold') bg-yellow-100 text-yellow-800
                                @elseif($project->status === 'cancelled') bg-red-100 text-red-800
                                @else bg-slate-100 text-slate-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-full" style="width: {{ $project->progress ?? 0 }}%"></div>
                                </div>
                                <span class="text-xs text-slate-600">{{ number_format($project->progress ?? 0) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                            <a href="{{ route('app.projects.show', $project->id) }}" class="text-emerald-600 hover:text-emerald-900">
                                {{ __('View') }}
                            </a>
                            @can('projects.edit')
                            <a href="{{ route('app.projects.edit', $project->id) }}" class="text-blue-600 hover:text-blue-900 ms-3">
                                {{ __('Edit') }}
                            </a>
                            @endcan
                            @can('projects.delete')
                            <button type="button" wire:click="delete({{ $project->id }})" wire:confirm="{{ __('Are you sure?') }}" 
                                    class="text-red-600 hover:text-red-900 ms-3">
                                {{ __('Delete') }}
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <p>{{ __('No projects found') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $projects->links() }}
        </div>
    </div>

    {{-- Loading State --}}
    <div wire:loading class="loading-overlay bg-slate-900/10 flex items-center justify-center" style="display:none">
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
</div>
