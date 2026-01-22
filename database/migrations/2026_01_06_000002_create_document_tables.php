<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: document management tables
 * 
 * Documents, versions, shares, tags, activities, attachments.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Document tags (global)
        Schema::create('document_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique('uq_doctag_slug');
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Documents
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_doc_branch__brnch');
            $table->string('code', 50);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size')->nullable();
            $table->string('file_type', 50)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('folder', 255)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('status', 30)->default('active'); // active, archived
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->boolean('is_public')->default(false);
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_doc_uploaded_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_doc_branch_code');
            $table->index('branch_id', 'idx_doc_branch_id');
            $table->index('folder', 'idx_doc_folder');
            $table->index('category', 'idx_doc_category');
            $table->index('status', 'idx_doc_status');
        });

        // Document tag pivot
        Schema::create('document_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete()
                ->name('fk_doctag_doc__doc');
            $table->foreignId('document_tag_id')
                ->constrained('document_tags')
                ->cascadeOnDelete()
                ->name('fk_doctag_tag__doctag');
            $table->timestamps();

            $table->unique(['document_id', 'document_tag_id'], 'uq_doctag_doc_tag');
        });

        // Document versions
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete()
                ->name('fk_docver_document__doc');
            $table->unsignedSmallInteger('version_number');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_docver_uploaded_by__usr');
            $table->text('change_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_number'], 'uq_docver_doc_version');
            $table->index('document_id', 'idx_docver_document_id');
        });

        // Document shares
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete()
                ->name('fk_docshr_document__doc');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_docshr_user__usr');
            $table->foreignId('shared_with_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_docshr_shared_with__usr');
            $table->string('shared_with_role', 100)->nullable();
            $table->foreignId('shared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_docshr_shared_by__usr');
            $table->string('permission', 30)->default('view'); // view, edit, admin
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('password_hash', 255)->nullable();
            $table->boolean('notify_on_access')->default(false);
            $table->timestamps();

            $table->index('document_id', 'idx_docshr_document_id');
            $table->index('shared_with_user_id', 'idx_docshr_shared_with');
        });

        // Document activities
        Schema::create('document_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete()
                ->name('fk_docact_document__doc');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_docact_user__usr');
            $table->string('action', 50); // view, download, edit, share, delete
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamps();

            $table->index('document_id', 'idx_docact_document_id');
            $table->index('user_id', 'idx_docact_user_id');
            $table->index('action', 'idx_docact_action');
        });

        // Attachments (polymorphic)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 191);
            $table->unsignedBigInteger('attachable_id');
            $table->string('filename', 255);
            $table->string('original_filename', 255)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('type', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_attach_branch__brnch');
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_attach_uploaded_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id'], 'idx_attach_attachable');
            $table->index('branch_id', 'idx_attach_branch_id');
        });

        // Media
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedInteger('optimized_size')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('disk', 50)->default('public');
            $table->string('collection', 100)->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_media_user__usr');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_media_branch__brnch');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('branch_id', 'idx_media_branch_id');
            $table->index('user_id', 'idx_media_user_id');
            $table->index('collection', 'idx_media_collection');
        });

        // Notes (polymorphic)
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('noteable_type', 191);
            $table->unsignedBigInteger('noteable_id');
            $table->text('content');
            $table->string('type', 30)->default('note'); // note, comment, reminder
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_note_branch__brnch');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_note_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_note_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['noteable_type', 'noteable_id'], 'idx_note_noteable');
            $table->index('branch_id', 'idx_note_branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('media');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('document_activities');
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_tag');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_tags');
    }
};
