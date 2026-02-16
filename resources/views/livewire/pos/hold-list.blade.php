<div class="space-y-2">
    @if(count($holds) > 0)
        <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Held Transactions') }} ({{ count($holds) }})</span>
        </div>
        @foreach($holds as $index => $hold)
            <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-700">
                <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ $hold['customer_name'] ?? __('Walk-in Customer') }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ count($hold['items'] ?? []) }} {{ __('items') }} · 
                        {{ number_format($hold['total'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        {{ $hold['held_at'] ?? '' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="resumeHold({{ $index }})" class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg" title="{{ __('Resume') }}">
                        {{ __('Resume') }}
                    </button>
                    <button type="button" wire:click="deleteHold({{ $index }})" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded" title="{{ __('Delete') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-8 px-4">
            <div class="text-4xl mb-2">📋</div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No held transactions') }}</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('Hold a transaction to see it here') }}</p>
        </div>
    @endif
</div>
