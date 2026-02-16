<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $ticket->ticket_number }}</h1>
            <p class="text-sm text-slate-500">{{ $ticket->subject }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('helpdesk.edit')
                <a href="{{ route('app.helpdesk.tickets.edit', $ticket->id) }}" class="erp-btn erp-btn-secondary">
                    {{ __('Edit') }}
                </a>
            @endcan
            @if($ticket->status !== 'resolved' && $ticket->status !== 'closed')
                @can('helpdesk.edit')
                    <button type="button" wire:click="resolveTicket" class="erp-btn erp-btn-success">
                        {{ __('Mark as Resolved') }}
                    </button>
                @endcan
            @endif
            @if($ticket->canBeClosed())
                @can('helpdesk.close')
                    <button type="button" wire:click="closeTicket" wire:confirm="{{ __('Are you sure?') }}" class="erp-btn erp-btn-secondary">
                        {{ __('Close Ticket') }}
                    </button>
                @endcan
            @endif
            @if($ticket->canBeReopened())
                @can('helpdesk.edit')
                    <button type="button" wire:click="reopenTicket" class="erp-btn erp-btn-primary">
                        {{ __('Reopen') }}
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Ticket Details --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Ticket Details') }}</h2>
                <div class="prose max-w-none">
                    <p class="text-slate-700 whitespace-pre-wrap">{{ $ticket->description }}</p>
                </div>
            </div>

            {{-- Replies --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Conversation') }}</h2>
                
                <div class="space-y-4 mb-6">
                    @forelse($ticket->replies as $reply)
                        <div class="flex gap-4 @if($reply->isInternal()) bg-yellow-50 @else bg-slate-50 @endif p-4 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                    {{ substr($reply->user?->name ?? '??', 0, 2) }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-semibold text-slate-900">{{ $reply->user?->name ?? __('Unknown') }}</span>
                                        @if($reply->isInternal())
                                            <span class="ml-2 px-2 py-0.5 text-xs bg-yellow-200 text-yellow-800 rounded">{{ __('Internal Note') }}</span>
                                        @endif
                                    </div>
                                    <span class="text-sm text-slate-500">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-slate-700 whitespace-pre-wrap">{{ $reply->message }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-slate-500 py-8">{{ __('No replies yet') }}</p>
                    @endforelse
                </div>

                {{-- Reply Form --}}
                @can('helpdesk.reply')
                    <div class="border-t pt-6">
                        <h3 class="font-semibold text-slate-800 mb-3">{{ __('Add Reply') }}</h3>
                        <textarea wire:model="replyMessage" rows="4" class="erp-input w-full mb-3" placeholder="{{ __('Type your reply...') }}"></textarea>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="isInternal" class="rounded border-slate-300">
                                <span class="text-sm text-slate-700">{{ __('Internal Note') }}</span>
                            </label>
                            <button type="button" wire:click="addReply" class="erp-btn erp-btn-primary">
                                {{ __('Send Reply') }}
                            </button>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Ticket Info --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-slate-800 mb-4">{{ __('Ticket Information') }}</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded 
                                @if($ticket->status === 'new') bg-blue-100 text-blue-800
                                @elseif($ticket->status === 'open') bg-amber-100 text-amber-800
                                @elseif($ticket->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($ticket->status === 'resolved') bg-emerald-100 text-emerald-800
                                @else bg-slate-100 text-slate-800
                                @endif">
                                {{ __(ucfirst($ticket->status)) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Priority') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if($ticket->priority)
                                <span class="px-2 py-1 text-xs font-semibold rounded" style="background-color: {{ $ticket->priority->color }}20; color: {{ $ticket->priority->color }}">
                                    {{ $ticket->priority->name }}
                                </span>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Category') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $ticket->category?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Customer') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $ticket->customer?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('Created') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $ticket->created_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                    @if($ticket->due_date)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Due Date') }}</dt>
                            <dd class="mt-1 text-sm @if($ticket->isOverdue()) text-red-600 @else text-slate-900 @endif">
                                {{ $ticket->due_date?->format('Y-m-d H:i') }}
                                @if($ticket->isOverdue())
                                    <span class="text-xs">({{ __('Overdue') }})</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Assignment --}}
            @can('helpdesk.assign')
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Assignment') }}</h3>
                    <select wire:model="assignToUser" class="erp-input w-full mb-3">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="assignTicket" class="erp-btn erp-btn-primary w-full">
                        {{ __('Assign') }}
                    </button>
                </div>
            @endcan

            {{-- SLA Compliance --}}
            @if($slaCompliance['has_sla'])
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('SLA Compliance') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('First Response') }}</dt>
                            <dd class="mt-1">
                                @if(isset($slaCompliance['response_sla_met']))
                                    <span class="px-2 py-1 text-xs font-semibold rounded @if($slaCompliance['response_sla_met']) bg-emerald-100 text-emerald-800 @else bg-red-100 text-red-800 @endif">
                                        {{ $slaCompliance['response_sla_met'] ? __('Met') : __('Breached') }}
                                    </span>
                                @else
                                    <span class="text-sm text-amber-600">{{ __('Pending') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">{{ __('Resolution') }}</dt>
                            <dd class="mt-1">
                                @if(isset($slaCompliance['resolution_sla_met']))
                                    <span class="px-2 py-1 text-xs font-semibold rounded @if($slaCompliance['resolution_sla_met']) bg-emerald-100 text-emerald-800 @else bg-red-100 text-red-800 @endif">
                                        {{ $slaCompliance['resolution_sla_met'] ? __('Met') : __('Breached') }}
                                    </span>
                                @else
                                    <span class="text-sm text-amber-600">{{ __('Pending') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>
    </div>
</div>
