<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Livewire\BaseComponent as Component;
class LoadingSpinner extends Component
{
    public function render()
    {
        return view('livewire.shared.loading-spinner');
    }
}
