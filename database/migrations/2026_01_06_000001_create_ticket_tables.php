<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: ticketing and helpdesk tables
 * 
 * Tickets, categories, priorities, SLAs, replies.
 * 
 * Classification: MIXED (categories/priorities global, tickets branch-owned)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ticket priorities (global)
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('slug', 100)->unique('uq_tktpri_slug');
            $table->unsignedSmallInteger('level')->default(0);
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('response_time_hours')->nullable();
            $table->unsignedSmallInteger('resolution_time_hours')->nullable();
            $table->unsignedSmallInteger('response_time_minutes')->nullable();
            $table->unsignedSmallInteger('resolution_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active', 'idx_tktpri_is_active');
            $table->index('level', 'idx_tktpri_level');
        });

        // Ticket SLA policies (global)
        Schema::create('ticket_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('first_response_time_hours')->nullable();
            $table->unsignedSmallInteger('resolution_time_hours')->nullable();
            $table->unsignedSmallInteger('response_time_minutes')->nullable();
            $table->unsignedSmallInteger('resolution_time_minutes')->nullable();
            $table->json('business_hours')->nullable();
            $table->boolean('business_hours_only')->default(false);
            $table->time('business_hours_start')->nullable();
            $table->time('business_hours_end')->nullable();
            $table->json('working_days')->nullable();
            $table->boolean('exclude_weekends')->default(true);
            $table->json('excluded_dates')->nullable();
            $table->boolean('auto_escalate')->default(false);
            $table->foreignId('escalate_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktsla_escalate__usr');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktsla_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktsla_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active', 'idx_tktsla_is_active');
        });

        // Ticket categories (global)
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('slug', 100)->unique('uq_tktcat_slug');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('ticket_categories')
                ->nullOnDelete()
                ->name('fk_tktcat_parent__tktcat');
            $table->foreignId('default_assignee_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktcat_assignee__usr');
            $table->foreignId('sla_policy_id')
                ->nullable()
                ->constrained('ticket_sla_policies')
                ->nullOnDelete()
                ->name('fk_tktcat_sla__tktsla');
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktcat_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktcat_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id', 'idx_tktcat_parent_id');
            $table->index('is_active', 'idx_tktcat_is_active');
        });

        // Tickets (branch-owned)
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_tkt_branch__brnch');
            $table->string('ticket_number', 50);
            $table->string('subject', 255);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('open'); // open, pending, in_progress, resolved, closed
            $table->foreignId('priority_id')
                ->nullable()
                ->constrained('ticket_priorities')
                ->nullOnDelete()
                ->name('fk_tkt_priority__tktpri');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->name('fk_tkt_customer__cust');
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tkt_assigned__usr');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('ticket_categories')
                ->nullOnDelete()
                ->name('fk_tkt_category__tktcat');
            $table->foreignId('sla_policy_id')
                ->nullable()
                ->constrained('ticket_sla_policies')
                ->nullOnDelete()
                ->name('fk_tkt_sla__tktsla');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->unsignedSmallInteger('satisfaction_rating')->nullable();
            $table->text('satisfaction_comment')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tkt_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tkt_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'ticket_number'], 'uq_tkt_branch_number');
            $table->index('branch_id', 'idx_tkt_branch_id');
            $table->index('status', 'idx_tkt_status');
            $table->index('priority_id', 'idx_tkt_priority_id');
            $table->index('category_id', 'idx_tkt_category_id');
            $table->index('assigned_to', 'idx_tkt_assigned_to');
            $table->index('customer_id', 'idx_tkt_customer_id');
            $table->index('due_date', 'idx_tkt_due_date');
            $table->index('created_at', 'idx_tkt_created_at');
        });

        // Ticket replies
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete()
                ->name('fk_tktrpl_ticket__tkt');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktrpl_user__usr');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('customer_email', 191)->nullable();
            $table->string('customer_name', 191)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktrpl_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_tktrpl_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_id', 'idx_tktrpl_ticket_id');
            $table->index('user_id', 'idx_tktrpl_user_id');
            $table->index('is_internal', 'idx_tktrpl_is_internal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('ticket_sla_policies');
        Schema::dropIfExists('ticket_priorities');
    }
};
