<div class="space-y-6">
    {{-- Global Loading Overlay --}}
    <div wire:loading.delay class="loading-overlay bg-slate-900/20 flex items-center justify-center" style="display:none">
        <div class="bg-white rounded-xl shadow-xl p-6 flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-slate-700 font-medium">{{ __('Loading...') }}</span>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Helpdesk Tickets') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage support tickets and customer inquiries') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('helpdesk.create')
            <a href="{{ route('app.helpdesk.tickets.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('New Ticket') }}
            </a>
            @endcan
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total Tickets') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm">{{ __('Open') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['open']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">{{ __('Overdue') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['overdue']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Resolved') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['resolved']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Search') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search tickets...') }}" class="erp-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Status') }}</label>
                <select wire:model.live="status" class="erp-input w-full">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="new">{{ __('New') }}</option>
                    <option value="open">{{ __('Open') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="resolved">{{ __('Resolved') }}</option>
                    <option value="closed">{{ __('Closed') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Priority') }}</label>
                <select wire:model.live="priorityId" class="erp-input w-full">
                    <option value="">{{ __('All Priorities') }}</option>
                    @foreach($priorities as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Category') }}</label>
                <select wire:model.live="category" class="erp-input w-full">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Assigned') }}</label>
                <select wire:model.live="assigned" class="erp-input w-full">
                    <option value="">{{ __('All Tickets') }}</option>
                    <option value="me">{{ __('My Tickets') }}</option>
                    <option value="unassigned">{{ __('Unassigned') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tickets Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th wire:click="sortBy('ticket_number')" class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100">
                            {{ __('Ticket #') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Subject') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Customer') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Priority') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Assigned To') }}
                        </th>
                        <th wire:click="sortBy('created_at')" class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100">
                            {{ __('Created') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                {{ $ticket->ticket_number }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-900">
                                <div class="font-medium">{{ $ticket->subject }}</div>
                                @if($ticket->isOverdue())
                                    <span class="text-xs text-red-600">{{ __('Overdue') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                {{ $ticket->customer?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($ticket->status === 'new') bg-blue-100 text-blue-800
                                    @elseif($ticket->status === 'open') bg-amber-100 text-amber-800
                                    @elseif($ticket->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($ticket->status === 'resolved') bg-emerald-100 text-emerald-800
                                    @else bg-slate-100 text-slate-800
                                    @endif">
                                    {{ __(ucfirst($ticket->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($ticket->priority)
                                    <span class="px-2 py-1 text-xs font-semibold rounded" style="background-color: {{ $ticket->priority->color }}20; color: {{ $ticket->priority->color }}">
                                        {{ $ticket->priority->name }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                {{ $ticket->assignedAgent?->name ?? __('Unassigned') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('app.helpdesk.tickets.show', $ticket->id) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ __('View') }}
                                    </a>
                                    @can('helpdesk.edit')
                                        <a href="{{ route('app.helpdesk.tickets.edit', $ticket->id) }}" class="text-emerald-600 hover:text-emerald-900">
                                            {{ __('Edit') }}
                                        </a>
                                    @endcan
                                    @can('helpdesk.delete')
                                        <button type="button" wire:click="delete({{ $ticket->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-900">
                                            {{ __('Delete') }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No tickets found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
