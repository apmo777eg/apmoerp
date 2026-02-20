<?php

declare(strict_types=1);

namespace App\Livewire\Hrm\Shifts;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;

    public ?int $shiftId = null;

    public string $name = '';

    public string $code = '';

    public string $startTime = '09:00';

    public string $endTime = '17:00';

    public int $gracePeriodMinutes = 15;

    public array $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public string $description = '';

    public bool $isActive = true;

    public bool $overrideCode = false;

    protected array $daysOfWeek = [
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
    ];

    public function mount(?int $shift = null): void
    {
        $this->authorize('hrm.manage');

        if ($shift) {
            $this->shiftId = $shift;
            $this->loadShift();
            $this->overrideCode = true; // When editing, code is already set
        }
    }

    protected function loadShift(): void
    {
        $shift = Shift::findOrFail($this->shiftId);

        $this->name = $shift->name ?? '';
        $this->code = $shift->code ?? '';
        $this->startTime = $shift->start_time ?? '09:00';
        $this->endTime = $shift->end_time ?? '17:00';
        $this->gracePeriodMinutes = $shift->grace_period_minutes ?? 15;
        $this->workingDays = $shift->working_days ?? [];
        $this->description = $shift->description ?? '';
        $this->isActive = $shift->is_active ?? true;
    }

    public function updatedName(): void
    {
        // Auto-generate code from name if not overriding and creating new
        if (! $this->overrideCode && ! $this->shiftId) {
            $this->code = $this->generateCode();
        }
    }

    protected function generateCode(): string
    {
        $prefix = 'SH';
        $base = strtoupper(Str::slug(Str::limit($this->name, 10, ''), ''));

        if (empty($base)) {
            // V8-HIGH-N02 FIX: Use lockForUpdate and filter by branch to prevent race condition
            $branchId = auth()->user()?->branch_id;
            $lastShift = Shift::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $seq = $lastShift ? ($lastShift->id % 1000) + 1 : 1;
            $base = sprintf('%03d', $seq);
        }

        $code = $prefix.'-'.$base;
        $counter = 1;

        while (Shift::where('code', $code)->where('id', '!=', $this->shiftId)->exists()) {
            $code = $prefix.'-'.$base.$counter;
            $counter++;
        }

        return $code;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:shifts,code'.($this->shiftId ? ','.$this->shiftId : ''),
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i|after:startTime',
            'gracePeriodMinutes' => 'required|integer|min:0|max:120',
            'workingDays' => 'array',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function save(): mixed
    {
        $this->authorize('hrm.manage');

        // V30-HIGH-03 FIX: Wrap code generation + create in DB transaction
        // lockForUpdate() has no effect outside a transaction
        return DB::transaction(function () {
            // Auto-generate code if empty
            if (empty($this->code)) {
                $this->code = $this->generateCode();
            }

            $this->validate();

            $branchId = auth()->user()?->branch_id ?? null;

            $data = [
                'name' => $this->name,
                'code' => $this->code,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'grace_period_minutes' => $this->gracePeriodMinutes,
                'working_days' => $this->workingDays,
                'description' => $this->description,
                'is_active' => $this->isActive,
                'branch_id' => $branchId,
            ];

            if ($this->shiftId) {
                Shift::findOrFail($this->shiftId)->update($data);
                session()->flash('success', __('Shift updated successfully'));
            } else {
                Shift::create($data);
                session()->flash('success', __('Shift created successfully'));
            }

            $this->redirectRoute('app.hrm.shifts.index', navigate: true);
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.hrm.shifts.form', [
            'daysOfWeek' => $this->daysOfWeek,
        ]);
    }
}
