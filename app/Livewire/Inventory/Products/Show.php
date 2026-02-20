<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Products;

use App\Models\Product;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;
    public Product $product;

    public function mount(Product $product): void
    {
        $this->authorize('inventory.products.view');
        $this->product = $product->load(['category', 'unit', 'branch']);
    }

    public function render()
    {
        return view('livewire.inventory.products.show');
    }
}
