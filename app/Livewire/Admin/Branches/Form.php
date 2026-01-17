<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branches;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use HandlesErrors;
    use HasMultilingualValidation;

    public ?int $branchId = null;

    /**
     * @var array<string,mixed>
     */
    public array $form = [
        'name' => '',
        'code' => '',
        'address' => '',
        'phone' => '',
        'timezone' => '',
        'currency' => 'EGP',
        'is_active' => true,
        'is_main' => false,
    ];

    /**
     * Available modules for selection
     *
     * @var array<int,array<string,mixed>>
     */
    public array $availableModules = [];

    /**
     * Selected module IDs
     *
     * @var array<int>
     */
    public array $selectedModules = [];

    /**
     * @var array<int,array<string,mixed>>
     */
    public array $schema = [];

    public function mount(?Branch $branch = null): void
    {
        $user = Auth::user();

        // Check appropriate permission based on create/edit mode
        // Using config-based permission check to align with routes
        $requiredPermission = config('screen_permissions.admin.branches.index', 'branches.view');
        if (! $user || ! $user->can($requiredPermission)) {
            abort(403, __('Unauthorized access'));
        }

        $this->branchId = $branch?->id;
        $branchModel = $branch;

        // Get available timezones and currencies for dropdowns
        $timezones = \DateTimeZone::listIdentifiers();

        // Get currencies with fallback if table is empty
        try {
            $currencies = \App\Models\Currency::active()->ordered()->pluck('code', 'code')->toArray();

            // Fallback to common currencies if database is empty
            if (empty($currencies)) {
                $currencies = $this->getDefaultCurrencies();
            }
        } catch (\Illuminate\Database\QueryException|\PDOException $e) {
            // Log the error for debugging while providing fallback for user
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            Log::warning('Currency table access failed, using default currencies', [
                'error' => $e->getMessage(),
                'user_id' => actual_user_id(),
            ]);

            // Fallback if currencies table doesn't exist or has database errors
            $currencies = $this->getDefaultCurrencies();
        }

        $this->schema = [
            ['name' => 'name',      'label' => __('Name'),      'type' => 'text'],
            ['name' => 'code',      'label' => __('Code'),      'type' => 'text'],
            ['name' => 'address',   'label' => __('Address'),   'type' => 'textarea'],
            ['name' => 'phone',     'label' => __('Phone'),     'type' => 'text'],
            ['name' => 'timezone',  'label' => __('Timezone'),  'type' => 'select', 'options' => array_combine($timezones, $timezones)],
            ['name' => 'currency',  'label' => __('Currency'),  'type' => 'select', 'options' => $currencies],
        ];

        // Load available modules
        $this->availableModules = Module::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'key' => $m->key,
                'name' => $m->localized_name,
                'description' => $m->localized_description,
                'is_core' => $m->is_core,
                'icon' => $m->icon,
            ])
            ->all();

        if ($branchModel) {
            $this->form['name'] = $branchModel->name;
            $this->form['code'] = $branchModel->code ?? '';
            $this->form['address'] = $branchModel->address ?? '';
            $this->form['phone'] = $branchModel->phone ?? '';
            $this->form['timezone'] = $branchModel->timezone ?? config('app.timezone');
            $this->form['currency'] = $branchModel->currency ?? 'EGP';
            $this->form['is_active'] = (bool) $branchModel->is_active;
            $this->form['is_main'] = (bool) $branchModel->is_main;

            // Load selected modules for this branch
            $this->selectedModules = BranchModule::query()
                ->where('branch_id', $branchModel->id)
                ->where('enabled', true)
                ->pluck('module_id')
                ->all();
        } else {
            $this->form['timezone'] = config('app.timezone');

            // Pre-select core modules for new branches
            $this->selectedModules = collect($this->availableModules)
                ->where('is_core', true)
                ->pluck('id')
                ->all();
        }
    }

    #[On('dynamic-form-updated')]
    public function syncForm(array $data): void
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->form)) {
                $this->form[$key] = $value;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->ignore($this->branchId),
            ],
            'form.address' => ['nullable', 'string', 'max:500'],
            'form.phone' => ['nullable', 'string', 'max:50'],
            'form.timezone' => ['required', 'string', 'max:64'],
            'form.currency' => ['required', 'string', 'max:10'],
            'form.is_active' => ['boolean'],
            'form.is_main' => ['boolean'],
        ];
    }

    public function save(): mixed
    {
        $validated = $this->validate();
        $data = $this->form;
        $branchId = $this->branchId;
        $selectedModules = $this->selectedModules;
        $availableModules = $this->availableModules;

        return $this->handleOperation(
            operation: function () use ($data, $branchId, $selectedModules, $availableModules) {
                DB::transaction(function () use ($data, $branchId, $selectedModules, $availableModules) {
                    if ($branchId) {
                        $branch = Branch::findOrFail($branchId);
                    } else {
                        $branch = new Branch;
                    }

                    $branch->name = $data['name'];
                    $branch->code = $data['code'];
                    $branch->address = $data['address'] ?: null;
                    $branch->phone = $data['phone'] ?: null;
                    $branch->timezone = $data['timezone'];
                    $branch->currency = $data['currency'];
                    $branch->is_active = (bool) $data['is_active'];
                    $branch->is_main = (bool) $data['is_main'];

                    $branch->save();

                    // Sync branch modules
                    foreach ($availableModules as $module) {
                        $moduleId = $module['id'];
                        $enabled = in_array($moduleId, $selectedModules);

                        BranchModule::updateOrCreate(
                            [
                                'branch_id' => $branch->id,
                                'module_id' => $moduleId,
                            ],
                            [
                                'module_key' => $module['key'],
                                'enabled' => $enabled,
                                'settings' => [],
                            ]
                        );
                    }
                });
            },
            successMessage: $this->branchId
                ? __('Branch updated successfully.')
                : __('Branch created successfully.'),
            redirectRoute: 'admin.branches.index'
        );
    }

    /**
     * Get default currencies as fallback when database is empty or unavailable
     *
     * @return array<string, string>
     */
    private function getDefaultCurrencies(): array
    {
        return [
            'EGP' => 'EGP',
            'USD' => 'USD',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            'SAR' => 'SAR',
        ];
    }

    public function render()
    {
        return view('livewire.admin.branches.form');
    }
}
