{{-- resources/views/components/ui/undo-notification.blade.php --}}
@props([
    'show' => false,
    'message' => 'Item deleted',
    'onUndo' => null,
    'duration' => 5000, // milliseconds
])

<div x-data="{ 
    show: @js($show),
    init() {
        if (this.show) {
            setTimeout(() => this.show = false, {{ $duration }});
        }
    }
}" 
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     class="z-notification fixed bottom-4 right-4 pointer-events-auto"
     {{ $attributes }}>
    <div class="bg-slate-800 dark:bg-slate-900 text-white rounded-lg shadow-lg flex items-center gap-4 px-4 py-3 min-w-[320px]">
        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        
        <p class="flex-1 text-sm font-medium">
            {{ $message }}
        </p>
        
        @if($onUndo)
        <button type="button" 
            wire:click="{{ $onUndo }}"
            @click="show = false"
            class="text-sm font-semibold text-emerald-400 hover:text-emerald-300 transition">
            {{ __('Undo') }}
        </button>
        @endif
        
        <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-300 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>
