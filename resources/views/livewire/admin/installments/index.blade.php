<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Installment Plans') }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ __('Manage customer payment plans') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total_active'] }}</div>
            <div class="text-sm text-blue-600 dark:text-blue-400">{{ __('Active Plans') }}</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['total_completed'] }}</div>
            <div class="text-sm text-green-600 dark:text-green-400">{{ __('Completed') }}</div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['total_defaulted'] }}</div>
            <div class="text-sm text-red-600 dark:text-red-400">{{ __('Defaulted') }}</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['overdue_payments_count'] }}</div>
            <div class="text-sm text-yellow-600 dark:text-yellow-400">{{ __('Overdue') }}</div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['total_outstanding'], 2) }}</div>
            <div class="text-sm text-purple-600 dark:text-purple-400">{{ __('Outstanding') }}</div>
        </div>
    </div>

    @if($overduePayments->count() > 0)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
        <h3 class="font-bold text-red-800 dark:text-red-200 mb-2">{{ __('Overdue Payments') }} ({{ $overduePayments->count() }})</h3>
        <div class="space-y-2">
            @foreach($overduePayments->take(5) as $payment)
            <div class="flex justify-between items-center text-sm">
                <span class="text-red-700 dark:text-red-300">{{ $payment->plan?->customer?->name ?? '-' }} - {{ __('Installment') }} #{{ $payment->installment_number }}</span>
                <span class="font-bold text-red-800 dark:text-red-200">{{ number_format($payment->remaining_amount, 2) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-4">
                <input type="text" wire:model.live.debounce.300ms="search" 
                    placeholder="{{ __('Search customers...') }}"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                
                <select wire:model.live="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="defaulted">{{ __('Defaulted') }}</option>
                    <option value="all">{{ __('All') }}</option>
                </select>
            </div>
            
            <button type="button" wire:click="updateOverdue" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                {{ __('Update Overdue') }}
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Customer') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Total') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Paid') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Remaining') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Next Payment') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($plans as $plan)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $plan->customer?->name ?? '-' }}</div>
                            <div class="text-sm text-gray-500">{{ __('Invoice') }}: {{ $plan->sale?->code ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            {{ number_format($plan->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-green-600">
                            {{ number_format($plan->paid_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-red-600">
                            {{ number_format($plan->remaining_balance, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'active' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'defaulted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$plan->status] }}">
                                {{ __(ucfirst($plan->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($plan->next_payment)
                                <div class="text-sm">
                                    <span class="{{ $plan->next_payment->isOverdue() ? 'text-red-600' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ $plan->next_payment?->due_date?->format('Y-m-d') }}
                                    </span>
                                    <button type="button" wire:click="openPaymentModal({{ $plan->next_payment->id }})" class="ml-2 text-emerald-600 hover:text-emerald-800">
                                        {{ __('Pay') }}
                                    </button>
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No installment plans found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $plans->links() }}
        </div>
    </div>

    @if($showPaymentModal)
    <div class="z-modal fixed inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" wire:click="$set('showPaymentModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Record Payment') }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" wire:model="paymentAmount" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment Method') }}</label>
                        <select wire:model="paymentMethod" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="cash">{{ __('Cash') }}</option>
                            <option value="card">{{ __('Card') }}</option>
                            <option value="transfer">{{ __('Bank Transfer') }}</option>
                            <option value="cheque">{{ __('Cheque') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reference') }}</label>
                        <input type="text" wire:model="paymentReference" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="recordPayment" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        {{ __('Record Payment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
