<div class="space-y-4 max-w-xl">
    <div>
        <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
            {{ __('Run payroll') }}
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ __('Generate draft payroll entries for all employees in the current branch for a selected period.') }}
        </p>
    </div>

    <form wire:submit.prevent="runPayroll" class="space-y-4">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                {{ __('Period (month)') }}
            </label>
            <input type="month" wire:model="period" class="erp-input w-48">
            @error('period')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label class="inline-flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
                <input type="checkbox" wire:model="includeInactive"
                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>{{ __('Include inactive employees') }}</span>
            </label>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('app.hrm.payroll.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ __('Run payroll') }}
            </button>
        </div>
    </form>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
            {{ session('error') }}
        </div>
    @endif
</div>
