@props([
    'message' => null,
    'dismissible' => true,
    'autoDismiss' => 5000,
])

@if($message || session('success') || session('status'))
<div 
    x-data="{ 
        show: true,
        init() {
            @if($autoDismiss)
            setTimeout(() => this.show = false, {{ $autoDismiss }})
            @endif
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform -translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => 'rounded-xl bg-emerald-50 border border-emerald-200 p-4 shadow-sm shadow-emerald-500/10']) }}
>
    <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="flex-1 text-sm text-emerald-700">
            {{ $message ?? session('success') ?? session('status') }}
        </p>
        @if($dismissible)
        <button type="button" @click="show = false" class="flex-shrink-0 text-emerald-400 hover:text-emerald-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        @endif
    </div>
</div>
@endif
