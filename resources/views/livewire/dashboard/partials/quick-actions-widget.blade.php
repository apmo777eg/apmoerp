<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
    {{-- New Sale --}}
    @can('pos.use')
    <a href="{{ route('pos.terminal') }}" class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-pink-200 dark:hover:border-pink-600 transition-all duration-300">
        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-pink-100 to-pink-50 dark:from-pink-900/30 dark:to-pink-800/20 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">{{ __('New Sale') }}</span>
    </a>
    @endcan

    {{-- Sales Report --}}
    @can('reports.view')
    <a href="{{ route('admin.reports.index') }}" class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-600 transition-all duration-300">
        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-800/20 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">{{ __('Sales Report') }}</span>
    </a>
    @endcan

    {{-- Inventory --}}
    @can('inventory.products.view')
    <a href="{{ route('app.inventory.products.index') }}" class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-green-200 dark:hover:border-green-600 transition-all duration-300">
        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-green-100 to-green-50 dark:from-green-900/30 dark:to-green-800/20 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">{{ __('Inventory') }}</span>
    </a>
    @endcan

    {{-- Employees --}}
    @can('hrm.employees.view')
    <a href="{{ route('app.hrm.employees.index') }}" class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-rose-200 dark:hover:border-rose-600 transition-all duration-300">
        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-rose-100 to-rose-50 dark:from-rose-900/30 dark:to-rose-800/20 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">{{ __('Employees') }}</span>
    </a>
    @endcan

    {{-- Settings --}}
    @can('settings.view')
    <a href="{{ route('admin.settings') }}" class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-gray-200 dark:hover:border-gray-600 transition-all duration-300">
        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-900/30 dark:to-gray-800/20 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">{{ __('Settings') }}</span>
    </a>
    @endcan

    {{-- Backup --}}
    @can('settings.manage')
    <a href="{{ route('admin.backup') }}" class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-lg hover:border-purple-200 dark:hover:border-purple-600 transition-all duration-300">
        <div class="w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-900/30 dark:to-purple-800/20 group-hover:scale-110 transition-transform">
            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">{{ __('Backup') }}</span>
    </a>
    @endcan
</div>
