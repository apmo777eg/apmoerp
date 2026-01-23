<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: user preferences and activity tables
 *
 * User preferences, favorites, sessions, search history, notifications.
 *
 * Classification: USER-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // User preferences
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_usrpref_user__usr');
            $table->string('preference_key', 100);
            $table->text('preference_value')->nullable();
            $table->string('preference_type', 30)->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->json('metadata')->nullable();
            $table->json('settings')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'preference_key'], 'uq_usrpref_user_key');
        });

        // User favorites
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_usrfav_user__usr');
            $table->string('favoritable_type', 191);
            $table->unsignedBigInteger('favoritable_id');
            $table->string('name', 191)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'uq_usrfav_user_fav');
            $table->index(['favoritable_type', 'favoritable_id'], 'idx_usrfav_favoritable');
        });

        // User sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_usrses_user__usr');
            $table->string('session_id', 100)->unique('uq_usrses_session');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_usrses_user_id');
            $table->index('is_active', 'idx_usrses_is_active');
        });

        // Login activities
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_logact_user__usr');
            $table->string('email', 191)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('status', 30); // success, failed, blocked, 2fa_required
            $table->string('failure_reason', 100)->nullable();
            $table->string('location', 191)->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_logact_user_id');
            $table->index('email', 'idx_logact_email');
            $table->index('ip_address', 'idx_logact_ip');
            $table->index('status', 'idx_logact_status');
            $table->index('created_at', 'idx_logact_created');
        });

        // Search history
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_srchhst_user__usr');
            $table->string('search_query', 500);
            $table->string('search_type', 50)->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamps();

            $table->index('user_id', 'idx_srchhst_user_id');
            $table->index('search_type', 'idx_srchhst_type');
        });

        // Search index
        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type', 191);
            $table->unsignedBigInteger('searchable_id');
            $table->string('title', 500);
            $table->text('content')->nullable();
            $table->string('category', 50)->nullable();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_srchidx_branch__brnch');
            $table->string('url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('indexed_at');
            $table->timestamps();

            $table->index(['searchable_type', 'searchable_id'], 'idx_srchidx_searchable');
            $table->index('category', 'idx_srchidx_category');
            $table->index('branch_id', 'idx_srchidx_branch_id');
            $table->fullText(['title', 'content'], 'ft_srchidx_content');
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 191);
            $table->string('notifiable_type', 191);
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notif_notifiable');
            $table->index('read_at', 'idx_notif_read_at');
        });

        // System settings
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique('uq_sysset_key');
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->string('group', 50)->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->index('group', 'idx_sysset_group');
        });

        // Audit logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_audlog_user__usr');
            // NEW-005 FIX: Added columns for impersonation tracking and enhanced audit info
            $table->foreignId('performed_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_audlog_performed_by__usr');
            $table->foreignId('impersonating_as_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_audlog_impersonating__usr');
            $table->foreignId('target_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_audlog_target_user__usr');
            $table->string('auditable_type', 191);
            $table->unsignedBigInteger('auditable_id');
            $table->string('action', 100)->nullable(); // NEW-005 FIX
            $table->string('module_key', 100)->nullable(); // NEW-005 FIX
            $table->string('subject_type', 191)->nullable(); // NEW-005 FIX
            $table->unsignedBigInteger('subject_id')->nullable(); // NEW-005 FIX
            $table->string('event', 30); // created, updated, deleted, restored
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ip', 45)->nullable(); // NEW-005 FIX
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('tags', 255)->nullable();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_audlog_branch__brnch');
            $table->json('extra_attributes')->nullable();
            $table->json('meta')->nullable(); // NEW-005 FIX
            $table->timestamps();
            $table->softDeletes(); // NEW-005 FIX: Model uses SoftDeletes

            $table->index(['auditable_type', 'auditable_id'], 'idx_audlog_auditable');
            $table->index('user_id', 'idx_audlog_user_id');
            $table->index('event', 'idx_audlog_event');
            $table->index('created_at', 'idx_audlog_created');
            $table->index('branch_id', 'idx_audlog_branch_id');
        });

        // Activity log (Spatie)
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 128)->nullable();
            $table->text('description');
            $table->string('subject_type', 191)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type', 191)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('event', 50)->nullable();
            $table->timestamps();

            $table->index('log_name', 'idx_actlog_log_name');
            $table->index(['subject_type', 'subject_id'], 'idx_actlog_subject');
            $table->index(['causer_type', 'causer_id'], 'idx_actlog_causer');
            $table->index('batch_uuid', 'idx_actlog_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('search_index');
        Schema::dropIfExists('search_history');
        Schema::dropIfExists('login_activities');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('user_preferences');
    }
};
