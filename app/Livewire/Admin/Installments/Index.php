<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Installments;

use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Services\InstallmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $status = 'active';

    public string $search = '';

    public bool $showPaymentModal = false;

    public ?int $selectedPaymentId = null;

    public float $paymentAmount = 0;

    public string $paymentMethod = 'cash';

    public string $paymentReference = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('sales.installments.view')) {
            abort(403);
        }
    }

    public function openPaymentModal(int $paymentId): void
    {
        $payment = InstallmentPayment::findOrFail($paymentId);
        $this->selectedPaymentId = $paymentId;
        $this->paymentAmount = decimal_float($payment->remaining_amount);
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->showPaymentModal = true;
    }

    public function recordPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string',
        ]);

        $payment = InstallmentPayment::findOrFail($this->selectedPaymentId);
        $service = app(InstallmentService::class);

        $service->recordPayment(
            $payment,
            $this->paymentAmount,
            $this->paymentMethod,
            $this->paymentReference ?: null,
            Auth::id()
        );

        $this->showPaymentModal = false;
        session()->flash('success', __('Payment recorded successfully'));
    }

    public function updateOverdue(): void
    {
        $service = app(InstallmentService::class);
        $count = $service->updateOverduePayments();

        session()->flash('success', __(':count payments marked as overdue', ['count' => $count]));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $branchId = Auth::user()?->branch_id;
        $service = app(InstallmentService::class);

        $query = InstallmentPlan::with(['customer', 'sale', 'payments'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('created_at');

        $stats = $service->getPlanStats($branchId);

        return view('livewire.admin.installments.index', [
            'plans' => $query->paginate(15),
            'stats' => $stats,
            'upcomingPayments' => $service->getUpcomingPayments($branchId, 7),
            'overduePayments' => $service->getOverduePayments($branchId),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }
}
