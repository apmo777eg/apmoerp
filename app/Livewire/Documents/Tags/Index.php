<?php

declare(strict_types=1);

namespace App\Livewire\Documents\Tags;

use App\Models\DocumentTag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('documents.tags.manage');
    }

    public function delete(int $id): void
    {
        // V57-CRITICAL-04 FIX: Re-authorize on mutation to prevent IDOR attacks
        $this->authorize('documents.tags.manage');
        
        $tag = DocumentTag::findOrFail($id);

        if ($tag->getDocumentCount() > 0) {
            session()->flash('error', __('Cannot delete tag that is being used'));

            return;
        }

        $tag->delete();
        session()->flash('success', __('Tag deleted successfully'));
        $this->resetPage();
    }

    public function render()
    {
        $tags = DocumentTag::withCount('documents')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.documents.tags.index', [
            'tags' => $tags,
        ]);
    }
}
