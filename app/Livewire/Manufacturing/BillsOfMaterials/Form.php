<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\BillsOfMaterials;

use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use \App\Http\Requests\Traits\HasMultilingualValidation;
    use AuthorizesRequests;

    public ?BillOfMaterial $bom = null;

    public bool $editMode = false;

    public ?int $product_id = null;

    public string $name = '';

    public string $name_ar = '';

    public string $description = '';

    public float $quantity = 1.0;

    public string $status = 'draft';

    public float $scrap_percentage = 0.0;

    public bool $is_multi_level = false;

    protected function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'name' => $this->multilingualString(required: true, max: 255),
            'name_ar' => $this->multilingualString(required: false, max: 255),
            'description' => $this->unicodeText(required: false, max: 2000),
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', 'in:draft,active,archived'],
            'scrap_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_multi_level' => ['boolean'],
        ];
    }

    public function mount(?BillOfMaterial $bom = null): void
    {
        if ($bom && $bom->exists) {
            $this->authorize('manufacturing.edit');
            $this->bom = $bom;
            $this->editMode = true;
            $this->fillFormFromModel();
        } else {
            $this->authorize('manufacturing.create');
        }
    }

    protected function fillFormFromModel(): void
    {
        $this->product_id = $this->bom->product_id;
        $this->name = $this->bom->name;
        $this->name_ar = $this->bom->name_ar ?? '';
        $this->description = $this->bom->description ?? '';
        $this->quantity = (float) $this->bom->quantity;
        $this->status = $this->bom->status;
        $this->scrap_percentage = (float) $this->bom->scrap_percentage;
        $this->is_multi_level = (bool) $this->bom->is_multi_level;
    }

    public function save(): mixed
    {
        $this->validate();

        $user = auth()->user();
        $branchId = $user->branch_id;

        // V32-HIGH-A01 FIX: Don't fallback to Branch::first() as it may assign records to wrong branch
        // If user has no branch assigned, they should not be able to create records
        if (! $branchId) {
            session()->flash('error', __('No branch assigned to your account. Please contact your administrator.'));

            return null;
        }

        $data = [
            'branch_id' => $branchId,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'scrap_percentage' => $this->scrap_percentage,
            'is_multi_level' => $this->is_multi_level,
        ];

        if ($this->editMode) {
            $this->bom->update($data);
            session()->flash('success', __('BOM updated successfully.'));
        } else {
            $data['bom_number'] = BillOfMaterial::generateBomNumber($branchId);
            BillOfMaterial::create($data);
            session()->flash('success', __('BOM created successfully.'));
        }

        $this->redirectRoute('app.manufacturing.boms.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        // V23-HIGH-07 FIX: Handle branch-less users properly
        // Don't use 'where branch_id = null' which returns nothing
        $user = auth()->user();
        $branchId = $user->branch_id ?? null;

        $products = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('livewire.manufacturing.bills-of-materials.form', [
            'products' => $products,
        ]);
    }
}
