{{--
    Enhanced Toast Notification Component
    Features:
    - Multiple toast types (success, error, warning, info)
    - Auto-dismiss with configurable duration
    - Optional action buttons
    - RTL/LTR support
    - Stacking support
--}}
@props([
    'position' => 'top-right'
])

@php
$positionClasses = match($position) {
    'top-left' => 'top-4 start-4',
    'top-center' => 'top-4 start-1/2 -translate-x-1/2',
    'bottom-right' => 'bottom-4 end-4',
    'bottom-left' => 'bottom-4 start-4',
    'bottom-center' => 'bottom-4 start-1/2 -translate-x-1/2',
    default => 'top-4 end-4', // top-right
};
@endphp

<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now();
            this.toasts.push({ ...toast, id });
            
            if (toast.duration !== 0) {
                setTimeout(() => this.remove(id), toast.duration || 5000);
            }
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast.window="add($event.detail[0] || $event.detail)"
    class="fixed {{ $positionClasses }} z-50 flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="pointer-events-auto w-80 max-w-full rounded-xl shadow-lg border backdrop-blur-sm"
            :class="{
                'bg-emerald-50/95 border-emerald-200 dark:bg-emerald-900/95 dark:border-emerald-700': toast.type === 'success',
                'bg-red-50/95 border-red-200 dark:bg-red-900/95 dark:border-red-700': toast.type === 'error',
                'bg-amber-50/95 border-amber-200 dark:bg-amber-900/95 dark:border-amber-700': toast.type === 'warning',
                'bg-blue-50/95 border-blue-200 dark:bg-blue-900/95 dark:border-blue-700': toast.type === 'info',
                'bg-white/95 border-slate-200 dark:bg-slate-800/95 dark:border-slate-700': !toast.type
            }"
        >
            <div class="flex items-start gap-3 p-4">
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    {{-- Success Icon --}}
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    {{-- Error Icon --}}
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    {{-- Warning Icon --}}
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    {{-- Info Icon --}}
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p 
                        class="text-sm font-medium"
                        :class="{
                            'text-emerald-800 dark:text-emerald-200': toast.type === 'success',
                            'text-red-800 dark:text-red-200': toast.type === 'error',
                            'text-amber-800 dark:text-amber-200': toast.type === 'warning',
                            'text-blue-800 dark:text-blue-200': toast.type === 'info',
                            'text-slate-800 dark:text-slate-200': !toast.type
                        }"
                        x-text="toast.message"
                    ></p>
                    <p 
                        x-show="toast.details"
                        class="mt-1 text-xs"
                        :class="{
                            'text-emerald-600 dark:text-emerald-300': toast.type === 'success',
                            'text-red-600 dark:text-red-300': toast.type === 'error',
                            'text-amber-600 dark:text-amber-300': toast.type === 'warning',
                            'text-blue-600 dark:text-blue-300': toast.type === 'info',
                            'text-slate-600 dark:text-slate-300': !toast.type
                        }"
                        x-text="toast.details"
                    ></p>
                    
                    {{-- Action Button --}}
                    <template x-if="toast.action && toast.actionUrl">
                        <a 
                            :href="toast.actionUrl"
                            class="inline-flex items-center gap-1 mt-2 text-xs font-medium underline underline-offset-2"
                            :class="{
                                'text-emerald-600 hover:text-emerald-700 dark:text-emerald-400': toast.type === 'success',
                                'text-red-600 hover:text-red-700 dark:text-red-400': toast.type === 'error',
                                'text-amber-600 hover:text-amber-700 dark:text-amber-400': toast.type === 'warning',
                                'text-blue-600 hover:text-blue-700 dark:text-blue-400': toast.type === 'info'
                            }"
                            x-text="toast.action"
                        ></a>
                    </template>
                </div>

                {{-- Close Button --}}
                <button type="button" aria-label="Close"
                    @click="remove(toast.id)"
                    class="flex-shrink-0 p-1 rounded-lg transition-colors"
                    :class="{
                        'text-emerald-400 hover:text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-800': toast.type === 'success',
                        'text-red-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-800': toast.type === 'error',
                        'text-amber-400 hover:text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-800': toast.type === 'warning',
                        'text-blue-400 hover:text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-800': toast.type === 'info',
                        'text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700': !toast.type
                    }"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Progress Bar (for auto-dismiss) --}}
            <template x-if="toast.duration && toast.duration > 0">
                <div class="h-1 bg-black/5 dark:bg-white/5 rounded-b-xl overflow-hidden">
                    <div 
                        class="h-full transition-all ease-linear"
                        :class="{
                            'bg-emerald-500': toast.type === 'success',
                            'bg-red-500': toast.type === 'error',
                            'bg-amber-500': toast.type === 'warning',
                            'bg-blue-500': toast.type === 'info',
                            'bg-slate-500': !toast.type
                        }"
                        :style="{ 
                            width: '100%',
                            animation: `shrink ${toast.duration}ms linear forwards`
                        }"
                    ></div>
                </div>
            </template>
        </div>
    </template>
</div>

<style>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
</style>
