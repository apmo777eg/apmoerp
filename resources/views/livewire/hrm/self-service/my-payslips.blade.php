<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('My Payslips') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View your salary history') }}</p>
        </div>
    </div>

    {{-- YTD Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('YTD Gross Earnings') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($ytdSummary['gross_earnings'], 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('YTD Deductions') }}</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($ytdSummary['total_deductions'], 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('YTD Net Salary') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($ytdSummary['net_salary'], 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Year') }}</label>
            <select wire:model.live="year" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                @foreach($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Month') }}</label>
            <select wire:model.live="month" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">{{ __('All') }}</option>
                @foreach($months as $m => $name)
                    <option value="{{ $m }}">{{ __($name) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Payslips Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($records as $payroll)
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $payroll->pay_period_start?->format('F Y') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $payroll->pay_period_start?->format('d M') }} - {{ $payroll->pay_period_end?->format('d M') }}
                        </p>
                    </div>
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                        @if($payroll->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif(in_array($payroll->status, ['draft', 'calculated', 'approved'])) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                        @endif">
                        {{ __(ucfirst($payroll->status ?? 'Unknown')) }}
                    </span>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Gross Salary') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($payroll->gross_salary ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Deductions') }}</span>
                        <span class="font-medium text-red-600">-{{ number_format($payroll->total_deductions ?? 0, 2) }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-900 dark:text-white">{{ __('Net Salary') }}</span>
                            <span class="font-bold text-green-600">{{ number_format($payroll->net_salary ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" wire:click="downloadPayslip({{ $payroll->id }})" 
                        class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Download PDF') }}
                        </span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('No payslips found.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
        <div class="mt-4">
            {{ $records->links() }}
        </div>
    @endif
</div>
