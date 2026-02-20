<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branch;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
/**
 * Branch Settings - Branch Admin Page
 * Allows branch admins to manage their branch settings
 */
class Settings extends Component
{
    public ?Branch $branch = null;

    // Settings form data
    public string $name = '';

    public string $code = '';

    public ?string $address = null;

    public ?string $phone = null;

    public string $timezone = 'UTC';

    public string $currency = 'EGP';

    public array $settings = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.settings.manage')) {
            abort(403);
        }

        $this->branch = $user->branch;

        if (! $this->branch) {
            abort(403, __('No branch assigned to this user.'));
        }

        // Check if user is a branch admin
        if (! $user->isBranchAdmin($this->branch->id) && ! $user->hasRole('Super Admin')) {
            abort(403);
        }

        $this->loadBranchData();
    }

    protected function loadBranchData(): void
    {
        if (! $this->branch) {
            return;
        }

        $this->name = $this->branch->name;
        $this->code = $this->branch->code;
        $this->address = $this->branch->address;
        $this->phone = $this->branch->phone;
        $this->timezone = $this->branch->timezone ?? 'UTC';
        $this->currency = $this->branch->currency ?? 'EGP';
        $this->settings = $this->branch->settings ?? [];
    }

    public function save(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.settings.manage')) {
            abort(403);
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,'.$this->branch->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'timezone' => 'required|string',
            'currency' => 'required|string|size:3',
        ]);

        $this->branch->update([
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'settings' => $this->settings,
        ]);

        session()->flash('success', __('Branch settings updated successfully.'));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.settings.manage')) {
            abort(403);
        }

        return view('livewire.admin.branch.settings', [
            'timezones' => timezone_identifiers_list(),
            'currencies' => ['EGP', 'USD', 'EUR', 'SAR', 'AED', 'KWD', 'QAR', 'BHD', 'OMR', 'JOD'],
        ]);
    }
}
