{{-- Branch Switcher Component - Fixed Position (Not Floating) --}}
@if($canSwitch && is_array($branches) && count($branches) > 0)
<div class="px-3 py-3 border-b border-slate-700/50 bg-slate-800/30">
    {{-- Header with Role indicator --}}
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            {{ __('Branch Context') }}
        </span>
        @if(!$selectedBranchId)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-900/30 text-purple-300">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ __('Super Admin') }}
            </span>
        @endif
    </div>
    
    {{-- Branch Selector - Fixed/Inline Dropdown --}}
    <div x-data="{ open: false }">
        <button 
            @click="open = !open"
            type="button"
            class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg border transition-all
                   {{ $selectedBranchId 
                      ? 'border-emerald-600 bg-emerald-900/20 text-emerald-300' 
                      : 'border-slate-600 bg-slate-700 text-slate-200' }}
                   hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
        >
            <span class="flex items-center gap-2">
                @if($selectedBranch && is_object($selectedBranch))
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="font-medium truncate">{{ $selectedBranch->name ?? '' }}</span>
                    @if(!empty($selectedBranch->code))
                        <span class="text-xs opacity-60">({{ $selectedBranch->code }})</span>
                    @endif
                @else
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ __('All Branches') }}</span>
                @endif
            </span>
            <svg class="w-4 h-4 text-slate-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        
        {{-- Inline Branch Options (Not floating/absolute) --}}
        <div 
            x-show="open" 
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="mt-2 rounded-lg bg-slate-800 border border-slate-700 overflow-hidden max-h-48 overflow-y-auto"
            style="display: none;"
        >
            {{-- All Branches Option --}}
            <button 
                wire:click="switchBranch(null)"
                @click="open = false"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-start hover:bg-slate-700/50 transition-colors
                       {{ !$selectedBranchId ? 'bg-purple-900/30 border-s-2 border-purple-500' : '' }}"
            >
                <div class="w-6 h-6 rounded-full bg-slate-700 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-200 text-xs">{{ __('All Branches') }}</p>
                </div>
                @if(!$selectedBranchId)
                    <svg class="w-4 h-4 text-purple-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </button>
            
            {{-- Branch Options --}}
            @if(is_array($branches))
                @foreach($branches as $branch)
                    <button 
                        wire:click="switchBranch({{ $branch['id'] ?? 0 }})"
                        @click="open = false"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-start hover:bg-slate-700/50 transition-colors border-t border-slate-700/50
                               {{ $selectedBranchId == ($branch['id'] ?? 0) ? 'bg-emerald-900/30 border-s-2 border-emerald-500' : '' }}"
                    >
                        <div class="w-6 h-6 rounded-full bg-emerald-900/50 flex items-center justify-center flex-shrink-0">
                            <span class="text-[10px] font-bold text-emerald-400">
                                {{ strtoupper(substr($branch['name'] ?? 'N/A', 0, 2)) }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-200 text-xs truncate">{{ $branch['name'] ?? 'N/A' }}</p>
                            @if(!empty($branch['code']))
                                <p class="text-[10px] text-slate-500">{{ $branch['code'] }}</p>
                            @endif
                        </div>
                        @if($selectedBranchId == ($branch['id'] ?? 0))
                            <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </button>
                @endforeach
            @endif
        </div>
    </div>
    
    {{-- Context Info --}}
    @if($selectedBranch && is_object($selectedBranch))
        <div class="mt-2 p-2 rounded-lg bg-emerald-900/20 border border-emerald-800/50">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <p class="text-[10px] text-emerald-300 flex-1">
                    {{ __('Viewing branch perspective') }}
                </p>
                <button 
                    wire:click="switchBranch(null)"
                    class="text-[10px] font-medium text-emerald-400 hover:text-emerald-300 hover:underline"
                >
                    {{ __('Exit') }}
                </button>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Livewire 4 FIX: Wrap with @script for proper execution timing
    Livewire.on('branch-switched', () => {
        // Use Livewire.navigate for SPA-friendly navigation instead of full reload
        // This refreshes the page content while preserving SPA state
        Livewire.navigate(window.location.href);
    });
</script>
@endscript
@endif
