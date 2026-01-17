<?php

declare(strict_types=1);

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Models\WorkflowApproval;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Purchase Approval Panel Component
 *
 * Shows approval workflow status and actions for a purchase.
 * Integrates with WorkflowService for multi-stage approvals.
 */
class ApprovalPanel extends Component
{
    public ?Purchase $purchase = null;

    public ?WorkflowInstance $workflowInstance = null;

    public ?WorkflowApproval $pendingApproval = null;

    public bool $showApproveModal = false;

    public bool $showRejectModal = false;

    public string $approvalComments = '';

    public string $rejectionReason = '';

    protected WorkflowService $workflowService;

    public function boot(WorkflowService $workflowService): void
    {
        $this->workflowService = $workflowService;
    }

    public function mount(Purchase $purchase): void
    {
        $this->purchase = $purchase;
        $this->loadWorkflowData();
    }

    /**
     * Load workflow instance and pending approval for this purchase
     */
    protected function loadWorkflowData(): void
    {
        if (! $this->purchase) {
            return;
        }

        $this->workflowInstance = WorkflowInstance::where('entity_type', 'purchase')
            ->where('entity_id', $this->purchase->id)
            ->latest()
            ->first();

        if ($this->workflowInstance) {
            $this->pendingApproval = $this->workflowInstance->approvals()
                ->where('status', 'pending')
                ->where('stage_name', $this->workflowInstance->current_stage)
                ->first();
        }
    }

    /**
     * Check if current user can approve
     */
    public function getCanApproveProperty(): bool
    {
        if (! $this->pendingApproval) {
            return false;
        }

        $userId = auth()->id();

        // Direct approver match
        if ($this->pendingApproval->approver_id === $userId) {
            return true;
        }

        // Role-based approval
        if ($this->pendingApproval->approver_role) {
            return auth()->user()->hasRole($this->pendingApproval->approver_role);
        }

        return false;
    }

    /**
     * Get workflow status color
     */
    public function getStatusColorProperty(): string
    {
        if (! $this->workflowInstance) {
            return 'gray';
        }

        return match ($this->workflowInstance->status) {
            'pending' => 'amber',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Initiate approval workflow for this purchase
     */
    public function initiateWorkflow(): void
    {
        if (! $this->purchase) {
            return;
        }

        try {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $instance = $this->workflowService->initiateWorkflow(
                moduleName: 'purchases',
                entityType: 'purchase',
                entityId: $this->purchase->id,
                entityData: [
                    'total' => $this->purchase->total,
                    'supplier_id' => $this->purchase->supplier_id,
                ],
                userId: actual_user_id(),
                branchId: auth()->user()->branch_id
            );

            if ($instance) {
                session()->flash('success', __('Approval workflow initiated successfully'));
                $this->loadWorkflowData();
            } else {
                session()->flash('info', __('No approval workflow configured for purchases'));
            }
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to initiate workflow: ').$e->getMessage());
        }
    }

    /**
     * Approve the pending approval
     */
    public function approve(): void
    {
        if (! $this->pendingApproval || ! $this->canApprove) {
            session()->flash('error', __('You are not authorized to approve this request'));

            return;
        }

        try {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $this->workflowService->approve(
                $this->pendingApproval,
                actual_user_id(),
                $this->approvalComments ?: null
            );

            session()->flash('success', __('Purchase approved successfully'));
            $this->showApproveModal = false;
            $this->approvalComments = '';
            $this->loadWorkflowData();
            $this->dispatch('purchase-approved');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Reject the pending approval
     */
    public function reject(): void
    {
        if (! $this->pendingApproval || ! $this->canApprove) {
            session()->flash('error', __('You are not authorized to reject this request'));

            return;
        }

        $this->validate([
            'rejectionReason' => 'required|string|min:10',
        ], [
            'rejectionReason.required' => __('Please provide a reason for rejection'),
            'rejectionReason.min' => __('Rejection reason must be at least 10 characters'),
        ]);

        try {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $this->workflowService->reject(
                $this->pendingApproval,
                actual_user_id(),
                $this->rejectionReason
            );

            session()->flash('success', __('Purchase rejected'));
            $this->showRejectModal = false;
            $this->rejectionReason = '';
            $this->loadWorkflowData();
            $this->dispatch('purchase-rejected');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Cancel the workflow
     */
    public function cancelWorkflow(): void
    {
        if (! $this->workflowInstance || $this->workflowInstance->isCompleted()) {
            return;
        }

        try {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $this->workflowService->cancel(
                $this->workflowInstance,
                actual_user_id(),
                __('Cancelled by user')
            );

            session()->flash('success', __('Approval workflow cancelled'));
            $this->loadWorkflowData();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    #[On('refresh-approval')]
    public function refresh(): void
    {
        $this->loadWorkflowData();
    }

    public function render()
    {
        return view('livewire.purchases.approval-panel');
    }
}
