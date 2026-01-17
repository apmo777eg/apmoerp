{{-- resources/views/components/ui/empty-state.blade.php --}}
@props([
    'icon' => '📭',
    'title' => __('No data found'),
    'description' => null,
    'action' => null,
    'actionLabel' => null,
    'type' => 'empty', // empty, error
    'onRetry' => null,
])

@php
$iconMap = [
    'empty' => $icon ?? '📭',
    'error' => '⚠️',
];

$displayIcon = $iconMap[$type] ?? $icon;
// Detect SVG-like content using case-insensitive matching
// This handles valid SVG and any potentially malicious content containing SVG tags
$isSvg = is_string($displayIcon) && (
    stripos($displayIcon, '<svg') !== false || 
    stripos($displayIcon, '<?xml') !== false
);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-4']) }}>
    <div class="text-6xl mb-4">
        @if($isSvg)
            {{-- Always sanitize SVG-like content to prevent XSS --}}
            {!! sanitize_svg_icon($displayIcon) !!}
        @else
            {{-- Safe output for emoji or text --}}
            {{ $displayIcon }}
        @endif
    </div>
    
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">
        {{ $title }}
    </h3>
    
    @if($description)
    <p class="text-sm text-slate-500 dark:text-slate-400 text-center max-w-md mb-6">
        {{ $description }}
    </p>
    @endif
    
    <div class="flex items-center gap-3">
        @if($action && $actionLabel)
        <x-ui.button href="{{ $action }}" variant="primary">
            {{ $actionLabel }}
        </x-ui.button>
        @endif
        
        @if($type === 'error' && $onRetry)
        <x-ui.button wire:click="{{ $onRetry }}" variant="secondary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            {{ __('Retry') }}
        </x-ui.button>
        @endif
    </div>
    
    {{ $slot }}
</div>
