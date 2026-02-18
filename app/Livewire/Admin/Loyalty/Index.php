<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Loyalty;

use App\Models\Customer;
use App\Models\LoyaltySetting;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $tier = '';

    public bool $showSettingsModal = false;

    public bool $showAdjustModal = false;

    public ?int $selectedCustomerId = null;

    public int $adjustPoints = 0;

    public string $adjustReason = '';

    public float $points_per_amount = 1;

    public float $amount_per_point = 100;

    public float $redemption_rate = 0.01;

    public int $min_points_redeem = 100;

    public ?int $points_expiry_days = null;

    public bool $is_active = true;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('customers.loyalty.manage')) {
            abort(403);
        }

        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $settings = LoyaltySetting::getForBranch(Auth::user()?->branch_id);
        if ($settings) {
            // V38-FINANCE-01 FIX: Use decimal_float() for proper precision handling
            $this->points_per_amount = decimal_float($settings->points_per_amount);
            $this->amount_per_point = decimal_float($settings->amount_per_point);
            $this->redemption_rate = decimal_float($settings->redemption_rate);
            $this->min_points_redeem = (int) $settings->min_points_redeem;
            $this->points_expiry_days = $settings->points_expiry_days;
            $this->is_active = $settings->is_active;
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'points_per_amount' => 'required|numeric|min:0',
            'amount_per_point' => 'required|numeric|min:1',
            'redemption_rate' => 'required|numeric|min:0',
            'min_points_redeem' => 'required|integer|min:0',
            'points_expiry_days' => 'nullable|integer|min:1',
        ]);

        LoyaltySetting::updateOrCreate(
            ['branch_id' => Auth::user()?->branch_id],
            [
                'points_per_amount' => $this->points_per_amount,
                'amount_per_point' => $this->amount_per_point,
                'redemption_rate' => $this->redemption_rate,
                'min_points_redeem' => $this->min_points_redeem,
                'points_expiry_days' => $this->points_expiry_days,
                'is_active' => $this->is_active,
            ]
        );

        $this->showSettingsModal = false;
        session()->flash('success', __('Loyalty settings saved'));
    }

    public function openAdjustModal(int $customerId): void
    {
        $this->selectedCustomerId = $customerId;
        $this->adjustPoints = 0;
        $this->adjustReason = '';
        $this->showAdjustModal = true;
    }

    public function adjustPoints(): void
    {
        $this->validate([
            'adjustPoints' => 'required|integer|not_in:0',
            'adjustReason' => 'required|string|max:255',
        ]);

        $customer = Customer::findOrFail($this->selectedCustomerId);
        $service = app(LoyaltyService::class);

        $service->adjustPoints($customer, $this->adjustPoints, $this->adjustReason, Auth::id());

        $this->showAdjustModal = false;
        session()->flash('success', __('Points adjusted successfully'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $branchId = Auth::user()?->branch_id;

        $query = Customer::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            // FIX: customers table uses loyalty_tier for loyalty segmentation
            ->when($this->tier, fn ($q) => $q->where('loyalty_tier', $this->tier))
            ->orderByDesc('loyalty_points');

        $stats = [
            'total_points' => Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('loyalty_points'),
            'vip_customers' => Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('loyalty_tier', 'vip')->count(),
            'premium_customers' => Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('loyalty_tier', 'premium')->count(),
        ];

        return view('livewire.admin.loyalty.index', [
            'customers' => $query->paginate(15),
            'stats' => $stats,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
