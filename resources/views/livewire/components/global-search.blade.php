<div class="relative" x-data="{ open: @entangle('showResults') }">
    {{-- Search Input --}}
    <div class="relative">
        <div class="relative">
            <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}-0 flex items-center {{ app()->getLocale() === 'ar' ? 'pr' : 'pl' }}-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input 
                wire:model.live.debounce.300ms="query"
                type="text" 
                placeholder="{{ __('Search everywhere...') }}"
                class="block w-full {{ app()->getLocale() === 'ar' ? 'pr-10 pl-3' : 'pl-10 pr-3' }} py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @focus="open = true"
                @keydown.escape="open = false"
            />
            @if($query)
                <button type="button" 
                    wire:click="$set('query', '')"
                    class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-0 flex items-center {{ app()->getLocale() === 'ar' ? 'pl' : 'pr' }}-3"
                >
                    <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>

        {{-- Module Filter --}}
        @if(count($this->availableModules) > 0)
            <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" 
                    wire:click="$set('selectedModule', null)"
                    class="px-3 py-1 text-sm rounded-full {{ is_null($selectedModule) ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                >
                    {{ __('All') }}
                </button>
                @foreach($this->availableModules as $module)
                    <button type="button" 
                        wire:click="$set('selectedModule', '{{ $module }}')"
                        class="px-3 py-1 text-sm rounded-full {{ $selectedModule === $module ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                    >
                        {{ __(ucfirst($module)) }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Search Results Dropdown --}}
    <div 
        x-show="open"
        x-transition
        @click.away="open = false"
        class="absolute z-popover mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto"
        style="display: none;"
    >
        @if($query && strlen($query) >= 2)
            @if($totalResults > 0)
                <div class="p-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 px-2">
                        {{ __('Found :count results', ['count' => $totalResults]) }}
                    </div>

                    {{-- Grouped Results --}}
                    @foreach($groupedResults as $module => $items)
                        <div class="mb-3">
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 px-2 mb-1">
                                {{ __(ucfirst($module)) }}
                            </div>
                            @foreach($items as $item)
                                <a 
                                    href="{{ $item['url'] ?? '#' }}"
                                    class="block px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl">{{ $item['icon'] ?? '📄' }}</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                {{ $item['title'] }}
                                            </div>
                                            @if(!empty($item['content']))
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {{ $item['content'] }}
                                                </div>
                                            @endif
                                        </div>
                                        @if(!empty($item['metadata']['status']))
                                            <span class="text-xs px-2 py-1 rounded bg-gray-200 dark:bg-gray-700">
                                                {{ $item['metadata']['status'] }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>{{ __('No results found for ":query"', ['query' => $query]) }}</p>
                </div>
            @endif
        @elseif(count($recentSearches) > 0)
            {{-- Recent Searches --}}
            <div class="p-2">
                <div class="flex items-center justify-between px-2 mb-2">
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('Recent Searches') }}
                    </div>
                    <button type="button" 
                        wire:click="clearHistory"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        {{ __('Clear') }}
                    </button>
                </div>
                @foreach($recentSearches as $recentQuery)
                    <button type="button" 
                        wire:click="useRecentSearch(@js($recentQuery))"
                        class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                    >
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $recentQuery }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-sm">{{ __('Start typing to search...') }}</p>
            </div>
        @endif
    </div>
</div>
