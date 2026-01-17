<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing\WorkCenters;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;

    public ?WorkCenter $workCenter = null;

    public bool $editMode = false;

    public string $code = '';

    public string $name = '';

    public string $name_ar = '';

    public string $description = '';

    public string $type = 'manual';

    public ?float $capacity_per_hour = null;

    public float $cost_per_hour = 0.0;

    public string $status = 'active';

    public bool $overrideCode = false;

    protected function rules(): array
    {
        $workCenterId = $this->workCenter?->id;

        return [
            'code' => ['required', 'string', 'max:50', 'unique:work_centers,code,'.$workCenterId],
            'name' => $this->multilingualString(required: true, max: 255),
            'name_ar' => $this->multilingualString(required: false, max: 255),
            'description' => $this->unicodeText(required: false, max: 2000),
            'type' => ['required', 'in:manual,machine,assembly,quality_control,packaging'],
            'capacity_per_hour' => ['nullable', 'numeric', 'min:0'],
            'cost_per_hour' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,maintenance,inactive'],
        ];
    }

    public function mount(?WorkCenter $workCenter = null): void
    {
        if ($workCenter && $workCenter->exists) {
            $this->authorize('manufacturing.edit');
            $this->workCenter = $workCenter;
            $this->editMode = true;
            $this->fillFormFromModel();
            $this->overrideCode = true; // When editing, code is already set
        } else {
            $this->authorize('manufacturing.create');
        }
    }

    protected function fillFormFromModel(): void
    {
        $this->code = $this->workCenter->code;
        $this->name = $this->workCenter->name;
        $this->name_ar = $this->workCenter->name_ar ?? '';
        $this->description = $this->workCenter->description ?? '';
        $this->type = $this->workCenter->type;
        $this->capacity_per_hour = $this->workCenter->capacity_per_hour ? (float) $this->workCenter->capacity_per_hour : null;
        $this->cost_per_hour = (float) $this->workCenter->cost_per_hour;
        $this->status = $this->workCenter->status;
    }

    public function updatedName(): void
    {
        // Auto-generate code from name if not overriding and creating new
        if (! $this->overrideCode && ! $this->editMode) {
            $this->code = $this->generateCode();
        }
    }

    protected function generateCode(): string
    {
        $prefix = 'WC';
        $base = strtoupper(Str::slug(Str::limit($this->name, 10, ''), ''));

        if (empty($base)) {
            // V32-HIGH-A03 FIX: Don't fallback to Branch::first() - use user's assigned branch
            $user = auth()->user();
            $branchId = $user?->branch_id;

            // If no branch is assigned, the save() method will reject the request anyway
            // Generate a temporary sequential code based on existing records
            $lastWc = WorkCenter::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $seq = $lastWc ? ($lastWc->id % 1000) + 1 : 1;
            $base = sprintf('%03d', $seq);
        }

        $code = $prefix.'-'.$base;
        $counter = 1;
        $workCenterId = $this->workCenter?->id;

        // Ensure uniqueness by checking existing codes and incrementing suffix
        while (WorkCenter::where('code', $code)->where('id', '!=', $workCenterId)->exists()) {
            $code = $prefix.'-'.$base.$counter;
            $counter++;
        }

        return $code;
    }

    public function save(): mixed
    {
        $user = auth()->user();
        $branchId = $user->branch_id;

        // V32-HIGH-A04 FIX: Don't fallback to Branch::first() as it may assign records to wrong branch
        // If user has no branch assigned, they should not be able to create records
        if (! $branchId) {
            session()->flash('error', __('No branch assigned to your account. Please contact your administrator.'));

            return null;
        }

        // V23-HIGH-09 FIX: Wrap code generation and create in DB transaction
        // The lockForUpdate() in generateCode() is ineffective without a transaction
        return \Illuminate\Support\Facades\DB::transaction(function () use ($branchId) {
            // Auto-generate code if empty (inside transaction for effective locking)
            if (empty($this->code)) {
                $this->code = $this->generateCode();
            }

            $this->validate();

            $data = [
                'branch_id' => $branchId,
                'code' => $this->code,
                'name' => $this->name,
                'name_ar' => $this->name_ar,
                'description' => $this->description,
                'type' => $this->type,
                'capacity_per_hour' => $this->capacity_per_hour,
                'cost_per_hour' => $this->cost_per_hour,
                'status' => $this->status,
            ];

            if ($this->editMode) {
                $this->workCenter->update($data);
                session()->flash('success', __('Work Center updated successfully.'));
            } else {
                WorkCenter::create($data);
                session()->flash('success', __('Work Center created successfully.'));
            }

            $this->redirectRoute('app.manufacturing.work-centers.index', navigate: true);
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.manufacturing.work-centers.form');
    }
}
