<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;

    private const SHARE_USER_LIMIT = 50;

    public Document $document;

    public int $shareUserId = 0;

    public string $sharePermission = 'view';

    public ?string $shareExpiresAt = null;

    protected DocumentService $documentService;

    public function boot(DocumentService $documentService): void
    {
        $this->documentService = $documentService;
    }

    public function mount(Document $document): void
    {
        $this->authorize('documents.view');

        // Prevent cross-branch document access (IDOR protection)
        $this->ensureDocumentBranchAccess(auth()->user(), $document);

        $this->document = $document->load(['uploader', 'tags', 'versions.uploader', 'shares.user', 'activities.user']);

        // Check if user can access this document
        if (! $document->canBeAccessedBy(auth()->user())) {
            abort(403, 'You do not have permission to view this document');
        }

        // Log view activity
        $document->logActivity('viewed', auth()->user());
    }

    public function download()
    {
        $this->authorize('documents.download');

        $user = auth()->user();
        $this->ensureDocumentBranchAccess($user);

        if (! $this->document->canBeAccessedBy($user)) {
            abort(403, __('You do not have permission to access this document.'));
        }

        return $this->documentService->downloadDocument(
            $this->document,
            auth()->user(),
            request()->boolean('inline', false)
        );
    }

    public function shareDocument(): void
    {
        $this->authorize('documents.share');
        $this->ensureDocumentBranchAccess(auth()->user());
        $this->authorizeShareManagement();

        $this->validate([
            'shareUserId' => 'required|exists:users,id',
            'sharePermission' => 'required|in:view,download,edit,manage',
            'shareExpiresAt' => 'nullable|date|after:now',
        ]);

        $expiresAt = $this->shareExpiresAt ? new \DateTime($this->shareExpiresAt) : null;

        $this->documentService->shareDocument(
            $this->document,
            $this->shareUserId,
            $this->sharePermission,
            $expiresAt
        );

        session()->flash('success', __('Document shared successfully'));
        $this->document->refresh();
        $this->reset(['shareUserId', 'sharePermission', 'shareExpiresAt']);
    }

    public function unshare(int $userId): void
    {
        $this->authorize('documents.share');
        $this->ensureDocumentBranchAccess(auth()->user());
        $this->authorizeShareManagement();

        $this->documentService->unshareDocument($this->document, $userId);

        session()->flash('success', __('Access revoked successfully'));
        $this->document->refresh();
    }

    public function render()
    {
        $users = collect();

        $currentUser = auth()->user();

        if ($this->canManageSharing($currentUser)) {
            $users = User::where('id', '!=', $this->document->uploaded_by)
                ->when($this->document->branch_id, fn ($q) => $q->where('branch_id', $this->document->branch_id))
                ->when(! $this->document->branch_id && $currentUser?->branch_id, fn ($q) => $q->where('branch_id', $currentUser->branch_id))
                ->orderBy('name')
                ->limit(self::SHARE_USER_LIMIT)
                ->get();
        }

        return view('livewire.documents.show', [
            'users' => $users,
        ]);
    }

    private function authorizeShareManagement(): void
    {
        if (! $this->canManageSharing(auth()->user())) {
            abort(403, __('Only the document owner or a manager can manage sharing.'));
        }
    }

    private function ensureDocumentBranchAccess(?User $user, ?Document $document = null): void
    {
        $document ??= $this->document;

        if (! $user || ! $document) {
            return;
        }

        if ($user->branch_id && $document->branch_id && $user->branch_id !== $document->branch_id) {
            abort(403, __('You cannot access documents from other branches.'));
        }
    }

    private function canManageSharing(?User $user): bool
    {
        return (bool) $user
            && $user->can('documents.share')
            && ($this->document->uploaded_by === $user->id || $user->can('documents.manage'));
    }
}
