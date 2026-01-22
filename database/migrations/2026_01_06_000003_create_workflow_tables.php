<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: workflow tables
 * 
 * Workflow definitions, instances, approvals, audit logs.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Workflow definitions
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_wfdef_branch__brnch');
            $table->string('name', 191);
            $table->string('code', 50);
            $table->string('module_name', 50)->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->json('stages')->nullable();
            $table->json('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wfdef_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_wfdef_branch_code');
            $table->index('branch_id', 'idx_wfdef_branch_id');
            $table->index('module_name', 'idx_wfdef_module');
            $table->index('is_active', 'idx_wfdef_is_active');
        });

        // Workflow rules
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->cascadeOnDelete()
                ->name('fk_wfrule_def__wfdef');
            $table->string('name', 191);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('workflow_definition_id', 'idx_wfrule_def_id');
            $table->index('is_active', 'idx_wfrule_is_active');
        });

        // Workflow instances
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->cascadeOnDelete()
                ->name('fk_wfinst_def__wfdef');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_wfinst_branch__brnch');
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('current_stage', 50)->nullable();
            $table->string('status', 30)->default('pending'); // pending, in_progress, approved, rejected, cancelled
            $table->foreignId('initiated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wfinst_initiated_by__usr');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('workflow_definition_id', 'idx_wfinst_def_id');
            $table->index('branch_id', 'idx_wfinst_branch_id');
            $table->index(['entity_type', 'entity_id'], 'idx_wfinst_entity');
            $table->index('status', 'idx_wfinst_status');
        });

        // Workflow approvals
        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->cascadeOnDelete()
                ->name('fk_wfappr_inst__wfinst');
            $table->string('stage_name', 50);
            $table->unsignedSmallInteger('stage_order')->default(0);
            $table->foreignId('approver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wfappr_approver__usr');
            $table->string('approver_role', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index('workflow_instance_id', 'idx_wfappr_inst_id');
            $table->index('approver_id', 'idx_wfappr_approver_id');
            $table->index('status', 'idx_wfappr_status');
        });

        // Workflow audit logs
        Schema::create('workflow_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->cascadeOnDelete()
                ->name('fk_wfaudit_inst__wfinst');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_wfaudit_user__usr');
            $table->string('action', 50);
            $table->string('from_stage', 50)->nullable();
            $table->string('to_stage', 50)->nullable();
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index('workflow_instance_id', 'idx_wfaudit_inst_id');
            $table->index('user_id', 'idx_wfaudit_user_id');
        });

        // Workflow notifications
        Schema::create('workflow_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->cascadeOnDelete()
                ->name('fk_wfnotif_inst__wfinst');
            $table->foreignId('workflow_approval_id')
                ->nullable()
                ->constrained('workflow_approvals')
                ->cascadeOnDelete()
                ->name('fk_wfnotif_appr__wfappr');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->name('fk_wfnotif_user__usr');
            $table->string('type', 50);
            $table->string('channel', 30)->default('database'); // database, email, sms
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->string('delivery_status', 30)->nullable();
            $table->string('priority', 20)->default('normal');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('workflow_instance_id', 'idx_wfnotif_inst_id');
            $table->index('user_id', 'idx_wfnotif_user_id');
            $table->index('is_sent', 'idx_wfnotif_is_sent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_notifications');
        Schema::dropIfExists('workflow_audit_logs');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_rules');
        Schema::dropIfExists('workflow_definitions');
    }
};
