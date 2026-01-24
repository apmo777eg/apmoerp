<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ __('Compare Branches') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ __('Compare module configurations between two branches.') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.branches.index') }}"
               class="erp-btn-secondary inline-flex items-center gap-2" wire:navigate>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('Back to Branches') }}
            </a>
        </div>
    </div>

    {{-- Branch Selection --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-3">
            {{-- Branch 1 --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    {{ __('First Branch') }}
                </label>
                <select wire:model.live="branch1Id" class="erp-input w-full rounded-xl">
                    <option value="">{{ __('Select Branch') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Branch 2 --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    {{ __('Second Branch') }}
                </label>
                <select wire:model.live="branch2Id" class="erp-input w-full rounded-xl">
                    <option value="">{{ __('Select Branch') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Compare Button --}}
            <div class="flex items-end">
                <button wire:click="compare" 
                        class="erp-btn-primary w-full"
                        @if(!$branch1Id || !$branch2Id) disabled aria-disabled="true" @endif>
                    {{ __('Compare') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Comparison Results --}}
    @if(!empty($comparison))
        <div class="grid gap-4 md:grid-cols-2">
            {{-- Branch 1 Modules --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">
                    {{ $comparison['branch1']['name'] ?? __('Branch 1') }}
                    <span class="text-sm font-normal text-slate-500">({{ $comparison['branch1']['count'] ?? 0 }} {{ __('modules') }})</span>
                </h3>
                <div class="space-y-2">
                    @foreach($comparison['branch1']['modules'] ?? [] as $module)
                        <div class="flex items-center gap-2 text-sm">
                            @if(in_array($module, $comparison['common'] ?? []))
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @endif
                            <span class="text-slate-700 dark:text-slate-300">{{ $module }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Branch 2 Modules --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">
                    {{ $comparison['branch2']['name'] ?? __('Branch 2') }}
                    <span class="text-sm font-normal text-slate-500">({{ $comparison['branch2']['count'] ?? 0 }} {{ __('modules') }})</span>
                </h3>
                <div class="space-y-2">
                    @foreach($comparison['branch2']['modules'] ?? [] as $module)
                        <div class="flex items-center gap-2 text-sm">
                            @if(in_array($module, $comparison['common'] ?? []))
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @endif
                            <span class="text-slate-700 dark:text-slate-300">{{ $module }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sync Actions --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">
                {{ __('Sync Modules') }}
            </h3>
            <div class="flex gap-4">
                <button wire:click="syncModules('to_branch1')" 
                        class="erp-btn-secondary"
                        wire:confirm="{{ __('Are you sure you want to sync modules to the first branch?') }}">
                    {{ __('Sync to') }} {{ $comparison['branch1']['name'] ?? __('Branch 1') }}
                </button>
                <button wire:click="syncModules('to_branch2')" 
                        class="erp-btn-secondary"
                        wire:confirm="{{ __('Are you sure you want to sync modules to the second branch?') }}">
                    {{ __('Sync to') }} {{ $comparison['branch2']['name'] ?? __('Branch 2') }}
                </button>
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-6 text-sm text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ __('Common module') }}
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                {{ __('Unique to branch') }}
            </div>
        </div>
    @endif
</div>
