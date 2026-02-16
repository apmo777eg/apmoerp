{{-- resources/views/components/reports/saved-views.blade.php --}}
@props([
    'reportType' => 'general',
])

@php
// Check if SavedReportView model exists
$savedViews = collect([]);
if (class_exists('\App\Models\SavedReportView')) {
    $savedViews = \App\Models\SavedReportView::where('user_id', auth()->id())
        ->where('report_type', $reportType)
        ->latest()
        ->get();
}
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4']) }}>
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ __('Saved Views') }}
        </h4>
        <button type="button" 
            wire:click="saveCurrentView"
            class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-500 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            {{ __('Save Current View') }}
        </button>
    </div>

    @if($savedViews->isEmpty())
    <p class="text-xs text-slate-500 dark:text-slate-400 text-center py-4">
        {{ __('No saved views yet') }}
    </p>
    @else
    <div class="space-y-2">
        @foreach($savedViews as $view)
        <div class="flex items-center justify-between p-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg transition cursor-pointer"
             wire:click="loadView({{ $view->id }})">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                        {{ $view->name }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $view->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            <button type="button" 
                wire:click.stop="deleteView({{ $view->id }})"
                class="flex-shrink-0 text-red-600 hover:text-red-700 dark:text-red-500 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
        @endforeach
    </div>
    @endif
</div>
