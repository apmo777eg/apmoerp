<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                {{ $isEdit ? __('Edit Purchase Requisition') : __('New Purchase Requisition') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Create and manage purchase requisitions') }}</p>
        </div>

        <a href="{{ route('app.purchases.requisitions.index') }}" class="erp-btn erp-btn-secondary">
            {{ __('Back') }}
        </a>
    </div>

    <div class="erp-card p-6">
        <form wire:submit.prevent="save" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="erp-label">{{ __('Subject') }} *</label>
                    <input type="text" wire:model.defer="subject" class="erp-input" required>
                    @error('subject') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="erp-label">{{ __('Priority') }} *</label>
                    <select wire:model.defer="priority" class="erp-input" required>
                        <option value="low">{{ __('Low') }}</option>
                        <option value="normal">{{ __('Normal') }}</option>
                        <option value="high">{{ __('High') }}</option>
                        <option value="urgent">{{ __('Urgent') }}</option>
                    </select>
                    @error('priority') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="erp-label">{{ __('Required Date') }}</label>
                    <input type="date" wire:model.defer="required_date" class="erp-input">
                    @error('required_date') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="erp-label">{{ __('Department') }}</label>
                    <select wire:model.defer="department_id" class="erp-input">
                        <option value="">{{ __('Select') }}</option>
                        @foreach(($departments ?? []) as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name_ar ? $dept->name_ar.' - '.$dept->name : $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="erp-label">{{ __('Cost Center') }}</label>
                    <select wire:model.defer="cost_center_id" class="erp-input">
                        <option value="">{{ __('Select') }}</option>
                        @foreach(($costCenters ?? []) as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name_ar ? $cc->name_ar.' - '.$cc->name : $cc->name }}</option>
                        @endforeach
                    </select>
                    @error('cost_center_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <div>
                <label class="erp-label">{{ __('Justification') }}</label>
                <textarea wire:model.defer="justification" rows="3" class="erp-input"></textarea>
                @error('justification') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="erp-label">{{ __('Notes') }}</label>
                <textarea wire:model.defer="notes" rows="3" class="erp-input"></textarea>
                @error('notes') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Items') }}</h2>
                    <button type="button" wire:click="addItem" class="erp-btn erp-btn-secondary">
                        + {{ __('Add Item') }}
                    </button>
                </div>

                @error('items') <div class="text-red-600 text-sm mb-2">{{ $message }}</div> @enderror

                <div class="overflow-x-auto">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="min-w-[240px]">{{ __('Product') }}</th>
                                <th class="w-32">{{ __('Qty') }}</th>
                                <th class="w-40">{{ __('Estimated Price') }}</th>
                                <th class="min-w-[260px]">{{ __('Specifications') }}</th>
                                <th class="w-20"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td>
                                        <select wire:model="items.{{ $index }}.product_id" wire:change="updateProductPrice({{ $index }})" class="erp-input">
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p['id'] }}">{{ $p['name'] }} @if(!empty($p['sku'])) ({{ $p['sku'] }}) @endif</option>
                                            @endforeach
                                        </select>
                                        @error('items.'.$index.'.product_id') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" wire:model.defer="items.{{ $index }}.quantity" class="erp-input">
                                        @error('items.'.$index.'.quantity') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" wire:model.defer="items.{{ $index }}.estimated_price" class="erp-input">
                                        @error('items.'.$index.'.estimated_price') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="text" wire:model.defer="items.{{ $index }}.specifications" class="erp-input" placeholder="{{ __('Optional') }}">
                                        @error('items.'.$index.'.specifications') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-end">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="text-red-600 hover:text-red-800" title="{{ __('Remove') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                <a href="{{ route('app.purchases.requisitions.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>

                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save Draft') }}
                </button>

                @can('purchases.requisitions.create')
                    <button type="button" wire:click="submit" class="erp-btn erp-btn-primary">
                        {{ __('Submit for Approval') }}
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>
