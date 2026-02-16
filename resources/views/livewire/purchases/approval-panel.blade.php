<div class="erp-card p-4">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('Approval Status') }}
        </h3>
        
        @if($workflowInstance)
            <span @class([
                'px-2 py-1 rounded-full text-xs font-medium',
                'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' => $statusColor === 'amber',
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $statusColor === 'green',
                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => $statusColor === 'red',
                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $statusColor === 'gray',
            ])>
                {{ __(ucfirst($workflowInstance->status)) }}
            </span>
        @endif
    </div>

    {{-- No Workflow --}}
    @if(!$workflowInstance)
        <div class="text-center py-6">
            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ __('No approval workflow active') }}</p>
            
            @can('purchases.approve')
                <button type="button" wire:click="initiateWorkflow" class="mt-4 erp-btn erp-btn-secondary text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Request Approval') }}
                </button>
            @endcan
        </div>
    @else
        {{-- Workflow Active --}}
        <div class="space-y-4">
            {{-- Current Stage --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('Current Stage') }}</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $workflowInstance->current_stage }}</p>
            </div>

            {{-- Approval Stages Timeline --}}
            @if($workflowInstance->approvals->count() > 0)
                <div class="space-y-2">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Approval Timeline') }}</p>
                    @foreach($workflowInstance->approvals->sortBy('stage_order') as $approval)
                        <div class="flex items-start gap-3">
                            <div @class([
                                'w-6 h-6 rounded-full flex items-center justify-center text-xs shrink-0 mt-0.5',
                                'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' => $approval->status === 'approved',
                                'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' => $approval->status === 'rejected',
                                'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' => $approval->status === 'pending',
                            ])>
                                @if($approval->status === 'approved')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif($approval->status === 'rejected')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $approval->stage_name }}</p>
                                @if($approval->approver)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $approval->approver->name }}</p>
                                @elseif($approval->approver_role)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Role') }}: {{ $approval->approver_role }}</p>
                                @endif
                                @if($approval->comments)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 italic">"{{ $approval->comments }}"</p>
                                @endif
                                @if($approval->responded_at)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $approval->responded_at->format('M d, Y H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Action Buttons --}}
            @if($workflowInstance->status === 'pending' && $canApprove)
                <div class="flex gap-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                    <button type="button" wire:click="$set('showApproveModal', true)" class="flex-1 erp-btn bg-green-600 hover:bg-green-700 text-white text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Approve') }}
                    </button>
                    <button type="button" wire:click="$set('showRejectModal', true)" class="flex-1 erp-btn bg-red-600 hover:bg-red-700 text-white text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('Reject') }}
                    </button>
                </div>
            @endif

            {{-- Cancel Button --}}
            @if($workflowInstance->status === 'pending' && $workflowInstance->initiated_by === auth()->id())
                <div class="pt-2">
                    <button type="button" wire:click="cancelWorkflow" wire:confirm="{{ __('Are you sure you want to cancel this approval request?') }}" class="text-xs text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400">
                        {{ __('Cancel Request') }}
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Approve Modal --}}
    @if($showApproveModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showApproveModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('Approve Purchase') }}</h4>
                
                <div class="mb-4">
                    <label class="erp-label">{{ __('Comments (Optional)') }}</label>
                    <textarea wire:model="approvalComments" rows="3" class="erp-input" placeholder="{{ __('Add any comments...') }}"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" wire:click="$set('showApproveModal', false)" class="flex-1 erp-btn erp-btn-secondary">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="approve" class="flex-1 erp-btn bg-green-600 hover:bg-green-700 text-white">{{ __('Confirm Approval') }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showRejectModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('Reject Purchase') }}</h4>
                
                <div class="mb-4">
                    <label class="erp-label">{{ __('Reason for Rejection') }} <span class="text-red-500">*</span></label>
                    <textarea wire:model="rejectionReason" rows="3" class="erp-input" placeholder="{{ __('Please explain why you are rejecting this purchase...') }}"></textarea>
                    @error('rejectionReason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button type="button" wire:click="$set('showRejectModal', false)" class="flex-1 erp-btn erp-btn-secondary">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="reject" class="flex-1 erp-btn bg-red-600 hover:bg-red-700 text-white">{{ __('Confirm Rejection') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
