<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ __('Compare Quotations') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Compare supplier quotations for the same requisition') }}</p>
        </div>

        <a href="{{ route('app.purchases.quotations.index') }}" class="erp-btn erp-btn-secondary">{{ __('Back') }}</a>
    </div>

    <div class="erp-card p-6 space-y-4">
        <div>
            <label class="erp-label">{{ __('Select Requisition') }}</label>
            <select wire:model="requisition_id" class="erp-input">
                <option value="">{{ __('Select') }}</option>
                @foreach($requisitions as $r)
                    <option value="{{ $r->id }}">{{ $r->reference_number }} @if($r->subject) — {{ $r->subject }} @endif</option>
                @endforeach
            </select>
        </div>

        @if($showComparison && !empty($comparisonData))
            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Requisition') }}: {{ $comparisonData['requisition']?->reference_number }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $comparisonData['requisition']?->subject }}</p>
                    </div>

                    @can('purchases.manage')
                        <button type="button" wire:click="acceptBestQuotation" class="erp-btn erp-btn-primary">
                            {{ __('Accept Best Offer') }}
                        </button>
                    @endcan
                </div>

                @if(isset($comparisonData['bestQuotation']))
                    <div class="mt-4 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <p class="text-sm text-emerald-800 dark:text-emerald-200">
                            {{ __('Best quotation (lowest total):') }}
                            <span class="font-semibold">{{ $comparisonData['bestQuotation']->supplier?->name }}</span>
                            — {{ number_format((float) ($comparisonData['bestQuotation']->total_amount ?? 0), 2) }}
                        </p>
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($comparisonData['quotations'] as $q)
                        <div class="erp-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $q->supplier?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $q->reference_number ?? $q->reference_number }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($q->status === 'accepted') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif($q->status === 'rejected') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300
                                    @else bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @endif">
                                    {{ ucfirst($q->status) }}
                                </span>
                            </div>

                            <div class="mt-3 text-sm">
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}</span><span>{{ number_format((float) ($q->subtotal ?? 0), 2) }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('Tax') }}</span><span>{{ number_format((float) ($q->tax_amount ?? 0), 2) }}</span></div>
                                <div class="flex justify-between font-semibold"><span>{{ __('Total') }}</span><span>{{ number_format((float) ($q->total_amount ?? 0), 2) }}</span></div>
                            </div>

                            <div class="mt-4">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">{{ __('Items') }}</p>
                                <ul class="text-sm space-y-1">
                                    @foreach($q->items as $item)
                                        <li class="flex justify-between gap-3">
                                            <span class="truncate">{{ $item->product?->name ?? '-' }}</span>
                                            <span class="shrink-0">{{ number_format((float) $item->unit_price, 2) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">{{ __('Price Matrix') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    @foreach($comparisonData['quotations'] as $q)
                                        <th class="min-w-[180px]">{{ $q->supplier?->name ?? '-' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comparisonData['matrix'] as $row)
                                    <tr>
                                        <td class="font-medium">{{ $row['product']?->name ?? '-' }}</td>
                                        @foreach($comparisonData['quotations'] as $q)
                                            @php($cell = $row['quotations'][$q->id] ?? null)
                                            <td>
                                                @if($cell)
                                                    <div class="text-sm">
                                                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('Unit') }}</span><span>{{ number_format((float) ($cell['unit_price'] ?? 0), 2) }}</span></div>
                                                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('Qty') }}</span><span>{{ number_format((float) ($cell['quantity'] ?? 0), 2) }}</span></div>
                                                        <div class="flex justify-between font-semibold"><span>{{ __('Line') }}</span><span>{{ number_format((float) ($cell['line_total'] ?? 0), 2) }}</span></div>
                                                    </div>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @elseif($requisition_id)
            <div class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('No quotations to compare for this requisition.') }}
            </div>
        @endif
    </div>
</div>
