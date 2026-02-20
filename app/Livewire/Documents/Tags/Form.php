<?php

declare(strict_types=1);

namespace App\Livewire\Documents\Tags;

use App\Models\DocumentTag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
class Form extends Component
{
    use AuthorizesRequests;

    public ?int $tagId = null;

    public string $name = '';

    public string $color = '#3B82F6';

    public string $description = '';

    public function mount(?int $tag = null): void
    {
        $this->authorize('documents.tags.manage');

        if ($tag) {
            $this->tagId = $tag;
            $this->loadTag();
        }
    }

    protected function loadTag(): void
    {
        $tag = DocumentTag::findOrFail($this->tagId);
        $this->name = $tag->name;
        $this->color = $tag->color ?? '#3B82F6';
        $this->description = $tag->description ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:document_tags,name,'.($this->tagId ?? 'NULL'),
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ];
    }

    public function save(): mixed
    {
        $this->authorize('documents.tags.manage');
        $this->validate();

        $data = [
            'name' => $this->name,
            'color' => $this->color,
            'description' => $this->description,
        ];

        if ($this->tagId) {
            $tag = DocumentTag::findOrFail($this->tagId);
            $tag->update($data);
            session()->flash('success', __('Tag updated successfully'));
        } else {
            DocumentTag::create($data);
            session()->flash('success', __('Tag created successfully'));
        }

        $this->redirectRoute('app.documents.tags.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.documents.tags.form');
    }
}
