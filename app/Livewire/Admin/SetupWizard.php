<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\Module;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class SetupWizard extends Component
{
    // Current step
    public int $step = 1;

    public int $totalSteps = 5;

    // Step 1: Company Info
    public string $companyName = '';

    public string $companyNameAr = '';

    public string $companyPhone = '';

    public string $companyEmail = '';

    public string $companyAddress = '';

    public string $timezone = 'Africa/Cairo';

    public string $currency = 'EGP';

    public string $locale = 'ar';

    // Step 2: Admin User
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPasswordConfirmation = '';

    // Step 3: Main Branch
    public string $branchName = '';

    public string $branchCode = '';

    public string $branchPhone = '';

    public string $branchAddress = '';

    // Step 4: Select Modules
    public array $selectedModules = [];

    // Step 5: Review (no inputs needed)

    // Completion status
    public bool $setupComplete = false;

    protected function rules(): array
    {
        return match ($this->step) {
            1 => [
                'companyName' => 'required|string|max:255',
                'companyEmail' => 'required|email|max:255',
                'timezone' => 'required|string',
                'currency' => 'required|string|size:3',
                'locale' => 'required|in:en,ar',
            ],
            2 => [
                'adminName' => 'required|string|max:255',
                'adminEmail' => 'required|email|max:255|unique:users,email',
                'adminPassword' => 'required|string|min:8|same:adminPasswordConfirmation',
            ],
            3 => [
                'branchName' => 'required|string|max:255',
                'branchCode' => 'required|string|max:20|unique:branches,code',
            ],
            4 => [
                'selectedModules' => 'required|array|min:1',
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'companyName.required' => __('Company name is required'),
            'adminEmail.unique' => __('This email is already registered'),
            'adminPassword.same' => __('Passwords do not match'),
            'adminPassword.min' => __('Password must be at least 8 characters'),
            'branchCode.unique' => __('This branch code already exists'),
            'selectedModules.required' => __('Please select at least one module'),
            'selectedModules.min' => __('Please select at least one module'),
        ];
    }

    public function mount(): void
    {
        // Check if setup is already complete
        $setupComplete = SystemSetting::where('setting_key', 'setup_wizard_complete')->value('value');
        if ($setupComplete === 'true' || $setupComplete === '1') {
            $this->setupComplete = true;
        }

        // Check if user has permission
        // Use case-insensitive role check - seeder uses "Super Admin" (Title Case)
        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['Super Admin', 'super-admin']) && ! $user->can('settings.manage')) {
            abort(403, __('Unauthorized'));
        }

        // Pre-select core modules
        $coreModules = Module::where('is_core', true)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->selectedModules = $coreModules;
    }

    public function nextStep(): void
    {
        $this->validate();

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->step + 1) {
            $this->step = $step;
        }
    }

    public function getAvailableModulesProperty()
    {
        return Module::where('is_active', true)
            ->orderBy('is_core', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTimezonesProperty(): array
    {
        return [
            'Africa/Cairo' => 'Cairo (UTC+2)',
            'Asia/Riyadh' => 'Riyadh (UTC+3)',
            'Asia/Dubai' => 'Dubai (UTC+4)',
            'Europe/London' => 'London (UTC+0)',
            'America/New_York' => 'New York (UTC-5)',
            'America/Los_Angeles' => 'Los Angeles (UTC-8)',
        ];
    }

    public function getCurrenciesProperty(): array
    {
        return [
            'EGP' => 'Egyptian Pound (EGP)',
            'SAR' => 'Saudi Riyal (SAR)',
            'AED' => 'UAE Dirham (AED)',
            'USD' => 'US Dollar (USD)',
            'EUR' => 'Euro (EUR)',
            'GBP' => 'British Pound (GBP)',
        ];
    }

    public function completeSetup(): void
    {
        // Final validation
        $this->step = 1;
        $this->validate();
        $this->step = 2;
        $this->validate();
        $this->step = 3;
        $this->validate();
        $this->step = 4;
        $this->validate();
        $this->step = 5;

        // Use database transaction to prevent race conditions
        DB::transaction(function () {
            // Create branch first
            $branch = Branch::create([
                'name' => $this->branchName,
                'code' => $this->branchCode,
                'phone' => $this->branchPhone,
                'address' => $this->branchAddress,
                'timezone' => $this->timezone,
                'currency' => $this->currency,
                'is_active' => true,
                'is_main' => true,
            ]);

            // Attach selected modules to branch
            foreach ($this->selectedModules as $moduleId) {
                $module = Module::find($moduleId);
                if ($module) {
                    $branch->modules()->attach($moduleId, [
                        'enabled' => true,
                        'module_key' => $module->module_key,
                    ]);
                }
            }

            // Create admin user
            $user = User::create([
                'name' => $this->adminName,
                'email' => $this->adminEmail,
                'password' => Hash::make($this->adminPassword),
                'branch_id' => $branch->id,
                'locale' => $this->locale,
                'is_active' => true,
            ]);

            // Get or create super-admin role (using firstOrCreate is safe within transaction)
            $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $user->assignRole($superAdminRole);

            // Save company settings
            $settings = [
                'company_name' => $this->companyName,
                'company_name_ar' => $this->companyNameAr,
                'company_email' => $this->companyEmail,
                'company_phone' => $this->companyPhone,
                'company_address' => $this->companyAddress,
                'timezone' => $this->timezone,
                'currency' => $this->currency,
                'locale' => $this->locale,
                'setup_wizard_complete' => 'true',
            ];

            foreach ($settings as $key => $value) {
                SystemSetting::updateOrCreate(
                    ['setting_key' => $key],
                    ['value' => $value]
                );
            }
        });

        $this->setupComplete = true;
        session()->flash('success', __('Setup completed successfully! Please login with your admin credentials.'));
    }

    public function skipSetup()
    {
        SystemSetting::updateOrCreate(
            ['key' => 'setup_wizard_complete'],
            ['value' => 'true']
        );

        return redirect()->route('dashboard');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.setup-wizard', [
            'modules' => $this->availableModules,
            'timezones' => $this->timezones,
            'currencies' => $this->currencies,
        ]);
    }
}
