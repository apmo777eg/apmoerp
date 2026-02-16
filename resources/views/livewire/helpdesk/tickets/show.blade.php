<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Ticket') }} #{{ $ticket->ticket_number }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $ticket->subject }}
            </p>
        </div>
        <a href="{{ route('app.helpdesk.tickets.index') }}" class="erp-btn-secondary text-xs">
            {{ __('Back to list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-green-800 dark:text-green-200">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Ticket Details -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-6">
                <h2 class="text-md font-semibold text-slate-800 dark:text-slate-100 mb-4">{{ __('Description') }}</h2>
                <div class="prose dark:prose-invert max-w-none text-slate-600 dark:text-slate-300">
                    {{ $ticket->description }}
                </div>
            </div>

            <!-- Replies -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-6">
                <h2 class="text-md font-semibold text-slate-800 dark:text-slate-100 mb-4">{{ __('Replies') }}</h2>
                
                <div class="space-y-4">
                    @forelse($ticket->replies as $reply)
                        <div class="border-l-4 {{ $reply->is_internal ? 'border-yellow-500' : 'border-blue-500' }} pl-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    {{ $reply->user?->name ?? __('Unknown') }}
                                    @if($reply->is_internal)
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ __('Internal') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $reply->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="text-sm text-slate-600 dark:text-slate-300">
                                {{ $reply->message }}
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500 dark:text-slate-400 text-sm">{{ __('No replies yet.') }}</p>
                    @endforelse
                </div>

                <!-- Add Reply Form -->
                <form wire:submit="addReply" class="mt-6 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Add Reply') }}
                        </label>
                        <textarea wire:model="replyMessage" rows="3" class="erp-input w-full" required></textarea>
                        @error('replyMessage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="isInternal" class="erp-checkbox">
                            <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">{{ __('Internal note') }}</span>
                        </label>
                        <button type="submit" class="erp-btn-primary text-sm">
                            {{ __('Add Reply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Actions -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-3">{{ __('Actions') }}</h3>
                <div class="space-y-2">
                    @if(!$ticket->assigned_to)
                        <button type="button" wire:click="assignToMe" class="w-full erp-btn-primary text-sm">
                            {{ __('Assign to Me') }}
                        </button>
                    @endif
                    @if(!in_array($ticket->status, ['resolved', 'closed']))
                        <button type="button" wire:click="resolve" class="w-full erp-btn-secondary text-sm">
                            {{ __('Mark as Resolved') }}
                        </button>
                    @endif
                    @if($ticket->canBeClosed())
                        <button type="button" wire:click="close" class="w-full erp-btn-secondary text-sm">
                            {{ __('Close Ticket') }}
                        </button>
                    @endif
                    @if($ticket->canBeReopened())
                        <button type="button" wire:click="reopen" class="w-full erp-btn-secondary text-sm">
                            {{ __('Reopen Ticket') }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-3">{{ __('Ticket Information') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Status') }}</dt>
                        <dd class="text-slate-700 dark:text-slate-200 font-medium">{{ ucfirst($ticket->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Priority') }}</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $ticket->priority?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Customer') }}</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $ticket->customer?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Category') }}</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $ticket->category?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Assigned To') }}</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $ticket->assignedAgent?->name ?? __('Unassigned') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Created') }}</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $ticket->created_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                    @if($ticket->due_date)
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Due Date') }}</dt>
                            <dd class="text-slate-700 dark:text-slate-200">
                                {{ $ticket->due_date?->format('Y-m-d H:i') }}
                                @if($ticket->isOverdue())
                                    <span class="ml-1 text-xs text-red-600 dark:text-red-400">({{ __('Overdue') }})</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
