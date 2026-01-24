<div 
    class="relative"
    x-data="{
        query: '',
        results: {},
        total: 0,
        showResults: false,
        isSearching: false,
        requestId: 0,
        debounceTimer: null,
        
        async performSearch() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.results = {};
                this.total = 0;
                this.showResults = false;
                return;
            }
            
            this.isSearching = true;
            const currentRequestId = ++this.requestId;
            
            try {
                const response = await $wire.search(q);
                
                // Prevent out-of-order responses from overwriting newer results
                if (currentRequestId !== this.requestId) return;
                
                this.results = response.results || {};
                this.total = response.total || 0;
                this.showResults = Object.keys(this.results).length > 0 || q.length >= 2;
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                if (currentRequestId === this.requestId) {
                    this.isSearching = false;
                }
            }
        },
        
        debouncedSearch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.performSearch(), 300);
        },
        
        clearSearch() {
            this.query = '';
            this.results = {};
            this.total = 0;
            this.showResults = false;
        },
        
        closeResults() {
            this.showResults = false;
        }
    }"
    @click.outside="closeResults()"
>
    <div class="relative">
        <input type="search" 
               x-model="query"
               @input="debouncedSearch()"
               @focus="query.length >= 2 && (showResults = true)"
               @keydown.escape="closeResults()"
               placeholder="{{ __('Search products, customers, sales...') }}"
               class="w-full erp-input rounded-full ltr:pl-10 rtl:pr-10 ltr:pr-10 rtl:pl-10 bg-white/90 backdrop-blur">
        <svg class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        
        <button type="button" 
                x-show="query.length > 0"
                @click="clearSearch()"
                class="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-slate-600">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        
        <div x-show="isSearching" class="absolute ltr:right-10 rtl:left-10 top-1/2 -translate-y-1/2">
            <svg class="animate-spin h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    <div x-show="showResults"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="absolute z-popover w-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-[70vh] overflow-hidden">
        
        <template x-if="total > 0">
            <div>
                <div class="px-4 py-2 border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-white flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">
                        {{ __('Found') }} <span class="text-emerald-600 font-bold" x-text="total"></span> {{ __('results') }}
                    </span>
                    <span class="text-[10px] text-slate-400">{{ __('Press Escape to close') }}</span>
                </div>

                <div class="overflow-y-auto max-h-[calc(70vh-50px)]">
                    <template x-for="(group, category) in results" :key="category">
                        <div class="border-b border-slate-100 last:border-b-0">
                            <div class="sticky top-0 bg-slate-50/95 backdrop-blur px-4 py-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-base" x-text="group.icon"></span>
                                    <span class="text-xs font-semibold text-slate-700 uppercase tracking-wide" x-text="group.label"></span>
                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full" x-text="group.items?.length || 0"></span>
                                </div>
                                <a :href="group.route + '?search=' + encodeURIComponent(query)" 
                                   class="text-[10px] text-emerald-600 hover:text-emerald-700 hover:underline">
                                    {{ __('View all') }} →
                                </a>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <template x-for="item in group.items" :key="item.id">
                                    <a :href="item.route" 
                                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-emerald-50 transition-colors group">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-800 truncate group-hover:text-emerald-700" x-text="item.title"></p>
                                            <p class="text-xs text-slate-500 truncate" x-text="item.subtitle"></p>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        
        <template x-if="total === 0 && query.length >= 2 && !isSearching">
            <div class="p-8 text-center">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="text-slate-500 font-medium">{{ __('No results found') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ __('Try a different search term') }}</p>
            </div>
        </template>
    </div>
</div>
