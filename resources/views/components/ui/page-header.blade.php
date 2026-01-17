{{-- resources/views/components/ui/page-header.blade.php --}}
@props([
    'title',
    'subtitle' => null,
    'actionUrl' => null,
    'actionLabel' => null,
    'actionVariant' => 'primary',
    'breadcrumbs' => [],
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    {{-- Breadcrumbs --}}
    @if(!empty($breadcrumbs))
    <nav class="flex mb-3" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 rtl:space-x-reverse">
            @foreach($breadcrumbs as $index => $crumb)
            <li class="inline-flex items-center">
                @if($index > 0)
                <svg class="w-3 h-3 mx-1 text-slate-400 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                @endif
                
                @if(isset($crumb['url']) && $crumb['url'])
                <a href="{{ $crumb['url'] }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-500">
                    {{ $crumb['label'] }}
                </a>
                @else
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    {{ $crumb['label'] }}
                </span>
                @endif
            </li>
            @endforeach
        </ol>
    </nav>
    @endif

    {{-- Header Content --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-start gap-3">
            @if($icon)
            <div class="flex-shrink-0 text-4xl">
                {!! sanitize_svg_icon($icon) !!}
            </div>
            @endif
            
            <div class="flex-1 min-w-0">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100">
                    {{ $title }}
                </h1>
                
                @if($subtitle)
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ $subtitle }}
                </p>
                @endif
            </div>
        </div>

        {{-- Action Button --}}
        @if($actionUrl && $actionLabel)
        <div class="flex-shrink-0">
            <x-ui.button :href="$actionUrl" :variant="$actionVariant">
                {{ $actionLabel }}
            </x-ui.button>
        </div>
        @endif

        {{-- Custom Actions Slot --}}
        @if(isset($actions))
        <div class="flex-shrink-0 flex items-center gap-2">
            {{ $actions }}
        </div>
        @endif
    </div>

    {{-- Additional Content Slot --}}
    @if($slot->isNotEmpty())
    <div class="mt-4">
        {{ $slot }}
    </div>
    @endif
</div>
