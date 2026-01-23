<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Laravel required tables
 * 
 * Cache, sessions, jobs, failed jobs, password resets, personal access tokens.
 * 
 * Classification: GLOBAL (Laravel framework)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cache table
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->string('owner', 191);
            $table->integer('expiration');
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index('idx_sess_user_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index('idx_sess_last_activity');
        });

        // Jobs table
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 191)->index('idx_jobs_queue');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Job batches table
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('name', 191);
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Failed jobs table
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 191)->unique('uq_fjobs_uuid');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token', 191);
            $table->timestamp('created_at')->nullable();
        });

        // Personal access tokens (Sanctum)
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type', 191);
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name', 191);
            $table->string('token', 64)->unique('uq_pat_token');
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id'], 'idx_pat_tokenable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
