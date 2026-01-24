<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">{{ __('Vehicle Models') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage vehicle models for spare parts compatibility.') }}</p>
        </div>
        <a href="{{ route('app.inventory.vehicle-models.create') }}"
           wire:navigate
           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-500/30 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Add Vehicle Model') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   class="erp-input ps-10"
                   placeholder="{{ __('Search by brand or model...') }}">
            <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <select wire:model.live="brandFilter" class="erp-input w-full sm:w-48">
            <option value="">{{ __('All Brands') }}</option>
            @foreach($brands as $brand)
                <option value="{{ $brand }}">{{ $brand }}</option>
            @endforeach
        </select>
    </div>

    {{-- Flash Message --}}
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Brand') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Model') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Years') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Category') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Engine') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">{{ __('Parts') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-end text-xs font-semibold uppercase text-slate-500">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($models as $model)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $model->brand }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $model->model }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @if($model->year_from && $model->year_to)
                                {{ $model->year_from }} - {{ $model->year_to }}
                            @elseif($model->year_from)
                                {{ $model->year_from }}+
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $model->category ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $model->engine_type ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                {{ $model->compatibilities_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button"
                                    wire:click="toggleActive({{ $model->id }})"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $model->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $model->is_active ? __('Active') : __('Inactive') }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('app.inventory.vehicle-models.edit', ['vehicleModel' => $model->id]) }}"
                                   wire:navigate
                                   class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                    {{ __('Edit') }}
                                </a>
                                <button type="button"
                                        wire:click="delete({{ $model->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this vehicle model?') }}"
                                        class="inline-flex items-center rounded-lg bg-red-50 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            {{ __('No vehicle models found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $models->links() }}
    </div>
</div>
