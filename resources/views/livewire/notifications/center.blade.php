<div class="space-y-6">
    @include('components.erp.breadcrumb', [
        'items' => [
            ['label' => __('Notifications')],
            ['label' => __('Center')],
        ],
    ])

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-50">
                {{ __('Notifications Center') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('All system notifications in one place (POS, Rental, HRM, ...).') }}
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <button wire:click="markAllAsRead" type="button" class="erp-btn-primary">
                {{ __('Mark all as read') }}
            </button>
        </div>
    </div>

    <div class="erp-card p-4 rounded-2xl">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2 text-xs">
                <button wire:click="$set('type','all')"
                        class="px-3 py-1 rounded-full border text-xs
                            @if($type === 'all')
                                bg-emerald-600 text-white border-emerald-500
                            @else
                                bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700
                            @endif">
                    {{ __('All') }}
                </button>
                <button wire:click="$set('type','pos')"
                        class="px-3 py-1 rounded-full border text-xs
                            @if(str_starts_with($type, 'pos'))
                                bg-emerald-600 text-white border-emerald-500
                            @else
                                bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700
                            @endif">
                    {{ __('POS') }}
                </button>
                <button wire:click="$set('type','rental')"
                        class="px-3 py-1 rounded-full border text-xs
                            @if(str_starts_with($type, 'rental'))
                                bg-emerald-600 text-white border-emerald-500
                            @else
                                bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700
                            @endif">
                    {{ __('Rental') }}
                </button>
                <button wire:click="$set('type','hr')"
                        class="px-3 py-1 rounded-full border text-xs
                            @if(str_starts_with($type, 'hr'))
                                bg-emerald-600 text-white border-emerald-500
                            @else
                                bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700
                            @endif">
                    {{ __('HRM') }}
                </button>
            </div>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($items as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = is_null($notification->read_at);
                @endphp
                <div class="flex items-start justify-between py-3 erp-pos-row">
                    <div class="flex-1 mr-3">
                        <div class="flex items-center space-x-2">
                            @if(isset($data['type']))
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide
                                    @if(str_starts_with($data['type'], 'rental'))
                                        bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300
                                    @elseif(str_starts_with($data['type'], 'hr'))
                                        bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300
                                    @elseif(str_starts_with($data['type'], 'pos'))
                                        bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300
                                    @else
                                        bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300
                                    @endif">
                                    {{ strtoupper(strtok($data['type'], '.')) }}
                                </span>
                            @endif
                            @if($isUnread)
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-900 dark:text-slate-50">
                            {{ $data['message'] ?? __('Notification') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ $notification->created_at }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($isUnread)
                            <button wire:click="markAsRead('{{ $notification->id }}')"
                                    type="button"
                                    class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                {{ __('Mark read') }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('No notifications found.') }}
                </p>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </div>
</div>

@script
<script>
    // Livewire 4 FIX: Wrap with @script for proper execution timing across SPA navigation
    if (window.Echo && window.Laravel && window.Laravel.userId) {
        window.Echo.private('App.Models.User.' + window.Laravel.userId)
            .notification((notification) => {
                // Use Livewire.dispatch() for global event broadcasting in Livewire 4
                Livewire.dispatch('notification-received');
            });
    }
</script>
@endscript
