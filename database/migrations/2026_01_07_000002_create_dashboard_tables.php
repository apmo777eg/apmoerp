<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: dashboard tables
 * 
 * Dashboard widgets, user layouts, widget configurations.
 * 
 * Classification: MIXED (widgets global, layouts user-owned)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dashboard widgets (global)
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique('uq_dshwgt_key');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->string('component', 191);
            $table->string('icon', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->json('default_settings')->nullable();
            $table->json('configurable_options')->nullable();
            $table->unsignedSmallInteger('default_width')->default(4);
            $table->unsignedSmallInteger('default_height')->default(2);
            $table->unsignedSmallInteger('min_width')->default(2);
            $table->unsignedSmallInteger('min_height')->default(1);
            $table->boolean('requires_permission')->default(false);
            $table->string('permission_key', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('category', 'idx_dshwgt_category');
            $table->index('is_active', 'idx_dshwgt_is_active');
        });

        // User dashboard layouts (user-owned)
        Schema::create('user_dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_usrdshly_user__usr');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_usrdshly_branch__brnch');
            $table->string('name', 191)->default('Default');
            $table->boolean('is_default')->default(false);
            $table->json('layout_config')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'name'], 'uq_usrdshly_user_branch_name');
            $table->index('user_id', 'idx_usrdshly_user_id');
        });

        // User dashboard widgets (user-owned)
        Schema::create('user_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_dashboard_layout_id')
                ->constrained('user_dashboard_layouts')
                ->cascadeOnDelete()
                ->name('fk_usrdshwgt_layout__usrdshly');
            $table->foreignId('dashboard_widget_id')
                ->constrained('dashboard_widgets')
                ->cascadeOnDelete()
                ->name('fk_usrdshwgt_widget__dshwgt');
            $table->unsignedSmallInteger('position_x')->default(0);
            $table->unsignedSmallInteger('position_y')->default(0);
            $table->unsignedSmallInteger('width')->default(4);
            $table->unsignedSmallInteger('height')->default(2);
            $table->json('settings')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('user_dashboard_layout_id', 'idx_usrdshwgt_layout_id');
        });

        // Widget data cache
        Schema::create('widget_data_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_wgtcache_user__usr');
            $table->foreignId('dashboard_widget_id')
                ->nullable()
                ->constrained('dashboard_widgets')
                ->cascadeOnDelete()
                ->name('fk_wgtcache_widget__dshwgt');
            $table->string('widget_id', 100)->nullable();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_wgtcache_branch__brnch');
            $table->string('cache_key', 191);
            $table->json('data')->nullable();
            $table->timestamp('cached_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['cache_key', 'user_id', 'branch_id'], 'uq_wgtcache_key_user_branch');
            $table->index('expires_at', 'idx_wgtcache_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_data_cache');
        Schema::dropIfExists('user_dashboard_widgets');
        Schema::dropIfExists('user_dashboard_layouts');
        Schema::dropIfExists('dashboard_widgets');
    }
};
