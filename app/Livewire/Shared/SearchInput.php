<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Livewire\BaseComponent as Component;
class SearchInput extends Component
{
    public ?string $query = null;

    public function updatedQuery(): void
    {
        $this->dispatch('search-updated', query: $this->query);
    }

    public function render()
    {
        return view('livewire.shared.search-input');
    }
}
