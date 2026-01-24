<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-200">
                {{ $branchId ? __('Edit Branch') : __('Create Branch') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Basic branch information and module configuration.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Basic Info Section --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{{ __('Basic Information') }}</h2>
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    @livewire('shared.dynamic-form', ['schema' => $schema, 'data' => $form], key('branch-form-' . ($branchId ?? 'new')))
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" wire:model="form.is_active"
                                   class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:bg-slate-700 dark:border-slate-600">
                            <span>{{ __('Active') }}</span>
                        </label>
                        @error('form.is_active')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" wire:model="form.is_main"
                                   class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:bg-slate-700 dark:border-slate-600">
                            <span>{{ __('Main branch') }}</span>
                        </label>
                        @error('form.is_main')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Module Selection Section --}}
        @if(!empty($availableModules))
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">{{ __('Branch Modules') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    {{ __('Select which modules this branch can access. Users assigned to this branch will only see these modules.') }}
                </p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @if(is_array($availableModules))
                        @foreach($availableModules as $module)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                                {{ in_array($module['id'] ?? 0, $selectedModules ?? []) 
                                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-600' 
                                    : 'border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500' }}">
                                <input type="checkbox" 
                                       value="{{ $module['id'] ?? '' }}"
                                       wire:model="selectedModules"
                                       class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-700 dark:border-slate-600">
                                <div class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ $module['name'] ?? '' }}
                                        @if(!empty($module['is_core']))
                                            <span class="text-xs text-emerald-600 dark:text-emerald-400">({{ __('Core') }})</span>
                                        @endif
                                    </span>
                                    @if(!empty($module['description']))
                                        <span class="block text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ $module['description'] }}
                                        </span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.branches.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:bg-slate-700 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-600">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $branchId ? __('Save changes') : __('Create branch') }}
            </button>
        </div>
    </form>
</div>
