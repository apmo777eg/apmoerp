<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    // Documents only - no images allowed (images go to Media Library)
    public const ALLOWED_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'csv',
        'txt',
        'zip',
        'rar',
    ];

    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/csv',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/vnd.rar',
    ];

    private string $documentsDisk;

    private ?string $fallbackDisk;

    public function __construct(
        protected UIHelperService $uiHelper
    ) {
        $this->documentsDisk = (string) config('filesystems.document_disk', 'local');
        $this->fallbackDisk = config('filesystems.document_disk_fallback');
    }

    /**
     * Upload a new document
     */
    public function uploadDocument(UploadedFile $file, array $data): Document
    {
        $this->validateFile($file);
        $user = auth()->user();
        $userBranchId = $user?->branch_id;

        if (isset($data['branch_id']) && $userBranchId && (int) $data['branch_id'] !== $userBranchId) {
            throw new AuthorizationException('You cannot upload documents to another branch.');
        }

        if (! $userBranchId) {
            throw new AuthorizationException('Unable to resolve your branch for document uploads.');
        }

        return DB::transaction(function () use ($file, $data) {
            // Store the file on the configured private disk
            $disk = $this->documentsDisk;
            $path = $file->store('documents', $disk);
            $this->assertStoredMimeIsAllowed($file, $path, $disk);
            $isPublic = (bool) ($data['is_public'] ?? false);

            $user = auth()->user();
            $branchId = $user?->branch_id;

            // Create document record
            $document = new Document([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'folder' => $data['folder'] ?? null,
                'category' => $data['category'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $document->code = Str::uuid()->toString();
            $document->version = 1;
            $document->version_number = 1;
            $document->is_public = $isPublic;
            $document->access_level = $isPublic ? 'public' : 'private';
            $document->uploaded_by = $user?->id;
            $document->branch_id = $branchId;
            $document->save();

            // Create initial version
            DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'uploaded_by' => actual_user_id(),
                'change_notes' => 'Initial upload',
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Log activity
            $document->logActivity('created', auth()->user());

            // Attach tags if provided
            if (! empty($data['tags'])) {
                $document->tags()->sync($data['tags']);
            }

            return $document;
        });
    }

    /**
     * Upload a new version of existing document
     */
    public function uploadVersion(Document $document, UploadedFile $file, ?string $changeNotes = null): DocumentVersion
    {
        $this->validateFile($file);

        $user = auth()->user();
        if ($user && $user->branch_id && $document->branch_id && $user->branch_id !== $document->branch_id) {
            throw new AuthorizationException('You cannot upload versions for documents outside your branch.');
        }

        return DB::transaction(function () use ($document, $file, $changeNotes) {
            // Store the file on the configured private disk
            $disk = $this->documentsDisk;
            $path = $file->store('documents', $disk);
            $this->assertStoredMimeIsAllowed($file, $path, $disk);

            // Get next version number
            $nextVersion = $document->versions()->max('version_number') + 1;

            // Create version record
            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                'uploaded_by' => actual_user_id(),
                'change_notes' => $changeNotes,
            ]);

            // Update document with new version info
            $document->update([
                'version' => $nextVersion,
                'version_number' => $nextVersion,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_type' => $file->getClientOriginalExtension(),
            ]);

            // Log activity
            $document->logActivity('version_created', auth()->user(), [
                'version' => $nextVersion,
                'change_notes' => $changeNotes,
            ]);

            return $version;
        });
    }

    /**
     * Update document metadata
     */
    public function updateDocument(Document $document, array $data): Document
    {
        // Prevent cross-branch document updates (IDOR protection)
        $user = auth()->user();
        if ($user && $user->branch_id && $document->branch_id && $user->branch_id !== $document->branch_id) {
            throw new AuthorizationException('You cannot update documents from other branches.');
        }

        return DB::transaction(function () use ($document, $data) {
            $document->update([
                'title' => $data['title'] ?? $document->title,
                'description' => $data['description'] ?? $document->description,
                'folder' => $data['folder'] ?? $document->folder,
                'category' => $data['category'] ?? $document->category,
                'is_public' => $data['is_public'] ?? $document->is_public,
                'access_level' => ($data['is_public'] ?? $document->is_public) ? 'public' : 'private',
                'metadata' => $data['metadata'] ?? $document->metadata,
            ]);

            // Update tags if provided
            if (isset($data['tags'])) {
                $document->tags()->sync($data['tags']);
            }

            // Log activity
            $document->logActivity('edited', auth()->user());

            return $document->fresh();
        });
    }

    /**
     * Share document with user
     */
    public function shareDocument(Document $document, int $userId, string $permission = 'view', ?\DateTime $expiresAt = null): void
    {
        $allowedPermissions = ['view', 'download', 'edit', 'manage'];

        if (! in_array($permission, $allowedPermissions, true)) {
            throw new AuthorizationException('Invalid share permission supplied.');
        }

        $this->ensureCanManageShares($document);
        $targetUser = User::findOrFail($userId);

        if ($document->branch_id && $targetUser->branch_id !== $document->branch_id) {
            throw new AuthorizationException('You cannot share documents across branches.');
        }

        if ($document->branch_id && auth()->user()?->branch_id && auth()->user()->branch_id !== $document->branch_id) {
            throw new AuthorizationException('You cannot share documents outside your branch.');
        }

        DB::transaction(function () use ($document, $userId, $permission, $expiresAt) {
            $document->shares()->updateOrCreate(
                ['shared_with_user_id' => $userId],
                [
                    'user_id' => $userId,
                    // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                    'shared_by' => actual_user_id(),
                    'permission' => $permission,
                    'expires_at' => $expiresAt,
                ]
            );

            // Log activity
            $document->logActivity('shared', auth()->user(), [
                'shared_with_user_id' => $userId,
                'permission' => $permission,
            ]);
        });
    }

    /**
     * Unshare document from user
     */
    public function unshareDocument(Document $document, int $userId): void
    {
        $this->ensureCanManageShares($document);

        DB::transaction(function () use ($document, $userId) {
            $document->shares()
                ->where('shared_with_user_id', $userId)
                ->delete();

            // Log activity
            $document->logActivity('unshared', auth()->user(), [
                'unshared_from_user_id' => $userId,
            ]);
        });
    }

    /**
     * Delete document
     */
    public function deleteDocument(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            // Delete all file versions from storage
            $documentDisk = $this->resolveDisk($document->file_path);
            Storage::disk($documentDisk)->delete($document->file_path);

            foreach ($document->versions as $version) {
                $versionDisk = $this->resolveDisk($version->file_path);
                Storage::disk($versionDisk)->delete($version->file_path);
            }

            // Log activity before deletion
            $document->logActivity('deleted', auth()->user());

            // Soft delete the document (cascades to versions, shares, activities)
            return $document->delete();
        });
    }

    /**
     * Download document and log activity
     */
    public function downloadDocument(Document $document, User $user, bool $inline = false): StreamedResponse
    {
        if (! $user->can('documents.download')) {
            abort(403, 'You do not have permission to download this document');
        }

        // Check access
        if (! $document->canBeAccessedBy($user)) {
            abort(403, 'You do not have permission to download this document');
        }

        // Ensure the granted permission allows downloading when access is via a share
        $share = $document->shares()
            ->where('shared_with_user_id', $user->id)
            ->first();

        if (
            $share
            && ! $share->canDownload()
            && $document->uploaded_by !== $user->id
            && ! $user->can('documents.manage')
        ) {
            abort(403, 'You do not have permission to download this document');
        }

        // Log activity
        $document->logActivity('downloaded', $user, [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Increment access count if shared
        $share ??= $document->shares()->where('shared_with_user_id', $user->id)->first();
        if ($share) {
            $share->incrementAccessCount();
        }

        $disk = $this->resolveDisk($document->file_path);

        // Ensure the file exists on the resolved disk to avoid storage driver errors
        abort_unless(
            Storage::disk($disk)->exists($document->file_path),
            404,
            'File not found'
        );

        // MED-004 FIX: Verify MIME type from actual file on storage instead of trusting DB
        $dbMimeType = $document->mime_type;
        try {
            $storedMimeType = Storage::disk($disk)->mimeType($document->file_path);
        } catch (\Exception $e) {
            // If MIME detection fails, log warning and use DB value
            Log::warning('Failed to detect MIME type from storage, using DB value', [
                'path' => $document->file_path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            $storedMimeType = null;
        }

        // Use storage-detected MIME if available, otherwise fall back to DB value
        $mimeType = $storedMimeType ?: $dbMimeType;

        // MED-004 FIX: Define safe MIME types that can be served inline
        $safeInlineMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        // MED-004 FIX: Add security headers including nosniff
        $headers = [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ];

        // MED-004 FIX: Only allow inline display for safe MIME types
        $isSafeForInline = in_array($mimeType, $safeInlineMimeTypes, true);

        if ($inline && $isSafeForInline) {
            return Storage::disk($disk)->response(
                $document->file_path,
                $document->file_name,
                $headers
            );
        }

        return Storage::disk($disk)->download(
            $document->file_path,
            $document->file_name,
            $headers
        );
    }

    public function documentsDisk(): string
    {
        return $this->documentsDisk;
    }

    private function resolveDisk(string $path): string
    {
        $primaryDisk = $this->documentsDisk;

        if (Storage::disk($primaryDisk)->exists($path)) {
            return $primaryDisk;
        }

        if ($this->fallbackDisk && Storage::disk($this->fallbackDisk)->exists($path)) {
            Log::warning('Primary document disk missing file, using fallback.', [
                'primary' => $primaryDisk,
                'fallback' => $this->fallbackDisk,
                'path' => $path,
            ]);

            return $this->fallbackDisk;
        }

        Log::warning('Document file missing on primary disk and fallback unavailable.', [
            'primary' => $primaryDisk,
            'fallback' => $this->fallbackDisk,
            'path' => $path,
        ]);

        // LOW-001 FIX: abort() never returns, so removed unreachable return statement
        abort(503, 'File temporarily unavailable');
    }

    private function assertStoredMimeIsAllowed(UploadedFile $file, string $path, string $disk): void
    {
        $storedMime = Storage::disk($disk)->mimeType($path) ?? $file->getMimeType();
        $clientMime = $file->getMimeType();

        if (
            ! in_array($storedMime, self::ALLOWED_MIME_TYPES, true)
            || ! in_array($clientMime, self::ALLOWED_MIME_TYPES, true)
            || $storedMime !== $clientMime
        ) {
            Storage::disk($disk)->delete($path);

            throw ValidationException::withMessages([
                'file' => [__('Uploaded file type is not allowed after verification.')],
            ]);
        }
    }

    private function validateFile(UploadedFile $file): void
    {
        $mimesRule = implode(',', self::ALLOWED_EXTENSIONS);
        $mimeTypesRule = implode(',', self::ALLOWED_MIME_TYPES);

        Validator::make(
            ['file' => $file],
            ['file' => "required|file|max:51200|mimes:{$mimesRule}|mimetypes:{$mimeTypesRule}"]
        )->validate();
    }

    private function ensureCanManageShares(Document $document): void
    {
        $user = auth()->user();

        if (! $user) {
            throw new AuthorizationException('You are not allowed to manage shares for this document.');
        }

        if (
            $document->branch_id
            && $user->branch_id
            && $document->branch_id !== $user->branch_id
        ) {
            throw new AuthorizationException('You cannot manage shares for documents outside your branch.');
        }

        if ($document->uploaded_by !== $user->id && ! $user->can('documents.manage')) {
            throw new AuthorizationException('You are not allowed to manage shares for this document.');
        }
    }

    /**
     * Get document statistics
     */
    public function getStatistics(?int $branchId = null): array
    {
        $baseQuery = Document::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $totalSize = (clone $baseQuery)->sum('file_size');

        return [
            'total_documents' => (clone $baseQuery)->count(),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->uiHelper->formatBytes((int) $totalSize),
            'by_category' => (clone $baseQuery)
                ->select('category', DB::raw('count(*) as count'))
                ->whereNotNull('category')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'storage_by_type' => (clone $baseQuery)
                ->select('mime_type',
                    DB::raw('COUNT(*) as documents'),
                    DB::raw('COALESCE(SUM(file_size), 0) as total_size'))
                ->whereNotNull('mime_type')
                ->groupBy('mime_type')
                ->orderByDesc('total_size')
                ->get(),
            'recent_uploads' => (clone $baseQuery)->latest()->limit(5)->get(),
            'top_uploaders' => (clone $baseQuery)
                ->select('uploaded_by',
                    DB::raw('COUNT(*) as documents_uploaded'),
                    DB::raw('COALESCE(SUM(file_size), 0) as storage_used'))
                ->whereNotNull('uploaded_by')
                ->groupBy('uploaded_by')
                ->orderByDesc('documents_uploaded')
                ->limit(5)
                ->with('uploader:id,name')
                ->get(),
            'most_downloaded' => Document::withCount('activities')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->orderBy('activities_count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}
