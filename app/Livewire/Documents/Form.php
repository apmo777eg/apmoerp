<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Models\Document;
use App\Models\DocumentTag;
use App\Services\BranchContextManager;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    use HasMultilingualValidation;
    use WithFileUploads;

    public ?Document $document = null;

    public bool $isEdit = false;

    public string $title = '';

    // Nullable to match database schema and prevent type errors when filling from model
    public ?string $description = null;

    public ?UploadedFile $file = null;

    // Nullable to match database schema and prevent type errors when filling from model
    public ?string $folder = null;

    // Nullable to match database schema and prevent type errors when filling from model
    public ?string $category = null;

    public bool $is_public = false;

    public array $selectedTags = [];

    protected DocumentService $documentService;

    public function boot(DocumentService $documentService): void
    {
        $this->documentService = $documentService;
    }

    public function mount(?Document $document = null): void
    {
        if ($document && $document->exists) {
            $this->authorize('documents.edit');

            // Prevent cross-branch document access (IDOR protection)
            $user = auth()->user();
            if ($user && $document->branch_id) {
                // Respect view-all permission (admins can manage documents across branches).
                if (! BranchContextManager::canViewAllBranches($user) && $user->branch_id && $user->branch_id !== $document->branch_id) {
                    abort(403, 'You cannot access documents from other branches.');
                }
            }

            $this->isEdit = true;
            $this->document = $document;
            $this->fill($document->only([
                'title',
                'description',
                'folder',
                'category',
                'is_public',
            ]));
            $this->selectedTags = $document->tags->pluck('id')->toArray();
        } else {
            $this->authorize('documents.create');
        }
    }

    public function save(): void
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize($this->isEdit ? 'documents.edit' : 'documents.create');

        // Documents are strictly branch-scoped (branch_id is required).
        if (! $this->isEdit && ! current_branch_id()) {
            $this->addError('branch_id', __('Please select a branch before uploading a document.'));

            return;
        }

        if ($this->isEdit) {
            $this->validate([
                'title' => $this->multilingualString(required: true, max: 255),
                'description' => $this->unicodeText(required: false),
                'folder' => $this->multilingualString(required: false, max: 255),
                'category' => $this->multilingualString(required: false, max: 100),
            ]);

            $this->document = $this->documentService->updateDocument($this->document, [
                'title' => $this->title,
                'description' => $this->description,
                'folder' => $this->folder,
                'category' => $this->category,
                'is_public' => $this->is_public,
                'tags' => $this->selectedTags,
            ]);

            session()->flash('success', __('Document updated successfully'));
        } else {
            $allowedExtensions = implode(',', DocumentService::ALLOWED_EXTENSIONS);
            $allowedMimeTypes = implode(',', DocumentService::ALLOWED_MIME_TYPES);

            $this->validate([
                'title' => $this->multilingualString(required: true, max: 255),
                'description' => $this->unicodeText(required: false),
                'file' => "required|file|max:51200|mimes:{$allowedExtensions}|mimetypes:{$allowedMimeTypes}",
                'folder' => $this->multilingualString(required: false, max: 255),
                'category' => $this->multilingualString(required: false, max: 100),
            ]);

            $this->document = $this->documentService->uploadDocument($this->file, [
                'title' => $this->title,
                'description' => $this->description,
                'folder' => $this->folder,
                'category' => $this->category,
                'is_public' => $this->is_public,
                'tags' => $this->selectedTags,
            ]);

            session()->flash('success', __('Document uploaded successfully'));
        }

        $this->redirectRoute('app.documents.show', ['document' => $this->document->id], navigate: true);
    }

    public function render()
    {
        // Limit tags to prevent memory issues with large datasets
        $tags = DocumentTag::orderBy('name')->limit(200)->get();

        return view('livewire.documents.form', [
            'tags' => $tags,
        ]);
    }
}
