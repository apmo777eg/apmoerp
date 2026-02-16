<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('SLA Policies') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage Service Level Agreement policies') }}</p>
        </div>
        <a href="{{ route('app.helpdesk.sla-policies.create') }}" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('New SLA Policy') }}
        </a>
    </div>

    @if(session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Name') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Response Time') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Resolution Time') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Business Hours') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($policies as $policy)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $policy->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $policy->getResponseTimeFormatted() }}</td>
                        <td class="px-6 py-4 text-sm">{{ $policy->getResolutionTimeFormatted() }}</td>
                        <td class="px-6 py-4 text-sm">{{ $policy->business_hours_only ? __('Yes') : __('No') }}</td>
                        <td class="px-6 py-4">
                            <button type="button" wire:click="toggleActive({{ $policy->id }})" class="px-2 py-1 text-xs font-semibold rounded 
                                {{ $policy->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ $policy->is_active ? __('Active') : __('Inactive') }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('app.helpdesk.sla-policies.edit', $policy->id) }}" class="text-blue-600 hover:text-blue-900">{{ __('Edit') }}</a>
                                <button type="button" wire:click="delete({{ $policy->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">{{ __('No SLA policies found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $policies->links() }}</div>
    </div>
</div>
