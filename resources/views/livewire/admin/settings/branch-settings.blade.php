<div class="space-y-4">
    <div class="flex flex-col gap-1">
        <h1 class="text-lg font-semibold text-slate-800">
            {{ __('Branch Settings') }}
        </h1>
        <p class="text-sm text-slate-500">
            {{ __('Settings stored per branch (discount limits, POS, payroll tax, etc.).') }}
        </p>
    </div>

    {{-- Developer Warning --}}
    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-amber-800">{{ __('Developer/Super-Admin Only') }}</h3>
                <p class="text-xs text-amber-700 mt-1">
                    {{ __('This page allows direct key/value editing of branch-specific settings. For most configuration needs, branch settings should be configured through the main') }}
                    <a href="{{ route('admin.settings') }}" class="underline font-medium hover:text-amber-900">{{ __('Unified Settings') }}</a>
                    {{ __('page with branch override capability.') }}
                </p>
                <p class="text-xs text-amber-600 mt-2">
                    {{ __('Incorrect key names or values may cause unexpected behavior for this branch.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
                {{ __('Branch') }}
            </label>
            <select wire:model="branchId"
                    aria-label="{{ __('Select branch') }}"
                    class="erp-input">
                @foreach($branches as $branch)
                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                @endforeach
            </select>
            @error('branchId')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <form wire:submit.prevent="save" class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-sm font-medium text-slate-700">
                    {{ __('Branch-specific key/value pairs') }}
                </h2>
                <button type="button"
                        wire:click="addRow"
                        aria-label="{{ __('Add new branch setting') }}"
                        class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-emerald-500/30 hover:bg-slate-800">
                    {{ __('Add setting') }}
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm shadow-emerald-500/10">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                                {{ __('Key') }}
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                                {{ __('Value') }}
                            </th>
                            <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($rows as $index => $row)
                            <tr class="hover:bg-emerald-50/40">
                                <td class="px-3 py-2 align-top">
                                    <input type="text" wire:model="rows.{{ $index }}.key"
                                           class="erp-input text-xs">
                                    @error('rows.' . $index . '.key')
                                        <p class="mt-1 text-[0.7rem] text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="text" wire:model="rows.{{ $index }}.value"
                                           class="erp-input text-xs">
                                </td>
                                <td class="px-3 py-2 align-top text-end">
                                    <button type="button"
                                            wire:click="removeRow({{ $index }})"
                                            aria-label="{{ __('Remove this setting') }}"
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-[0.7rem] font-semibold text-red-700 hover:bg-red-100">
                                        {{ __('Remove') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="erp-btn-primary">
                    {{ __('Save branch settings') }}
                </button>
            </div>
        </form>
    </div>
</div>
