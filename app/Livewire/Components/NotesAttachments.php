<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Attachment;
use App\Models\Note;
use App\Services\AttachmentAuthorizationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class NotesAttachments extends Component
{
    use WithFileUploads;

    public string $modelType;

    public int $modelId;

    public array $notes = [];

    public array $attachments = [];

    public string $newNote = '';

    public string $noteType = 'general';

    public $newFiles = [];

    public string $fileDescription = '';

    public bool $showNoteModal = false;

    public bool $showFileModal = false;

    public ?int $editingNoteId = null;

    public string $editingNoteContent = '';

    protected AttachmentAuthorizationService $authorizer;

    public function boot(AttachmentAuthorizationService $authorizer): void
    {
        $this->authorizer = $authorizer;
    }

    public function mount(string $modelType, int $modelId): void
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->ensureAuthorized();
        $this->loadData();
    }

    #[On('refreshNotesAttachments')]
    public function loadData(): void
    {
        $this->ensureAuthorized();

        $this->notes = Note::where('noteable_type', $this->modelType)
            ->where('noteable_id', $this->modelId)
            ->with('creator')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        $this->attachments = Attachment::where('attachable_type', $this->modelType)
            ->where('attachable_id', $this->modelId)
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attachment) {
                return array_merge($attachment->toArray(), [
                    'url' => $attachment->url,
                    'human_size' => $attachment->human_size,
                    'is_image' => $attachment->isImage(),
                ]);
            })
            ->toArray();
    }

    public function openNoteModal(): void
    {
        $this->reset(['newNote', 'noteType', 'editingNoteId', 'editingNoteContent']);
        $this->showNoteModal = true;
    }

    public function closeNoteModal(): void
    {
        $this->showNoteModal = false;
        $this->reset(['newNote', 'noteType', 'editingNoteId', 'editingNoteContent']);
    }

    public function saveNote(): void
    {
        $this->ensureAuthorized();
        $this->validate([
            'newNote' => 'required|string|min:2|max:5000',
        ]);

        $user = Auth::user();

        if ($this->editingNoteId) {
            $note = Note::where('noteable_type', $this->modelType)
                ->where('noteable_id', $this->modelId)
                ->findOrFail($this->editingNoteId);
            $note->update([
                'content' => $this->newNote,
                'type' => $this->noteType,
                'updated_by' => $user?->id,
            ]);
            session()->flash('success', __('Note updated successfully'));
        } else {
            Note::create([
                'noteable_type' => $this->modelType,
                'noteable_id' => $this->modelId,
                'content' => $this->newNote,
                'type' => $this->noteType,
                'branch_id' => $user?->branch_id,
                'created_by' => $user?->id,
            ]);
            session()->flash('success', __('Note added successfully'));
        }

        $this->closeNoteModal();
        $this->loadData();
    }

    public function editNote(int $noteId): void
    {
        $this->ensureAuthorized();

        $note = Note::where('noteable_type', $this->modelType)
            ->where('noteable_id', $this->modelId)
            ->findOrFail($noteId);
        $this->editingNoteId = $noteId;
        $this->newNote = $note->content;
        $this->noteType = $note->type;
        $this->showNoteModal = true;
    }

    public function deleteNote(int $noteId): void
    {
        $this->ensureAuthorized();

        Note::where('noteable_type', $this->modelType)
            ->where('noteable_id', $this->modelId)
            ->findOrFail($noteId)
            ->delete();
        session()->flash('success', __('Note deleted successfully'));
        $this->loadData();
    }

    public function togglePin(int $noteId): void
    {
        $this->ensureAuthorized();

        $note = Note::where('noteable_type', $this->modelType)
            ->where('noteable_id', $this->modelId)
            ->findOrFail($noteId);
        $note->update(['is_pinned' => ! $note->is_pinned]);
        $this->loadData();
    }

    public function openFileModal(): void
    {
        $this->reset(['newFiles', 'fileDescription']);
        $this->showFileModal = true;
    }

    public function closeFileModal(): void
    {
        $this->showFileModal = false;
        $this->reset(['newFiles', 'fileDescription']);
    }

    /**
     * HIGH-002 FIX: Wrap uploadFiles in DB::transaction for atomicity.
     * Multiple file uploads and database records should succeed or fail together.
     */
    public function uploadFiles(): void
    {
        $this->ensureAuthorized();
        $this->validate([
            'newFiles' => 'required|array|min:1',
            'newFiles.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt|mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/csv,text/plain',
        ]);

        $user = Auth::user();
        $storage = Storage::disk('local');
        $uploadedPaths = [];

        try {
            DB::transaction(function () use ($user, $storage, &$uploadedPaths) {
                foreach ($this->newFiles as $file) {
                    $path = $file->store('attachments/'.strtolower(class_basename($this->modelType)), 'local');
                    $uploadedPaths[] = $path;

                    $storedMime = $storage->mimeType($path) ?? $file->getMimeType();
                    $clientMime = $file->getMimeType();
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt'];
                    $allowedMimeTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/csv',
                        'text/plain',
                    ];
                    if (! in_array($file->extension(), $allowedExtensions, true)
                        || ! in_array($storedMime, $allowedMimeTypes, true)
                        || ! in_array($clientMime, $allowedMimeTypes, true)
                        || $storedMime !== $clientMime) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'newFiles' => [__('Uploaded file type is not allowed after verification.')],
                        ]);
                    }

                    if (! $storage->exists($path)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'newFiles' => [__('Uploaded file could not be saved. Please try again.')],
                        ]);
                    }

                    $hash = hash_file('sha256', $storage->path($path));

                    if ($hash === false) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'newFiles' => [__('Uploaded file could not be processed. Please try again.')],
                        ]);
                    }

                    $attachment = new Attachment([
                        'attachable_type' => $this->modelType,
                        'attachable_id' => $this->modelId,
                        'filename' => basename($path),
                        'original_filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $this->getFileType($storedMime),
                        'description' => $this->fileDescription,
                        'branch_id' => $user?->branch_id,
                        'uploaded_by' => $user?->id,
                        'metadata' => [
                            'sha256' => $hash,
                        ],
                    ]);
                    $attachment->disk = 'local';
                    $attachment->path = $path;
                    $attachment->mime_type = $storedMime;
                    $attachment->save();
                }
            });

            session()->flash('success', __('Files uploaded successfully'));
            $this->closeFileModal();
            $this->loadData();
        } catch (\Exception $e) {
            // Clean up uploaded files if transaction failed
            foreach ($uploadedPaths as $path) {
                if ($storage->exists($path)) {
                    $storage->delete($path);
                }
            }
            throw $e;
        }
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->ensureAuthorized();

        $attachment = Attachment::where('attachable_type', $this->modelType)
            ->where('attachable_id', $this->modelId)
            ->findOrFail($attachmentId);

        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();

        session()->flash('success', __('File deleted successfully'));
        $this->loadData();
    }

    protected function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }
        if (str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel')) {
            return 'spreadsheet';
        }
        if (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return 'document';
        }

        return 'other';
    }

    public function render()
    {
        return view('livewire.components.notes-attachments');
    }

    private function ensureAuthorized(): void
    {
        $user = Auth::user();
        abort_if(! $user, 403);

        $this->authorizer->authorizeForModel($user, $this->modelType, $this->modelId);
    }
}
