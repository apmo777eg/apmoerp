<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Suppliers') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage your suppliers database') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openExportModal"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('Export') }}
            </button>
            <a href="{{ route('suppliers.create') }}" class="erp-btn erp-btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Add Supplier') }}
            </a>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search suppliers...') }}" class="erp-input max-w-md">
        </div>

        @if(session()->has('success'))
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th wire:click="sortBy('name')" class="cursor-pointer hover:bg-slate-100">{{ __('Name') }}</th>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Contact Person') }}</th>
                        <th>{{ __('Balance') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr wire:key="supplier-{{ $supplier->id }}">
                            <td class="font-medium text-slate-800">{{ $supplier->name }}</td>
                            <td>{{ $supplier->company_name ?? '-' }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td dir="ltr">{{ $supplier->phone ?? '-' }}</td>
                            <td>{{ $supplier->contact_person ?? '-' }}</td>
                            <td class="{{ $supplier->balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ number_format($supplier->balance, 2) }} {{ __('EGP') }}
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $supplier->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <button type="button" wire:click="delete({{ $supplier->id }})" wire:confirm="{{ __('Are you sure you want to delete this supplier?') }}" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-500">{{ __('No suppliers found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $suppliers->links() }}
        </div>
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
