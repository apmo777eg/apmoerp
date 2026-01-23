<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('GRN Inspection') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Quality inspection for received goods') }} - {{ $grn->code ?? 'GRN-' . $grn->id }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.purchases.grn.index') }}" class="erp-btn-secondary" wire:navigate>
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('Back to GRN List') }}
            </a>
        </div>
    </div>

    {{-- GRN Details Card --}}
    <div class="erp-card p-6 rounded-2xl">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('GRN Details') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-slate-500 uppercase">{{ __('Supplier') }}</p>
                <p class="font-medium text-slate-800">{{ $grn->supplier?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase">{{ __('Purchase Order') }}</p>
                <p class="font-medium text-slate-800">{{ $grn->purchaseOrder?->code ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase">{{ __('Status') }}</p>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                    @if($grn->status === 'approved') bg-emerald-100 text-emerald-700
                    @elseif($grn->status === 'rejected') bg-red-100 text-red-700
                    @elseif($grn->status === 'partial') bg-amber-100 text-amber-700
                    @else bg-slate-100 text-slate-700
                    @endif">
                    {{ ucfirst($grn->status) }}
                </span>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase">{{ __('Received Date') }}</p>
                <p class="font-medium text-slate-800">{{ $grn->received_date ?? $grn->created_at?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase">{{ __('Received By') }}</p>
                <p class="font-medium text-slate-800">{{ $grn->receivedBy?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase">{{ __('Total Items') }}</p>
                <p class="font-medium text-slate-800">{{ $grn->items->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Inspection Checklist --}}
    <div class="erp-card p-6 rounded-2xl">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Inspection Checklist') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(['quantity_verified' => __('Quantity Verified'), 'quality_verified' => __('Quality Verified'), 'packaging_intact' => __('Packaging Intact'), 'documentation_complete' => __('Documentation Complete'), 'no_visible_damage' => __('No Visible Damage')] as $key => $label)
            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-colors
                {{ $checklist[$key] ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 hover:border-slate-300' }}">
                <input type="checkbox" 
                       wire:click="updateChecklistItem('{{ $key }}', {{ json_encode(!$checklist[$key]) }})"
                       {{ $checklist[$key] ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Items Inspection Table --}}
    <div class="erp-card p-6 rounded-2xl">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Items Inspection') }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">{{ __('Product') }}</th>
                        <th class="py-3 pr-4">{{ __('Ordered Qty') }}</th>
                        <th class="py-3 pr-4">{{ __('Received Qty') }}</th>
                        <th class="py-3 pr-4 text-center">{{ __('Pass/Fail') }}</th>
                        <th class="py-3 pr-4">{{ __('Defect Category') }}</th>
                        <th class="py-3 pr-4">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($grn->items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 pr-4">
                            <div class="font-medium text-slate-800">{{ $item->product?->name ?? 'Unknown Product' }}</div>
                            <div class="text-xs text-slate-500">SKU: {{ $item->product?->sku ?? '—' }}</div>
                        </td>
                        <td class="py-3 pr-4">{{ $item->ordered_quantity ?? 0 }}</td>
                        <td class="py-3 pr-4">{{ $item->received_quantity ?? $item->quantity ?? 0 }}</td>
                        <td class="py-3 pr-4">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button"
                                        wire:click="updateInspection({{ $item->id }}, 'pass', true)"
                                        class="p-2 rounded-lg transition-colors {{ ($inspectionData[$item->id]['pass'] ?? null) === true ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400 hover:bg-emerald-50 hover:text-emerald-500' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <button type="button"
                                        wire:click="updateInspection({{ $item->id }}, 'pass', false)"
                                        class="p-2 rounded-lg transition-colors {{ ($inspectionData[$item->id]['pass'] ?? null) === false ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="py-3 pr-4">
                            <select wire:change="updateInspection({{ $item->id }}, 'defect_category', $event.target.value)"
                                    class="erp-input text-sm py-1"
                                    {{ ($inspectionData[$item->id]['pass'] ?? null) !== false ? 'disabled' : '' }}>
                                <option value="">{{ __('Select...') }}</option>
                                <option value="damaged" {{ ($inspectionData[$item->id]['defect_category'] ?? '') === 'damaged' ? 'selected' : '' }}>{{ __('Damaged') }}</option>
                                <option value="wrong_item" {{ ($inspectionData[$item->id]['defect_category'] ?? '') === 'wrong_item' ? 'selected' : '' }}>{{ __('Wrong Item') }}</option>
                                <option value="quantity_mismatch" {{ ($inspectionData[$item->id]['defect_category'] ?? '') === 'quantity_mismatch' ? 'selected' : '' }}>{{ __('Quantity Mismatch') }}</option>
                                <option value="expired" {{ ($inspectionData[$item->id]['defect_category'] ?? '') === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                                <option value="quality_issue" {{ ($inspectionData[$item->id]['defect_category'] ?? '') === 'quality_issue' ? 'selected' : '' }}>{{ __('Quality Issue') }}</option>
                                <option value="other" {{ ($inspectionData[$item->id]['defect_category'] ?? '') === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                        </td>
                        <td class="py-3 pr-4">
                            <input type="text" 
                                   wire:change="updateInspection({{ $item->id }}, 'notes', $event.target.value)"
                                   value="{{ $inspectionData[$item->id]['notes'] ?? '' }}"
                                   class="erp-input text-sm py-1"
                                   placeholder="{{ __('Notes...') }}">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">
                            {{ __('No items found in this GRN.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Inspector Notes --}}
    <div class="erp-card p-6 rounded-2xl">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('Inspector Notes') }}</h2>
        <textarea wire:model="inspectorNotes" 
                  rows="4" 
                  class="erp-input w-full"
                  placeholder="{{ __('Add any overall inspection notes or comments...') }}"></textarea>
        @error('inspectorNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
        <button wire:click="rejectGRN" 
                wire:loading.attr="disabled"
                class="w-full sm:w-auto px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ __('Reject GRN') }}
        </button>
        <button wire:click="partialAccept"
                wire:loading.attr="disabled"
                class="w-full sm:w-auto px-6 py-3 bg-amber-600 text-white rounded-xl hover:bg-amber-700 transition-colors disabled:opacity-50">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('Partial Accept') }}
        </button>
        <button wire:click="acceptGRN"
                wire:loading.attr="disabled"
                class="w-full sm:w-auto px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors disabled:opacity-50">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ __('Accept GRN') }}
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('error'))
    <div class="fixed bottom-4 right-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl shadow-lg" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
        {{ session('error') }}
    </div>
    @endif
</div>
