<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ __('Module Management') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Manage system modules and their settings') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.modules.product-fields') }}" class="erp-btn-secondary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                {{ __('Product Fields') }}
            </a>
            <a href="{{ route('admin.modules.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Add Module') }}
            </a>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search modules...') }}" class="erp-input max-w-md">
        </div>

        @if(session()->has('success'))
            <div class="mb-4 p-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 p-3 rounded-xl border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($modules as $module)
                <div class="border border-slate-200 dark:border-slate-700 rounded-2xl p-4 bg-white/80 dark:bg-slate-900/60 backdrop-blur-sm hover:shadow-lg transition-all duration-200 {{ !$module->is_active ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ $module->display_icon }}</span>
                            <div>
                                <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ $module->localized_name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $module->module_key }}</p>
                            </div>
                        </div>
                        @if($module->is_core)
                            <span class="px-2 py-0.5 text-xs bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-200 rounded-full">{{ __('Core') }}</span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">{{ $module->localized_description ?? __('No description') }}</p>
                    
                    {{-- Module Type Badge --}}
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $module->getModuleTypeColor() }}">
                            {{ $module->getModuleTypeLabel() }}
                        </span>
                        @if($module->supports_items)
                            <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-200 rounded-full">
                                {{ __('Creates Items') }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $module->branches_count }} {{ __('branches') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="toggleActive({{ $module->id }})" class="p-1.5 rounded-lg {{ $module->is_active ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10' : 'text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}" title="{{ $module->is_active ? __('Deactivate') : __('Activate') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($module->is_active)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    @endif
                                </svg>
                            </button>
                            <a href="{{ route('admin.modules.edit', $module) }}" class="p-1.5 rounded-lg text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-500/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-8 text-slate-500 dark:text-slate-400">{{ __('No modules found') }}</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $modules->links() }}
        </div>
    </div>
</div>
