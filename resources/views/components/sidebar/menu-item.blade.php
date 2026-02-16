@props([
    'navKey',
    'label',
    'labelAr' => null,
    'icon' => '📄',
    'route' => null,
    'children' => [],
    'permissions' => [],
    'badge' => null,
    'badgeColor' => 'blue',
])

@php
    $hasChildren = !empty($children);
    $displayLabel = app()->getLocale() === 'ar' && $labelAr ? $labelAr : $label;
    $isActive = $route && request()->routeIs($route . '*');
    
    // Check permissions
    $hasPermission = empty($permissions);
    if (!empty($permissions)) {
        foreach ($permissions as $permission) {
            if (auth()->user()->can($permission)) {
                $hasPermission = true;
                break;
            }
        }
    }
@endphp

@if($hasPermission)
<div 
    x-data="{ 
        expanded: {{ $hasChildren ? 'false' : 'true' }},
        key: '{{ $navKey }}'
    }"
    x-init="expanded = !collapsed[key]"
>
    <div class="relative group">
        @if($hasChildren)
            <button type="button"
                @click="expanded = !expanded; toggleCollapse(key)"
                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors
                    {{ $isActive 
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' 
                    }}"
            >
                <div class="flex items-center min-w-0 flex-1">
                    <span class="text-lg flex-shrink-0">{{ $icon }}</span>
                    <span class="ml-3 rtl:mr-3 rtl:ml-0 truncate" x-show="open">{{ $displayLabel }}</span>
                    @if($badge)
                    <span class="ml-auto rtl:mr-auto rtl:ml-0 px-2 py-0.5 text-xs rounded-full bg-{{ $badgeColor }}-100 text-{{ $badgeColor }}-700" x-show="open">
                        {{ $badge }}
                    </span>
                    @endif
                </div>
                <svg 
                    x-show="open"
                    class="w-4 h-4 ml-2 rtl:mr-2 rtl:ml-0 transition-transform"
                    :class="{ 'rotate-180': expanded }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        @else
            <a
                href="{{ route($route) }}"
                @click="addToRecent('{{ $navKey }}', '{{ $displayLabel }}', '{{ route($route) }}', '{{ $icon }}')"
                class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors
                    {{ $isActive 
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' 
                    }}"
            >
                <div class="flex items-center min-w-0 flex-1">
                    <span class="text-lg flex-shrink-0">{{ $icon }}</span>
                    <span class="ml-3 rtl:mr-3 rtl:ml-0 truncate" x-show="open">{{ $displayLabel }}</span>
                    @if($badge)
                    <span class="ml-auto rtl:mr-auto rtl:ml-0 px-2 py-0.5 text-xs rounded-full bg-{{ $badgeColor }}-100 text-{{ $badgeColor }}-700" x-show="open">
                        {{ $badge }}
                    </span>
                    @endif
                </div>
            </a>
        @endif
        
        <!-- Favorite Toggle Button -->
        <button type="button"
            x-show="open"
            @click.stop="toggleFavorite('{{ $navKey }}', '{{ $displayLabel }}', '{{ $route ? route($route) : '#' }}', '{{ $icon }}')"
            class="absolute right-1 top-1/2 -translate-y-1/2 p-1 rounded opacity-0 group-hover:opacity-100 transition-opacity
                {{ app()->getLocale() === 'ar' ? 'right-auto left-1' : '' }}"
            :class="{ 'text-yellow-500': isFavorite('{{ $navKey }}'), 'text-gray-400 hover:text-yellow-500': !isFavorite('{{ $navKey }}') }"
        >
            <svg class="w-4 h-4" :fill="isFavorite('{{ $navKey }}') ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
        </button>
    </div>
    
    <!-- Children -->
    @if($hasChildren)
    <div 
        x-show="expanded && open" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-96"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 max-h-96"
        x-transition:leave-end="opacity-0 max-h-0"
        class="ml-4 rtl:mr-4 rtl:ml-0 mt-1 space-y-1 overflow-hidden"
    >
        @foreach($children as $child)
            @php
                $childHasPermission = empty($child['permissions'] ?? []);
                if (!empty($child['permissions'] ?? [])) {
                    foreach ($child['permissions'] as $perm) {
                        if (auth()->user()->can($perm)) {
                            $childHasPermission = true;
                            break;
                        }
                    }
                }
            @endphp
            
            @if($childHasPermission)
            <a
                href="{{ route($child['route']) }}"
                @click="addToRecent('{{ $child['key'] }}', '{{ $child['label'] }}', '{{ route($child['route']) }}', '{{ $child['icon'] ?? '📄' }}')"
                class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors
                    {{ request()->routeIs($child['route'] . '*') 
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' 
                    }}"
            >
                <span class="text-base">{{ $child['icon'] ?? '•' }}</span>
                <span class="ml-3 rtl:mr-3 rtl:ml-0 truncate">
                    {{ app()->getLocale() === 'ar' && isset($child['label_ar']) ? $child['label_ar'] : $child['label'] }}
                </span>
            </a>
            @endif
        @endforeach
    </div>
    @endif
</div>
@endif
