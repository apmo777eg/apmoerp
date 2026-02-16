<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Loyalty Program') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Manage customer loyalty points and tiers') }}</p>
        </div>
        <button type="button" wire:click="$set('showSettingsModal', true)" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
            {{ __('Settings') }}
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg border border-emerald-200 dark:border-emerald-800">
            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['total_points']) }}</div>
            <div class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Total Points') }}</div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['vip_customers'] }}</div>
            <div class="text-sm text-purple-600 dark:text-purple-400">{{ __('VIP Customers') }}</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['premium_customers'] }}</div>
            <div class="text-sm text-yellow-600 dark:text-yellow-400">{{ __('Premium Customers') }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-4">
            <input type="text" wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search customers...') }}"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            
            <select wire:model.live="tier" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('All Tiers') }}</option>
                <option value="new">{{ __('New') }}</option>
                <option value="regular">{{ __('Regular') }}</option>
                <option value="vip">{{ __('VIP') }}</option>
                <option value="premium">{{ __('Premium') }}</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Customer') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Points') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Tier') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($customers as $customer)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $customer->name }}</div>
                            <div class="text-sm text-gray-500">{{ $customer->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-lg font-bold text-emerald-600">{{ number_format($customer->loyalty_points) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $tierColors = [
                                    'new' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'regular' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'vip' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                    'premium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $tierColors[$customer->customer_tier] ?? $tierColors['new'] }}">
                                {{ __(ucfirst($customer->customer_tier)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button type="button" wire:click="openAdjustModal({{ $customer->id }})" class="text-emerald-600 hover:text-emerald-800">
                                {{ __('Adjust Points') }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No customers found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $customers->links() }}
        </div>
    </div>

    @if($showSettingsModal)
    <div class="z-modal fixed inset-0 overflow-y-auto" x-data x-init="$el.focus()">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" wire:click="$set('showSettingsModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Loyalty Settings') }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Points per Amount') }}</label>
                        <input type="number" step="0.01" wire:model="points_per_amount" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount per Point') }}</label>
                        <input type="number" step="0.01" wire:model="amount_per_point" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Redemption Rate') }}</label>
                        <input type="number" step="0.001" wire:model="redemption_rate" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Minimum Points to Redeem') }}</label>
                        <input type="number" wire:model="min_points_redeem" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-emerald-600">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Program Active') }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showSettingsModal', false)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="saveSettings" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showAdjustModal)
    <div class="z-modal fixed inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" wire:click="$set('showAdjustModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Adjust Points') }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Points (negative to deduct)') }}</label>
                        <input type="number" wire:model="adjustPoints" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Reason') }}</label>
                        <textarea wire:model="adjustReason" rows="3" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showAdjustModal', false)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="adjustPoints" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        {{ __('Adjust') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
