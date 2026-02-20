<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Livewire\BaseComponent as Component;
class ErrorMessage extends Component
{
    public string $message = '';

    public function render()
    {
        return view('livewire.shared.error-message');
    }
}
