<?php

declare(strict_types=1);

namespace App\Livewire\Banking;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('banking.view');
    }

    public function render()
    {
        return view('livewire.banking.index');
    }
}
