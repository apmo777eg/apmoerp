{{-- 
    Improved Modal Component - Non-blocking popup card style
    Usage:
    @if($showModal)
        <x-modal wire:click.self="closeModal">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white">
                <h3 class="text-lg font-semibold">Modal Title</h3>
            </div>
            <div class="p-6">
                Modal content...
            </div>
        </x-modal>
    @endif
    
    Features:
    - No backdrop overlay (allows page scrolling)
    - High z-index (9000+)
    - Centered popup card
    - Click outside to close still works
    - Page remains scrollable
--}}

@props([
    'maxWidth' => 'lg',
])

@php
$maxWidthClass = match($maxWidth) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
    '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl',
    '6xl' => 'max-w-6xl',
    '7xl' => 'max-w-7xl',
    default => 'max-w-lg',
};
@endphp

<div
    {{ $attributes->merge(['class' => 'fixed inset-0 flex items-center justify-center p-4 overflow-y-auto']) }}
    style="z-index: 9000;"
    role="dialog"
    aria-modal="true"
>
    <div
        class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full {{ $maxWidthClass }} mx-auto my-auto max-h-[90vh] overflow-y-auto border-2 border-emerald-500/30"
        style="z-index: 9001;"
    >
        {{ $slot }}
    </div>
</div>
