{{-- resources/views/components/ui/card.blade.php --}}
@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'actions' => null,
    'noPadding' => false,
    'loading' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700']) }}>
    @if($title || $actions || $subtitle)
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-3">
            @if($icon)
            <div class="flex-shrink-0">
                {!! sanitize_svg_icon($icon) !!}
            </div>
            @endif
            
            <div>
                @if($title)
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $title }}
                </h3>
                @endif
                
                @if($subtitle)
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    {{ $subtitle }}
                </p>
                @endif
            </div>
        </div>
        
        @if($actions)
        <div class="flex items-center gap-2">
            {{-- Actions are developer-controlled slot content (buttons, links), not user input --}}
            {!! $actions !!}
        </div>
        @endif
    </div>
    @endif
    
    <div class="{{ $noPadding ? '' : 'p-6' }}">
        @if($loading)
        <div class="flex items-center justify-center py-12">
            <x-loading-indicator />
        </div>
        @else
        {{ $slot }}
        @endif
    </div>
</div>
