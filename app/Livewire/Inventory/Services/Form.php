<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Services;

use App\Models\Module;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    public ?int $productId = null;

    public ?int $moduleId = null;

    public string $name = '';

    public string $code = '';

    public string $sku = '';

    public ?string $description = null;

    public float $defaultPrice = 0;

    public float $cost = 0;

    public ?float $hourlyRate = null;

    public ?int $serviceDuration = null;

    public string $durationUnit = 'hours';

    public ?int $taxId = null;

    public string $status = 'active';

    public string $notes = '';

    public function mount(?int $service = null, ?int $moduleId = null): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('inventory.products.view')) {
            abort(403);
        }

        $this->moduleId = $moduleId ? $this->validateServiceModule($moduleId) : null;

        if ($service) {
            $this->authorizeAction('update');
            $this->productId = $service;
            $this->loadService();
        } else {
            $this->authorizeAction('create');
        }
    }

    protected function authorizeAction(string $action): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, __('Unauthorized'));
        }

        $permission = match ($action) {
            'create' => 'inventory.products.create',
            'update' => 'inventory.products.update',
            'view' => 'inventory.products.view',
            default => null,
        };

        if ($permission && ! $user->can($permission)) {
            abort(403, __('Unauthorized'));
        }
    }

    protected function requireUserBranch(): int
    {
        $user = Auth::user();

        if (! $user || ! $user->branch_id) {
            abort(403, __('User must be assigned to a branch to perform this action'));
        }

        return $user->branch_id;
    }

    protected function authorizeProductBranch(Product $product): void
    {
        $userBranchId = $this->requireUserBranch();

        if ($product->branch_id !== $userBranchId) {
            abort(403, __('Access denied to product from another branch'));
        }
    }

    protected function validateServiceModule(?int $moduleId): ?int
    {
        if (! $moduleId) {
            return null;
        }

        $module = Module::where('id', $moduleId)
            ->where(function ($query) {
                $query->where('is_service', true)
                    ->orWhere('key', 'services');
            })
            ->first();

        if (! $module) {
            abort(422, __('Invalid or non-service module'));
        }

        return $module->id;
    }

    protected function loadService(): void
    {
        $product = Product::find($this->productId);
        if (! $product) {
            abort(404, __('Product not found'));
        }

        $this->authorizeProductBranch($product);

        $this->moduleId = $product->module_id;
        $this->name = $product->name;
        $this->code = $product->code ?? '';
        $this->sku = $product->sku ?? '';
        // V51-CRIT-02 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for prices
        $this->defaultPrice = decimal_float($product->default_price, 4);
        $this->cost = decimal_float($product->cost ?: $product->standard_cost, 4);
        $this->hourlyRate = $product->hourly_rate;
        $this->serviceDuration = $product->service_duration;
        $this->durationUnit = $product->duration_unit ?? 'hours';
        $this->taxId = $product->tax_id;
        $this->status = $product->status;
        $this->notes = $product->notes ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'defaultPrice' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'hourlyRate' => 'nullable|numeric|min:0',
            'serviceDuration' => 'nullable|integer|min:1',
            'durationUnit' => 'required|in:minutes,hours,days',
            'taxId' => 'nullable|exists:taxes,id',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function save(): mixed
    {
        $userBranchId = $this->requireUserBranch();

        if ($this->productId) {
            $this->authorizeAction('update');
        } else {
            $this->authorizeAction('create');
        }

        $this->validate();

        $validatedModuleId = $this->validateServiceModule($this->moduleId);

        $data = [
            'name' => $this->name,
            'code' => $this->code ?: null,
            'sku' => $this->sku ?: null,
            'module_id' => $validatedModuleId,
            'type' => 'service',
            'product_type' => 'service',
            'default_price' => $this->defaultPrice,
            'standard_cost' => $this->cost,
            'cost' => $this->cost,
            'hourly_rate' => $this->hourlyRate,
            'service_duration' => $this->serviceDuration,
            'duration_unit' => $this->durationUnit,
            'tax_id' => $this->taxId,
            'status' => $this->status,
            'notes' => $this->notes ?: null,
            'is_serialized' => false,
            'is_batch_tracked' => false,
        ];

        if ($this->productId) {
            $product = Product::find($this->productId);
            if (! $product) {
                abort(404, __('Product not found'));
            }

            $this->authorizeProductBranch($product);
            $product->update($data);
            session()->flash('success', __('Service updated successfully'));
        } else {
            $data['created_by'] = Auth::id();
            $data['branch_id'] = $userBranchId;
            $product = Product::create($data);
            session()->flash('success', __('Service created successfully'));
        }

        $this->redirectRoute('app.inventory.products.index', navigate: true);
    }

    /**
     * Hours per day constant for service duration calculations.
     * This value can be overridden via config or settings.
     */
    protected const HOURS_PER_DAY = 8;

    public function calculateFromHourly(): void
    {
        if ($this->hourlyRate && $this->serviceDuration) {
            $hoursPerDay = config('services.hours_per_day', self::HOURS_PER_DAY);
            $hours = match ($this->durationUnit) {
                'minutes' => bcdiv((string) $this->serviceDuration, '60', 4),
                'hours' => (string) $this->serviceDuration,
                'days' => bcmul((string) $this->serviceDuration, (string) $hoursPerDay, 4),
                default => (string) $this->serviceDuration,
            };
            $calculated = bcmul((string) $this->hourlyRate, $hours, 4);
            // V51-CRIT-02 FIX: Use bcround() with scale 4 to match decimal:4 schema for prices
            $this->defaultPrice = decimal_float(bcround($calculated, 4), 4);
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $modules = Module::where(function ($query) {
            $query->where('is_service', true)
                ->orWhere('key', 'services');
        })
            ->orderBy('name')
            ->get();

        $taxes = Tax::where('is_active', true)->orderBy('name')->get();

        return view('livewire.inventory.services.form', [
            'modules' => $modules,
            'taxes' => $taxes,
        ]);
    }
}
