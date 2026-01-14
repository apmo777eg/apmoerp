{{-- resources/views/livewire/rental/contracts/form.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $contractId ? __('Edit rental contract') : __('Create rental contract') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Link a tenant to a rental unit with start/end dates and pricing.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6 max-w-3xl">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Unit') }}
                    </label>
                    <x-quick-add-link 
                        :route="route('app.rental.units.create')" 
                        label="{{ __('Add Unit') }}"
                        permission="rental.units.manage" />
                </div>
                <select wire:model="form.unit_id" class="erp-input">
                    @foreach($availableUnits as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('form.unit_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Tenant') }}
                    </label>
                    <x-quick-add-link 
                        :route="route('app.rental.tenants.index')" 
                        label="{{ __('Add Tenant') }}"
                        permission="rental.view" />
                </div>
                <select wire:model="form.tenant_id" class="erp-input">
                    @foreach($availableTenants as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('form.tenant_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Status') }}
                </label>
                <select wire:model="form.status" class="erp-input">
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="ended">{{ __('Ended') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
                @error('form.status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Rental Period') }}
                    </label>
                    <x-quick-add-link 
                        :route="route('admin.modules.rental-periods', ['module' => 5])" 
                        label="{{ __('Manage Periods') }}"
                        permission="modules.manage" />
                </div>
                <select wire:model.live="form.rental_period_id" class="erp-input">
                    <option value="">{{ __('Select period...') }}</option>
                    @foreach($availablePeriods as $period)
                        <option value="{{ $period['id'] }}">{{ $period['label'] }}</option>
                    @endforeach
                </select>
                @error('form.rental_period_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @if(empty($availablePeriods))
                    <p class="mt-1 text-xs text-amber-600">
                        {{ __('No rental periods configured. Please add periods in Admin > Modules > Rental Periods.') }}
                    </p>
                @endif
            </div>

            @if($showCustomDays)
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Number of Days') }}
                </label>
                <input type="number" min="1" max="365" wire:model.live="form.custom_days" class="erp-input" placeholder="{{ __('Enter number of days') }}">
                @error('form.custom_days')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Start date') }}
                </label>
                <input type="date" wire:model.live="form.start_date" class="erp-input">
                @error('form.start_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('End date') }}
                    <span class="text-xs text-slate-500">({{ __('auto-calculated') }})</span>
                </label>
                <input type="date" wire:model="form.end_date" class="erp-input bg-slate-100" readonly>
                @error('form.end_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Monthly rent') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.rent" class="erp-input">
                @error('form.rent')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Security deposit') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.deposit" class="erp-input">
                @error('form.deposit')
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
                        wire:key="rental-contract-dynamic-form-{{ $contractId ?? 'new' }}"
                    ></livewire:shared.dynamic-form>
                </div>
            @endif
        </div>

        {{-- File Upload Section --}}
        <div class="space-y-4 border-t border-slate-200 dark:border-slate-700 pt-6">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Contract Documents & Images') }}
            </h2>
            
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Upload Files') }}
                </label>
                <input type="file" wire:model="contractFiles" multiple 
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"
                    class="block w-full text-sm text-slate-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100
                        dark:file:bg-blue-900 dark:file:text-blue-200">
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Accepted: PDF, DOC, DOCX, JPG, PNG, GIF (Max: 10MB per file)') }}
                </p>
                @error('contractFiles.*') 
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Existing Files --}}
            @if(!empty($existingFiles))
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">
                        {{ __('Uploaded Files') }}
                    </h3>
                    @foreach($existingFiles as $index => $file)
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ $file['original_name'] ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ isset($file['size']) ? number_format($file['size'] / 1024, 2) . ' KB' : '' }}
                                        {{ isset($file['uploaded_at']) ? '• ' . \Carbon\Carbon::parse($file['uploaded_at'])->diffForHumans() : '' }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" wire:click="removeExistingFile({{ $index }})" 
                                wire:confirm="{{ __('Are you sure you want to remove this file?') }}"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Uploading Progress --}}
            <div wire:loading wire:target="contractFiles" class="text-sm text-blue-600 dark:text-blue-400">
                {{ __('Uploading files...') }}
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
            <a href="{{ route('app.rental.contracts.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $contractId ? __('Save changes') : __('Create contract') }}
            </button>
        </div>
    </form>
</div>
