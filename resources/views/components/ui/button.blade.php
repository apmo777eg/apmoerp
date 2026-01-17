{{-- resources/views/components/ui/button.blade.php --}}
@props([
    'variant' => 'primary', // primary, secondary, danger, ghost, success, warning
    'size' => 'md', // sm, md, lg
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'loading' => false,
    'type' => 'button',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 shadow-sm disabled:cursor-not-allowed disabled:opacity-60 w-full sm:w-auto min-h-[44px]';

$variantClasses = match($variant) {
    'primary' => 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500 shadow-emerald-500/20 hover:shadow-md',
    'secondary' => 'bg-slate-200 hover:bg-slate-300 text-slate-900 focus:ring-slate-500 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-100 shadow-slate-400/20 hover:shadow-md',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 shadow-red-500/20 hover:shadow-md',
    'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500 shadow-green-500/20 hover:shadow-md',
    'warning' => 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500 shadow-amber-500/20 hover:shadow-md',
    'ghost' => 'bg-transparent hover:bg-slate-100 text-slate-700 focus:ring-slate-500 dark:hover:bg-slate-800 dark:text-slate-300 shadow-none hover:shadow-sm',
    default => 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500 shadow-emerald-500/20 hover:shadow-md',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm gap-1.5',
    'md' => 'px-4 py-2 text-base gap-2',
    'lg' => 'px-6 py-3 text-lg gap-2.5',
    default => 'px-4 py-2 text-base gap-2',
};

$classes = "$baseClasses $variantClasses $sizeClasses";
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($loading)
    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @else
        @if($icon && $iconPosition === 'left')
        {!! sanitize_svg_icon($icon) !!}
        @endif
        
        {{ $slot }}
        
        @if($icon && $iconPosition === 'right')
        {!! sanitize_svg_icon($icon) !!}
        @endif
    @endif
</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} {{ $loading ? 'disabled' : '' }}>
    @if($loading)
    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @else
        @if($icon && $iconPosition === 'left')
        {!! sanitize_svg_icon($icon) !!}
        @endif
        
        {{ $slot }}
        
        @if($icon && $iconPosition === 'right')
        {!! sanitize_svg_icon($icon) !!}
        @endif
    @endif
</button>
@endif
