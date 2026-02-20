<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $isEdit ? __('Edit Ticket') : __('New Ticket') }}</h1>
        <p class="text-sm text-slate-500">{{ $isEdit ? __('Update ticket details') : __('Create a new support ticket') }}</p>
    </div>

    <form wire:submit="save" class="bg-white rounded-xl shadow-sm p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Subject') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="subject" class="erp-input w-full" required>
                @error('subject') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }} <span class="text-red-500">*</span></label>
                <textarea wire:model="description" rows="6" class="erp-input w-full" required></textarea>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Customer') }}</label>
                <select wire:model="customer_id" class="erp-input w-full">
                    <option value="">{{ __('Select Customer') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Category') }}</label>
                <select wire:model="category_id" class="erp-input w-full">
                    <option value="">{{ __('Select Category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Priority') }}</label>
                <select wire:model="priority_id" class="erp-input w-full">
                    <option value="">{{ __('Select Priority') }}</option>
                    @foreach($priorities as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('priority_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Assign To') }}</label>
                <select wire:model="assigned_to" class="erp-input w-full">
                    <option value="">{{ __('Unassigned') }}</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('SLA Policy') }}</label>
                <select wire:model="sla_policy_id" class="erp-input w-full">
                    <option value="">{{ __('Select SLA Policy') }}</option>
                    @foreach($slaPolicies as $policy)
                        <option value="{{ $policy->id }}">{{ $policy->name }}</option>
                    @endforeach
                </select>
                @error('sla_policy_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Due Date') }}</label>
                <input type="datetime-local" wire:model="due_date" class="erp-input w-full">
                @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            @if($isEdit)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Status') }}</label>
                    <select wire:model="status" class="erp-input w-full">
                        <option value="new">{{ __('New') }}</option>
                        <option value="open">{{ __('Open') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="resolved">{{ __('Resolved') }}</option>
                        <option value="closed">{{ __('Closed') }}</option>
                    </select>
                    @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Tags') }}</label>
                <div class="flex gap-2 mb-2">
                    <input type="text" wire:model="tagInput" wire:keydown.enter.prevent="addTag" class="erp-input flex-1" placeholder="{{ __('Add tag and press Enter') }}">
                    <button type="button" wire:click="addTag" class="erp-btn erp-btn-secondary">{{ __('Add') }}</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                            {{ $tag }}
                            <button type="button" wire:click="removeTag(@js($tag))" class="hover:text-blue-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t">
            <a href="{{ route('app.helpdesk.index') }}" class="erp-btn erp-btn-secondary">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $isEdit ? __('Update Ticket') : __('Create Ticket') }}
            </button>
        </div>
    </form>
</div>
