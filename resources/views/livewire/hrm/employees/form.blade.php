<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $employeeId ? __('Edit employee') : __('Create employee') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Basic information and HR settings for the employee.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Employee code') }}
                </label>
                <input type="text" wire:model="form.code" class="erp-input">
                @error('form.code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Full name') }}
                </label>
                <input type="text" wire:model="form.name" class="erp-input">
                @error('form.name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Position / title') }}
                </label>
                <input type="text" wire:model="form.position" class="erp-input">
                @error('form.position')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Base salary') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.salary" class="erp-input">
                @error('form.salary')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Branch') }}
                    </label>
                    <x-quick-add-link 
                        :route="route('admin.branches.create')" 
                        label="{{ __('Add Branch') }}"
                        permission="branches.create" />
                </div>
                <select wire:model="form.branch_id" class="erp-input">
                    @foreach(\App\Models\Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('form.branch_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Linked user (optional)') }}
                    </label>
                    <x-quick-add-link 
                        :route="route('admin.users.create')" 
                        label="{{ __('Add User') }}"
                        permission="users.manage" />
                </div>
                <select wire:model="form.user_id" class="erp-input">
                    <option value="">{{ __('Not linked') }}</option>
                    @foreach($availableUsers as $option)
                        <option value="{{ $option['id'] }}">
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('form.user_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Status') }}
                </label>
                <label class="inline-flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
                    <input type="checkbox" wire:model="form.is_active"
                           class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>{{ __('Active') }}</span>
                </label>
                @error('form.is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if (! empty($dynamicSchema))
                <div class="sm:col-span-2 lg:col-span-3 space-y-2">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('Additional fields') }}
                    </h2>
                    <livewire:shared.dynamic-form
                        :schema="$dynamicSchema"
                        :data="$dynamicData"
                        wire:key="employee-dynamic-form-{{ $employeeId ?? 'new' }}"
                    ></livewire:shared.dynamic-form>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('app.hrm.employees.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $employeeId ? __('Save changes') : __('Create employee') }}
            </button>
        </div>
    </form>
</div>
