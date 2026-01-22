<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: module configuration tables
 * 
 * Tables for module settings, fields, navigation, operations, and policies.
 * 
 * Classification: MIXED (some global, some branch-owned)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Module settings (branch-owned)
        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_modstt_module__mod');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_modstt_branch__brnch');
            $table->string('setting_key', 100);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 30)->default('string');
            $table->string('scope', 30)->default('branch');
            $table->boolean('is_inherited')->default(false);
            $table->foreignId('inherited_from_setting_id')
                ->nullable()
                ->constrained('module_settings')
                ->nullOnDelete()
                ->name('fk_modstt_inherit__modstt');
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->unique(['module_id', 'branch_id', 'setting_key'], 'uq_modstt_mod_brnch_key');
            $table->index('branch_id', 'idx_modstt_branch_id');
            $table->index('setting_key', 'idx_modstt_setting_key');
            $table->index('scope', 'idx_modstt_scope');
        });

        // Module custom fields (global)
        Schema::create('module_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_modcf_module__mod');
            $table->string('field_key', 100);
            $table->string('field_label', 191);
            $table->string('field_label_ar', 191)->nullable();
            $table->string('field_type', 50)->default('text');
            $table->json('field_options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('validation_rules')->nullable();
            $table->string('placeholder', 191)->nullable();
            $table->string('default_value', 500)->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'field_key'], 'uq_modcf_mod_field_key');
            $table->index('is_active', 'idx_modcf_is_active');
        });

        // Module fields (branch-owned)
        Schema::create('module_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_modfld_branch__brnch');
            $table->string('module_key', 50);
            $table->string('entity', 100);
            $table->string('name', 100);
            $table->string('label', 191);
            $table->string('type', 50)->default('text');
            $table->json('options')->nullable();
            $table->json('rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('show_in_export')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->json('default')->nullable();
            $table->json('meta')->nullable();
            $table->string('field_category', 50)->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('computed_config')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('supports_bulk_edit')->default(false);
            $table->json('dependencies')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'module_key', 'entity', 'name'], 'uq_modfld_brnch_mod_ent_name');
            $table->index('branch_id', 'idx_modfld_branch_id');
            $table->index('module_key', 'idx_modfld_module_key');
            $table->index('entity', 'idx_modfld_entity');
        });

        // Module navigation (global)
        Schema::create('module_navigation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_modnav_module__mod');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('module_navigation')
                ->cascadeOnDelete()
                ->name('fk_modnav_parent__modnav');
            $table->string('nav_key', 100);
            $table->string('nav_label', 191);
            $table->string('nav_label_ar', 191)->nullable();
            $table->string('route_name', 191)->nullable();
            $table->string('icon', 50)->nullable();
            $table->json('required_permissions')->nullable();
            $table->json('visibility_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module_id', 'nav_key'], 'uq_modnav_mod_key');
            $table->index('parent_id', 'idx_modnav_parent_id');
            $table->index('is_active', 'idx_modnav_is_active');
        });

        // Module operations (global)
        Schema::create('module_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_modop_module__mod');
            $table->string('operation_key', 100);
            $table->string('operation_name', 191);
            $table->text('description')->nullable();
            $table->string('operation_type', 50)->nullable();
            $table->json('operation_config')->nullable();
            $table->json('required_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module_id', 'operation_key'], 'uq_modop_mod_key');
            $table->index('is_active', 'idx_modop_is_active');
        });

        // Module policies (branch-owned)
        Schema::create('module_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_modpol_module__mod');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_modpol_branch__brnch');
            $table->string('policy_key', 100);
            $table->string('policy_name', 191);
            $table->text('policy_description')->nullable();
            $table->json('policy_rules')->nullable();
            $table->string('scope', 30)->default('branch');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->unique(['module_id', 'branch_id', 'policy_key'], 'uq_modpol_mod_brnch_key');
            $table->index('branch_id', 'idx_modpol_branch_id');
            $table->index('is_active', 'idx_modpol_is_active');
        });

        // Module product fields (global)
        Schema::create('module_product_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete()
                ->name('fk_modpf_module__mod');
            $table->string('field_key', 100);
            $table->string('field_label', 191);
            $table->string('field_label_ar', 191)->nullable();
            $table->string('field_type', 50)->default('text');
            $table->json('field_options')->nullable();
            $table->string('placeholder', 191)->nullable();
            $table->string('placeholder_ar', 191)->nullable();
            $table->string('default_value', 500)->nullable();
            $table->text('validation_rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('show_in_form')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('field_group', 50)->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'field_key'], 'uq_modpf_mod_field_key');
            $table->index('is_active', 'idx_modpf_is_active');
            $table->index('field_group', 'idx_modpf_field_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_product_fields');
        Schema::dropIfExists('module_policies');
        Schema::dropIfExists('module_operations');
        Schema::dropIfExists('module_navigation');
        Schema::dropIfExists('module_fields');
        Schema::dropIfExists('module_custom_fields');
        Schema::dropIfExists('module_settings');
    }
};
