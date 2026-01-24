<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    class="relative"
>
    <button type="button"
            x-on:click="open = !open"
            class="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-100 bg-white text-emerald-600 shadow-sm shadow-emerald-500/30 hover:bg-emerald-50">
        🔔
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-0.5 inline-flex min-w-[16px] items-center justify-center rounded-full bg-emerald-500 px-1 text-[0.6rem] font-semibold text-white shadow-sm">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <div x-cloak
         x-show="open"
         x-transition
         class="absolute right-0 mt-2 w-72 rounded-2xl border border-slate-200 bg-white shadow-lg shadow-emerald-500/20 z-popover">
        <div class="px-3 py-2 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-700">
                {{ __('Notifications') }}
            </span>
        </div>

        <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
            @forelse($items as $item)
                <div class="px-3 py-2 text-xs text-slate-700">
                    <span class="font-medium text-emerald-600">{{ strtoupper($item['type']) }}</span>
                    <span class="mx-1">•</span>
                    <span>{{ $item['message'] }}</span>
                </div>
            @empty
                <div class="px-3 py-4 text-xs text-slate-500 text-center">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
