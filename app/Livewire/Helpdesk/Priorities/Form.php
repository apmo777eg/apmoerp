<?php

declare(strict_types=1);

namespace App\Livewire\Helpdesk\Priorities;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\TicketPriority;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;

    public ?int $priorityId = null;

    public string $name = '';

    public string $name_ar = '';

    public int $level = 1;

    public string $color = '#3B82F6';

    public int $response_time_minutes = 60;

    public int $resolution_time_minutes = 480;

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(?int $priority = null): void
    {
        $this->authorize('helpdesk.manage');

        if ($priority) {
            $this->priorityId = $priority;
            $this->loadPriority();
        }
    }

    protected function loadPriority(): void
    {
        $priority = TicketPriority::findOrFail($this->priorityId);

        $this->name = $priority->name;
        $this->name_ar = $priority->name_ar ?? '';
        $this->level = $priority->level;
        $this->color = $priority->color ?? '#3B82F6';
        $this->response_time_minutes = $priority->response_time_minutes;
        $this->resolution_time_minutes = $priority->resolution_time_minutes;
        $this->is_active = $priority->is_active;
        $this->sort_order = $priority->sort_order;
    }

    protected function rules(): array
    {
        return [
            'name' => $this->multilingualString(required: true, max: 100),
            'name_ar' => $this->multilingualString(required: false, max: 100),
            'level' => 'required|integer|min:1|max:5',
            'color' => 'nullable|string|max:20',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function save(): mixed
    {
        $this->authorize('helpdesk.manage');

        $this->validate();

        $data = [
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'level' => $this->level,
            'color' => $this->color,
            'response_time_minutes' => $this->response_time_minutes,
            'resolution_time_minutes' => $this->resolution_time_minutes,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->priorityId) {
            $priority = TicketPriority::findOrFail($this->priorityId);
            $priority->update($data);
            session()->flash('success', __('Priority updated successfully'));
        } else {
            TicketPriority::create($data);
            session()->flash('success', __('Priority created successfully'));
        }

        $this->redirectRoute('app.helpdesk.priorities.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.helpdesk.priorities.form');
    }
}
