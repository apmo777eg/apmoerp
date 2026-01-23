<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: reporting tables
 * 
 * Report definitions, templates, scheduled reports, saved views, export layouts.
 * 
 * Classification: MIXED (definitions global, user reports user-owned)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Report definitions (global)
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->nullable()
                ->constrained('modules')
                ->nullOnDelete()
                ->name('fk_rptdef_module__mod');
            $table->string('report_key', 100)->unique('uq_rptdef_key');
            $table->string('report_name', 191);
            $table->string('report_name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('report_type', 50)->default('tabular'); // tabular, chart, summary
            $table->json('available_columns')->nullable();
            $table->json('default_columns')->nullable();
            $table->json('available_filters')->nullable();
            $table->json('default_filters')->nullable();
            $table->json('available_groupings')->nullable();
            $table->json('chart_options')->nullable();
            $table->string('data_source', 255)->nullable();
            $table->text('query_template')->nullable();
            $table->boolean('supports_export')->default(true);
            $table->json('export_formats')->nullable();
            $table->boolean('supports_scheduling')->default(true);
            $table->boolean('is_branch_specific')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('module_id', 'idx_rptdef_module_id');
            $table->index('is_active', 'idx_rptdef_is_active');
        });

        // Report templates (global)
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 100)->unique('uq_rpttpl_key');
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->string('route_name', 191)->nullable();
            $table->json('default_filters')->nullable();
            $table->string('output_type', 30)->default('html'); // html, pdf, excel, csv
            $table->json('export_columns')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('module', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('required_permission', 100)->nullable();
            $table->timestamps();

            $table->index('module', 'idx_rpttpl_module');
            $table->index('is_active', 'idx_rpttpl_is_active');
        });

        // Scheduled reports (user-owned)
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_schdrpt_user__usr');
            $table->foreignId('report_template_id')
                ->nullable()
                ->constrained('report_templates')
                ->nullOnDelete()
                ->name('fk_schdrpt_template__rpttpl');
            $table->string('route_name', 191)->nullable();
            $table->string('cron_expression', 100);
            $table->json('filters')->nullable();
            $table->string('recipient_email', 191)->nullable();
            $table->string('last_status', 30)->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('runs_count')->default(0);
            $table->unsignedInteger('failures_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id', 'idx_schdrpt_user_id');
            $table->index('is_active', 'idx_schdrpt_is_active');
        });

        // Saved report views (user-owned)
        Schema::create('saved_report_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_svdrptv_user__usr');
            $table->string('name', 191);
            $table->string('report_type', 100);
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->json('ordering')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id', 'idx_svdrptv_user_id');
            $table->index('report_type', 'idx_svdrptv_type');
        });

        // Export layouts (user-owned)
        Schema::create('export_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_explyt_user__usr');
            $table->foreignId('report_definition_id')
                ->nullable()
                ->constrained('report_definitions')
                ->nullOnDelete()
                ->name('fk_explyt_def__rptdef');
            $table->string('layout_name', 191);
            $table->string('entity_type', 100)->nullable();
            $table->json('selected_columns')->nullable();
            $table->json('column_order')->nullable();
            $table->json('column_labels')->nullable();
            $table->string('export_format', 30)->default('xlsx'); // xlsx, csv, pdf
            $table->boolean('include_headers')->default(true);
            $table->string('date_format', 50)->default('Y-m-d');
            $table->string('number_format', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index('user_id', 'idx_explyt_user_id');
            $table->index('entity_type', 'idx_explyt_entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_layouts');
        Schema::dropIfExists('saved_report_views');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('report_definitions');
    }
};
