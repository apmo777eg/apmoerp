@props([
    'title',
    'titleAr' => null,
    'icon' => '📁',
    'routes' => [],
    'permission' => null,
    'sectionKey' => null,
    'gradient' => 'from-slate-600 to-slate-700'
])

@php
    $locale = app()->getLocale();
    $displayTitle = $locale === 'ar' && $titleAr ? $titleAr : $title;
    $currentRoute = request()->route()?->getName() ?? '';
    
    // Check if any child route is active
    $hasActiveChild = false;
    foreach ($routes as $route) {
        if (str_starts_with($currentRoute, $route)) {
            $hasActiveChild = true;
            break;
        }
    }
    
    // Check permission
    $user = auth()->user();
    $canView = true;
    if ($permission) {
        $canView = $user && ($user->hasRole('Super Admin') || $user->can($permission));
    }
    
    // Generate unique section key for Alpine state
    $storageKey = $sectionKey ?? 'sidebar_' . md5($title);
@endphp

@if($canView)
<div x-data="{ 
    open: (() => {
        const stored = localStorage.getItem(@js($storageKey));
        if (stored !== null) return stored === 'true';
        return {{ $hasActiveChild ? 'true' : 'false' }};
    })(),
    hasActive: {{ $hasActiveChild ? 'true' : 'false' }}
}" 
x-init="$watch('open', value => localStorage.setItem(@js($storageKey), value))"
class="space-y-1">
    <button type="button" 
        @click="open = !open"
        class="sidebar-link bg-gradient-to-r {{ $gradient }} w-full {{ $hasActiveChild ? 'ring-2 ring-white/30' : '' }}"
    >
        <span class="text-lg">{{ $icon }}</span>
        <span class="text-sm font-medium flex-1 text-start">{{ $displayTitle }}</span>
        <svg 
            :class="{ 'rotate-180': open }" 
            class="w-4 h-4 transition-transform duration-200" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
        @if($hasActiveChild)
            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
        @endif
    </button>
    
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="ms-4 space-y-0.5">
        {{ $slot }}
    </div>
</div>
@endif
