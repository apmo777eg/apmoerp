<div class="p-6">
@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp

    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ __('Branch Modules') }}</h1>
                <p class="text-slate-500">
                    {{ __('Manage enabled modules for') }}: 
                    <span class="font-medium text-emerald-600">{{ $locale === 'ar' && $branch->name_ar ? $branch->name_ar : $branch->name }}</span>
                </p>
            </div>
            <a href="{{ route('admin.branches.index') }}" class="erp-btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Back to Branches') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-semibold text-slate-800">{{ __('Available Modules') }}</h3>
                <p class="text-sm text-slate-500">{{ __('Enable or disable modules for this branch') }}</p>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($modules as $module)
                        <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all {{ ($enabledModules[$module['id']] ?? false) ? 'bg-emerald-50 border-emerald-200' : 'bg-white' }}">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    wire:click="toggleModule({{ $module['id'] }})"
                                    {{ ($enabledModules[$module['id']] ?? false) ? 'checked' : '' }}
                                    class="erp-checkbox mt-1"
                                >
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if (!empty($module['icon']))
                                            <span class="text-xl">{{ $module['icon'] }}</span>
                                        @endif
                                        <span class="font-medium text-slate-800">
                                            {{ $locale === 'ar' && !empty($module['name_ar']) ? $module['name_ar'] : $module['name'] }}
                                        </span>
                                    </div>
                                    @if (!empty($module['description']))
                                        <p class="text-sm text-slate-500 mt-1">{{ $module['description'] }}</p>
                                    @endif
                                    <span class="inline-block mt-2 px-2 py-0.5 bg-slate-100 text-slate-600 text-xs rounded font-mono">
                                        {{ $module['key'] }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12 text-slate-400">
                            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <p class="text-lg font-medium mb-2">{{ __('No modules available') }}</p>
                            <p class="text-sm">{{ __('Add modules from the Modules management page first') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                <button type="submit" class="erp-btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Save Changes') }}
                </button>
            </div>
        </div>
    </form>

    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>
                <h4 class="font-medium text-blue-800">{{ __('About Branch Modules') }}</h4>
                <p class="text-sm text-blue-700 mt-1">
                    {{ __('Enabled modules will be available for users assigned to this branch. Disabling a module hides its menu items and blocks access to its features.') }}
                </p>
            </div>
        </div>
    </div>
</div>