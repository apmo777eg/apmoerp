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
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Accounting Module') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage chart of accounts and journal entries') }}</p>
        </div>
        @can('accounting.create')
        <div class="flex gap-2">
            <a href="{{ route('app.accounting.accounts.create') }}" class="erp-btn erp-btn-secondary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('New Account') }}
            </a>
            <a href="{{ route('app.accounting.journal-entries.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('New Journal Entry') }}
            </a>
        </div>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">{{ __('Total Accounts') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_accounts']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">{{ __('Active Accounts') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['active_accounts']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-cyan-100 text-sm">{{ __('Total Assets') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_assets'], 2) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">{{ __('Total Liabilities') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_liabilities'], 2) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">{{ __('Journal Entries') }}</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['journal_entries']) }}</p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="erp-card">
        <div class="border-b border-slate-200">
            <nav class="flex gap-4 px-4" aria-label="Tabs">
                <button type="button" wire:click="setTab('accounts')" class="py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'accounts' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        {{ __('Chart of Accounts') }}
                        <div wire:loading.delay wire:target="setTab('accounts')">
                            <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </button>
                <button type="button" wire:click="setTab('journal')" class="py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'journal' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('Journal Entries') }}
                        <div wire:loading.delay wire:target="setTab('journal')">
                            <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </button>
            </nav>
        </div>

        <div class="p-4">
            <div class="flex flex-col lg:flex-row gap-4 mb-6">
                <div class="flex-1 relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search...') }}" class="erp-input pr-10">
                    {{-- Search loading indicator --}}
                    <div wire:loading.delay wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @if($activeTab === 'accounts')
                    <div class="relative">
                        <select wire:model.live="accountType" class="erp-input w-full lg:w-48">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="asset">{{ __('Asset') }}</option>
                            <option value="liability">{{ __('Liability') }}</option>
                            <option value="equity">{{ __('Equity') }}</option>
                            <option value="revenue">{{ __('Revenue') }}</option>
                            <option value="expense">{{ __('Expense') }}</option>
                        </select>
                        <div wire:loading.delay wire:target="accountType" class="absolute right-8 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                @endif
            </div>

            @if($activeTab === 'accounts')
                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>{{ __('Account #') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Balance') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr wire:key="account-{{ $account->id }}" class="hover:bg-slate-50">
                                    <td class="font-mono text-slate-700">{{ $account->account_number }}</td>
                                    <td class="font-medium text-slate-800">{{ $account->localized_name ?? $account->name }}</td>
                                    <td>
                                        @php
                                            $typeColors = [
                                                'asset' => 'bg-blue-100 text-blue-700',
                                                'liability' => 'bg-red-100 text-red-700',
                                                'equity' => 'bg-purple-100 text-purple-700',
                                                'revenue' => 'bg-emerald-100 text-emerald-700',
                                                'expense' => 'bg-amber-100 text-amber-700'
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded-full {{ $typeColors[$account->type] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ __($account->type) }}
                                        </span>
                                    </td>
                                    <td class="{{ $account->balance < 0 ? 'text-red-600' : 'text-emerald-600' }} font-semibold">
                                        {{ number_format($account->balance, 2) }} {{ __('EGP') }}
                                    </td>
                                    <td>
                                        <span class="px-2 py-1 text-xs rounded-full {{ $account->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $account->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @can('accounting.view')
                                            <a href="{{ route('app.accounting.accounts.edit', $account->id) }}" class="text-blue-600 hover:text-blue-800" title="{{ __('View') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @endcan
                                            @can('accounting.update')
                                            <a href="{{ route('app.accounting.accounts.edit', $account->id) }}" class="text-slate-600 hover:text-slate-800" title="{{ __('Edit') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-12">
                                        <div class="text-slate-400">
                                            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                            <p class="text-lg font-medium">{{ __('No accounts found') }}</p>
                                            <p class="text-sm">{{ __('Start by creating your chart of accounts') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(is_object($accounts) && method_exists($accounts, 'links'))
                    <div class="mt-4">{{ $accounts->links() }}</div>
                @endif
            @else
                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Debit') }}</th>
                                <th>{{ __('Credit') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($journalEntries as $entry)
                                <tr wire:key="entry-{{ $entry->id }}" class="hover:bg-slate-50">
                                    <td class="font-mono text-slate-700">{{ $entry->reference_number }}</td>
                                    <td>{{ $entry->entry_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="max-w-xs truncate">{{ $entry->description ?? '-' }}</td>
                                    <td class="text-blue-600 font-semibold">{{ number_format($entry->total_debit ?? 0, 2) }}</td>
                                    <td class="text-emerald-600 font-semibold">{{ number_format($entry->total_credit ?? 0, 2) }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'posted' => 'bg-emerald-100 text-emerald-700',
                                                'draft' => 'bg-amber-100 text-amber-700',
                                                'cancelled' => 'bg-red-100 text-red-700'
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$entry->status] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ __($entry->status ?? 'draft') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @can('accounting.view')
                                            <a href="{{ route('app.accounting.journal-entries.edit', $entry->id) }}" class="text-blue-600 hover:text-blue-800" title="{{ __('View') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @endcan
                                            @can('accounting.update')
                                            @if($entry->status === 'draft')
                                            <a href="{{ route('app.accounting.journal-entries.edit', $entry->id) }}" class="text-slate-600 hover:text-slate-800" title="{{ __('Edit') }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12">
                                        <div class="text-slate-400">
                                            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <p class="text-lg font-medium">{{ __('No journal entries found') }}</p>
                                            <p class="text-sm">{{ __('Start by creating a new journal entry') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(is_object($journalEntries) && method_exists($journalEntries, 'links'))
                    <div class="mt-4">{{ $journalEntries->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
