<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: users
 * 
 * Core user table for authentication and authorization.
 * Uses Spatie Permission for role-based access control.
 * 
 * Classification: BRANCH-OWNED (users belong to a primary branch)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('email', 191)->unique('uq_usr_email');
            $table->string('password');
            $table->string('phone', 50)->nullable();
            $table->string('username', 100)->nullable()->unique('uq_usr_username');
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->foreignId('branch_id')->nullable()
                ->constrained('branches')
                ->nullOnDelete()
                ->name('fk_usr_branch__brnch');
            $table->string('avatar', 500)->nullable();
            $table->json('preferences')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->decimal('max_discount_percent', 5, 2)->nullable();
            $table->decimal('daily_discount_limit', 18, 4)->nullable();
            $table->boolean('can_modify_price')->default(false);
            $table->unsignedSmallInteger('max_sessions')->default(3);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('branch_id', 'idx_usr_branch_id');
            $table->index('is_active', 'idx_usr_is_active');
            $table->index('email', 'idx_usr_email');
            $table->index('phone', 'idx_usr_phone');
            $table->index('last_login_at', 'idx_usr_last_login');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
