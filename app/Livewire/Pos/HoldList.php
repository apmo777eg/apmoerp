<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
class HoldList extends Component
{
    public array $holds = [];

    public function mount(): void
    {
        $this->loadHolds();
    }

    #[On('holdUpdated')]
    public function loadHolds(): void
    {
        // Load holds from session or database
        $this->holds = session()->get('pos_holds', []);
    }

    public function resumeHold(int $index): void
    {
        if (isset($this->holds[$index])) {
            $hold = $this->holds[$index];

            // Remove from holds
            unset($this->holds[$index]);
            $this->holds = array_values($this->holds);
            session()->put('pos_holds', $this->holds);

            // Dispatch event to resume the cart
            $this->dispatch('resumeCart', cart: $hold);
        }
    }

    public function deleteHold(int $index): void
    {
        if (isset($this->holds[$index])) {
            unset($this->holds[$index]);
            $this->holds = array_values($this->holds);
            session()->put('pos_holds', $this->holds);

            $this->dispatch('notify', type: 'success', message: __('Hold deleted successfully'));
        }
    }

    public function render()
    {
        return view('livewire.pos.hold-list');
    }
}
