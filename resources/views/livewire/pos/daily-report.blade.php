<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">{{ __('POS Daily Report') }}</h1>
            <p class="text-sm text-slate-500">{{ __('View daily sales summary and session reports') }}</p>
        </div>
        <div class="flex items-center gap-3">
	            @if($isSuperAdmin)
	                @if($branchContextId)
	                    @php
	                        $lockedBranchName = optional($branches->firstWhere('id', $branchContextId))->name ?? __('Selected Branch');
	                    @endphp
	                    <div class="erp-input bg-emerald-50 border-emerald-200 text-emerald-900 flex items-center gap-2">
	                        <span class="text-xs font-semibold truncate">{{ $lockedBranchName }}</span>
	                        <span class="ms-auto text-[11px] bg-white/70 border border-emerald-200 rounded-full px-2 py-0.5">{{ __('Locked') }}</span>
	                    </div>
	                @else
	                    <select wire:model.live="branchId" class="erp-input">
	                        <option value="">{{ __('All Branches') }}</option>
	                        @foreach($branches as $branch)
	                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
	                        @endforeach
	                    </select>
	                @endif
	            @endif
            <input type="date" wire:model.live="date" class="erp-input">
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm">
            <p class="text-xs text-emerald-600 font-medium">{{ __('Total Sales') }}</p>
            <p class="text-2xl font-bold text-emerald-700">{{ number_format($summary['total_sales'] ?? 0, 2) }}</p>
            <p class="text-xs text-slate-500">{{ __('EGP') }}</p>
        </div>
        
        <div class="rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-4 shadow-sm">
            <p class="text-xs text-blue-600 font-medium">{{ __('Transactions') }}</p>
            <p class="text-2xl font-bold text-blue-700">{{ $summary['total_transactions'] ?? 0 }}</p>
            <p class="text-xs text-slate-500">{{ __('Orders') }}</p>
        </div>
        
        <div class="rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50 to-white p-4 shadow-sm">
            <p class="text-xs text-purple-600 font-medium">{{ __('Average Sale') }}</p>
            <p class="text-2xl font-bold text-purple-700">{{ number_format($summary['average_sale'] ?? 0, 2) }}</p>
            <p class="text-xs text-slate-500">{{ __('EGP') }}</p>
        </div>
        
        <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm">
            <p class="text-xs text-amber-600 font-medium">{{ __('Total Discount') }}</p>
            <p class="text-2xl font-bold text-amber-700">{{ number_format($summary['total_discount'] ?? 0, 2) }}</p>
            <p class="text-xs text-slate-500">{{ __('EGP') }}</p>
        </div>
        
        <div class="rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 shadow-sm">
            <p class="text-xs text-rose-600 font-medium">{{ __('Total Tax') }}</p>
            <p class="text-2xl font-bold text-rose-700">{{ number_format($summary['total_tax'] ?? 0, 2) }}</p>
            <p class="text-xs text-slate-500">{{ __('EGP') }}</p>
        </div>
        
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
            <p class="text-xs text-slate-600 font-medium">{{ __('Sessions') }}</p>
            <p class="text-2xl font-bold text-slate-700">{{ count($summary['sessions'] ?? []) }}</p>
            <p class="text-xs text-slate-500">{{ __('Today') }}</p>
        </div>
    </div>

    {{-- Payment Breakdown --}}
    <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Payment Methods Breakdown') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $methods = [
                    'cash' => ['label' => __('Cash'), 'color' => 'emerald', 'icon' => '💵'],
                    'card' => ['label' => __('Card'), 'color' => 'blue', 'icon' => '💳'],
                    'transfer' => ['label' => __('Bank Transfer'), 'color' => 'purple', 'icon' => '🏦'],
                    'cheque' => ['label' => __('Cheque'), 'color' => 'amber', 'icon' => '📝'],
                ];
            @endphp
            @foreach($methods as $key => $method)
                @php
                    $data = $summary['payment_breakdown'][$key] ?? ['count' => 0, 'total' => 0];
                @endphp
                <div class="rounded-xl border border-{{ $method['color'] }}-100 bg-{{ $method['color'] }}-50/50 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xl">{{ $method['icon'] }}</span>
                        <span class="font-medium text-slate-700">{{ $method['label'] }}</span>
                    </div>
                    <p class="text-xl font-bold text-{{ $method['color'] }}-700">{{ number_format($data['total'] ?? 0, 2) }} {{ __('EGP') }}</p>
                    <p class="text-xs text-slate-500">{{ $data['count'] ?? 0 }} {{ __('transactions') }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Sessions --}}
    @if(!empty($summary['sessions']))
    <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('POS Sessions') }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Cashier') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Opening Cash') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Closing Cash') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Expected') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Difference') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Transactions') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Total Sales') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Time') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['sessions'] as $session)
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $session['user_name'] }}</td>
                        <td class="px-4 py-3">{{ number_format($session['opening_cash'], 2) }}</td>
                        <td class="px-4 py-3">{{ $session['closing_cash'] !== null ? number_format($session['closing_cash'], 2) : '-' }}</td>
                        <td class="px-4 py-3">{{ $session['expected_cash'] !== null ? number_format($session['expected_cash'], 2) : '-' }}</td>
                        <td class="px-4 py-3 {{ ($session['cash_difference'] ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $session['cash_difference'] !== null ? number_format($session['cash_difference'], 2) : '-' }}
                        </td>
                        <td class="px-4 py-3">{{ $session['total_transactions'] }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($session['total_sales'], 2) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $session['opened_at'] }} - {{ $session['closed_at'] ?? __('Open') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $session['status'] === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $session['status'] === 'open' ? __('Open') : __('Closed') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Sales List --}}
    <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Sales Transactions') }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Code') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Customer') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Payment Method') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Subtotal') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Discount') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Total') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Cashier') }}</th>
                        <th class="px-4 py-2 text-start font-medium text-slate-600">{{ __('Time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $sale->reference_number }}</td>
                        <td class="px-4 py-3">{{ $sale->customer?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @foreach($sale->payments as $payment)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700 mr-1">
                                    {{ __($payment->payment_method) }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">{{ number_format($sale->subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-amber-600">{{ number_format($sale->discount_amount, 2) }}</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">{{ number_format($sale->total_amount, 2) }}</td>
                        <td class="px-4 py-3">{{ $sale->createdBy?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $sale->created_at?->format('H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            {{ __('No sales found for this date') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $sales->links() }}
        </div>
    </div>
</div>
