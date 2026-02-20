<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Versions extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Document $document;

    public ?\Illuminate\Http\UploadedFile $file = null;

    public string $changeNotes = '';

    protected DocumentService $documentService;

    public function boot(DocumentService $documentService): void
    {
        $this->documentService = $documentService;
    }

    public function mount(Document $document): void
    {
        $this->authorize('documents.versions.manage');
        $this->document = $document->load(['versions.uploader']);

        $user = auth()->user();
        if ($user && $user->branch_id && $document->branch_id && $user->branch_id !== $document->branch_id) {
            abort(403, 'You cannot manage versions for documents from other branches.');
        }

        // Check if user can access this document
        if (! $document->canBeAccessedBy(auth()->user())) {
            abort(403, 'You do not have permission to manage versions for this document');
        }
    }

    public function uploadVersion(): void
    {
        $allowedExtensions = implode(',', DocumentService::ALLOWED_EXTENSIONS);
        $allowedMimeTypes = implode(',', DocumentService::ALLOWED_MIME_TYPES);

        $this->validate([
            'file' => "required|file|max:51200|mimes:{$allowedExtensions}|mimetypes:{$allowedMimeTypes}",
            'changeNotes' => 'nullable|string',
        ]);

        $this->documentService->uploadVersion($this->document, $this->file, $this->changeNotes);

        session()->flash('success', __('New version uploaded successfully'));
        $this->document->refresh();
        $this->reset(['file', 'changeNotes']);
    }

    public function render()
    {
        return view('livewire.documents.versions');
    }
}
