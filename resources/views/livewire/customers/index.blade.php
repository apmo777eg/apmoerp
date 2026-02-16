<div class="space-y-6">
    {{-- Global Loading Overlay --}}
    <div wire:loading.delay class="loading-overlay bg-slate-900/20 flex items-center justify-center" style="display:none">
        <div class="bg-white rounded-xl shadow-xl p-6 flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-slate-700 font-medium">{{ __('Loading...') }}</span>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Customers') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage your customers database') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openExportModal"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('Export') }}
            </button>
            <a href="{{ route('customers.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Add Customer') }}
            </a>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <div class="flex-1 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search customers...') }}" class="erp-input pr-10">
                {{-- Search loading indicator --}}
                <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            <div class="relative">
                <select wire:model.live="customerType" class="erp-input w-full sm:w-48">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="individual">{{ __('Individual') }}</option>
                    <option value="company">{{ __('Company') }}</option>
                </select>
                <div wire:loading.delay wire:target="customerType" class="absolute right-8 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>

        @if(session()->has('success'))
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th wire:click="sortBy('name')" class="cursor-pointer hover:bg-slate-100">{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Balance') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="font-medium text-slate-800">{{ $customer->name }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td dir="ltr">{{ $customer->phone ?? '-' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full {{ $customer->customer_type === 'company' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $customer->customer_type === 'company' ? __('Company') : __('Individual') }}
                                </span>
                            </td>
                            <td class="{{ $customer->balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ number_format($customer->balance, 2) }} {{ __('EGP') }}
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full {{ $customer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $customer->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('customers.edit', $customer) }}" class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <button type="button" wire:click="delete({{ $customer->id }})" wire:loading.attr="disabled" wire:loading.class="opacity-50" wire:target="delete({{ $customer->id }})" wire:confirm="{{ __('Are you sure you want to delete this customer?') }}" class="text-red-600 hover:text-red-800">
                                        <svg wire:loading.remove wire:target="delete({{ $customer->id }})" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <svg wire:loading wire:target="delete({{ $customer->id }})" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-500">{{ __('No customers found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginationMode === 'load-more')
            <x-load-more :hasMore="$hasMorePages" loadMoreMethod="loadMore" />
        @else
            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    @if($showExportModal)
        <x-export-modal 
            :exportColumns="$exportColumns"
            :selectedExportColumns="$selectedExportColumns"
            :exportFormat="$exportFormat"
            :exportDateFormat="$exportDateFormat"
            :exportIncludeHeaders="$exportIncludeHeaders"
            :exportRespectFilters="$exportRespectFilters"
            :exportIncludeTotals="$exportIncludeTotals"
            :exportMaxRows="$exportMaxRows"
            :exportUseBackgroundJob="$exportUseBackgroundJob"
        />
    @endif
</div>
