<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: modules
 * 
 * Core modules table for modular ERP functionality.
 * 
 * Classification: GLOBAL (system-wide module definitions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique('uq_mod_key');
            $table->string('slug', 100)->unique('uq_mod_slug');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->boolean('is_core')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('default_settings')->nullable();
            $table->string('pricing_type', 30)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('module_type', 30)->nullable();
            // Feature flags
            $table->boolean('has_variations')->default(false);
            $table->boolean('has_inventory')->default(false);
            $table->boolean('has_serial_numbers')->default(false);
            $table->boolean('has_expiry_dates')->default(false);
            $table->boolean('has_batch_numbers')->default(false);
            $table->boolean('is_rental')->default(false);
            $table->boolean('is_service')->default(false);
            $table->boolean('supports_reporting')->default(true);
            $table->boolean('supports_custom_fields')->default(true);
            $table->boolean('supports_items')->default(true);
            $table->json('operation_config')->nullable();
            $table->json('integration_hooks')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('is_active', 'idx_mod_is_active');
            $table->index('is_core', 'idx_mod_is_core');
            $table->index('sort_order', 'idx_mod_sort_order');
            $table->index('category', 'idx_mod_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
