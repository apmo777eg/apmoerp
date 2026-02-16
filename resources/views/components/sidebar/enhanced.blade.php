<div 
    x-data="{
        open: true,
        searchQuery: '',
        favorites: JSON.parse(localStorage.getItem('sidebar_favorites') || '[]'),
        collapsed: JSON.parse(localStorage.getItem('sidebar_collapsed') || '{}'),
        recentItems: JSON.parse(localStorage.getItem('sidebar_recent') || '[]'),
        
        toggleSidebar() {
            this.open = !this.open;
            localStorage.setItem('sidebar_open', this.open);
        },
        
        toggleCollapse(key) {
            this.collapsed[key] = !this.collapsed[key];
            localStorage.setItem('sidebar_collapsed', JSON.stringify(this.collapsed));
        },
        
        toggleFavorite(key, label, route, icon) {
            const index = this.favorites.findIndex(f => f.key === key);
            if (index > -1) {
                this.favorites.splice(index, 1);
            } else {
                this.favorites.push({ key, label, route, icon });
            }
            localStorage.setItem('sidebar_favorites', JSON.stringify(this.favorites));
        },
        
        isFavorite(key) {
            return this.favorites.some(f => f.key === key);
        },
        
        addToRecent(key, label, route, icon) {
            this.recentItems = this.recentItems.filter(item => item.key !== key);
            this.recentItems.unshift({ key, label, route, icon });
            if (this.recentItems.length > 5) {
                this.recentItems = this.recentItems.slice(0, 5);
            }
            localStorage.setItem('sidebar_recent', JSON.stringify(this.recentItems));
        },
        
        matchesSearch(label) {
            if (!this.searchQuery) return true;
            return label.toLowerCase().includes(this.searchQuery.toLowerCase());
        }
    }"
    x-init="open = localStorage.getItem('sidebar_open') !== 'false'"
    class="h-full flex flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700"
>
    <!-- Header with Logo and Toggle -->
    <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                <span class="text-2xl">🏢</span>
                <h2 x-show="open" class="text-xl font-bold text-gray-900 dark:text-white">
                    HugousERP
                </h2>
            </div>
            <button type="button" 
                @click="toggleSidebar"
                class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                title="{{ __('Toggle Sidebar') }}"
            >
                <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        
        <!-- Search -->
        <div x-show="open" class="mt-3">
            <div class="relative">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    placeholder="{{ __('Search menu...') }}"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 rtl:left-auto rtl:right-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Navigation Content -->
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <!-- Quick Actions -->
        <div x-show="open && !searchQuery" class="mb-6">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-3">
                {{ __('Quick Actions') }}
            </div>
            <div class="space-y-1">
                @can('pos.use')
                <a 
                    href="{{ route('pos.terminal') }}"
                    class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700"
                >
                    <span class="text-lg">🧾</span>
                    <span class="ml-3 rtl:mr-3 rtl:ml-0">{{ __('New Sale') }}</span>
                </a>
                @endcan
                
                @can('inventory.products.create')
                <a 
                    href="{{ route('app.inventory.products.create') }}"
                    class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700"
                >
                    <span class="text-lg">📦</span>
                    <span class="ml-3 rtl:mr-3 rtl:ml-0">{{ __('New Product') }}</span>
                </a>
                @endcan
            </div>
        </div>

        <!-- Favorites -->
        <div x-show="open && !searchQuery && favorites.length > 0" class="mb-6">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-3">
                {{ __('Favorites') }}
            </div>
            <div class="space-y-1">
                <template x-for="fav in favorites" :key="fav.key">
                    <a 
                        :href="fav.route"
                        class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                        @click="addToRecent(fav.key, fav.label, fav.route, fav.icon)"
                    >
                        <span x-text="fav.icon"></span>
                        <span class="ml-3 rtl:mr-3 rtl:ml-0" x-text="fav.label"></span>
                    </a>
                </template>
            </div>
        </div>

        <!-- Main Navigation -->
        {{ $slot }}
    </nav>

    <!-- Keyboard Shortcuts Info -->
    <div x-show="open" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center justify-between mb-1">
                <span>{{ __('Keyboard Shortcuts') }}</span>
                <button type="button" 
                    @click="$dispatch('show-shortcuts')"
                    class="text-blue-600 hover:text-blue-700"
                >
                    {{ __('View All') }}
                </button>
            </div>
            <div class="space-y-0.5">
                <div><kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+K</kbd> {{ __('Search') }}</div>
                <div><kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+B</kbd> {{ __('Toggle Sidebar') }}</div>
            </div>
        </div>
    </div>
</div>

<script>
// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+K or Cmd+K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[placeholder*="Search"]');
        if (searchInput) searchInput.focus();
    }
    
    // Ctrl+B or Cmd+B for toggle sidebar
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        window.dispatchEvent(new Event('toggle-sidebar'));
    }
});
</script>

<style>
.sidebar-transition {
    transition: width 0.3s ease-in-out;
}
</style>
