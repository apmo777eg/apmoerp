<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add missing columns to align models with database schema
 *
 * NEW-005 FIX: This migration adds columns that are defined in model fillable/casts
 * but were missing from the original migrations.
 *
 * Affected tables:
 * - account_mappings: module_name
 * - audit_logs: action, impersonating_as_id, ip, meta, module_key, performed_by_id, subject_id, subject_type, target_user_id
 * - cashflow_projections: period_type, variance, inflow_breakdown, outflow_breakdown
 * - credit_notes: amount, applied_date, auto_apply, journal_entry_id, posted_at, posted_to_accounting, updated_by
 * - credit_note_applications: applied_amount
 * - debit_notes: amount, applied_date, auto_apply, journal_entry_id, posted_at, posted_to_accounting, updated_by
 * - installment_payments: paid_by, payment_reference
 * - installment_plans: created_by, installment_amount, notes, num_installments, remaining_amount
 * - leave_accrual_rules: created_by, prorate_on_joining, prorate_on_leaving, waiting_period_months
 * - leave_adjustments: amount, approved_at, approved_by, created_by, notes
 * - leave_balances: opening_balance, annual_quota, accrued, used, pending, available, carry_forward_from_previous, carry_forward_expiry_date, notes
 * - leave_encashments: encashment_number, rate_per_day, currency, processed_by, created_by
 * - leave_request_approvals: approval_level (rename level)
 */
return new class extends Migration
{
    public function up(): void
    {
        // account_mappings: add module_name column
        if (Schema::hasTable('account_mappings') && ! Schema::hasColumn('account_mappings', 'module_name')) {
            Schema::table('account_mappings', function (Blueprint $table) {
                $table->string('module_name', 100)->nullable()->after('branch_id');
            });
        }

        // audit_logs: add missing columns for impersonation tracking and enhanced audit info
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_logs', 'action')) {
                    $table->string('action', 100)->nullable()->after('auditable_id');
                }
                if (! Schema::hasColumn('audit_logs', 'performed_by_id')) {
                    $table->foreignId('performed_by_id')->nullable()->after('user_id')
                        ->constrained('users')->nullOnDelete()->name('fk_audlog_performed_by__usr');
                }
                if (! Schema::hasColumn('audit_logs', 'impersonating_as_id')) {
                    $table->foreignId('impersonating_as_id')->nullable()->after('performed_by_id')
                        ->constrained('users')->nullOnDelete()->name('fk_audlog_impersonating__usr');
                }
                if (! Schema::hasColumn('audit_logs', 'target_user_id')) {
                    $table->foreignId('target_user_id')->nullable()->after('impersonating_as_id')
                        ->constrained('users')->nullOnDelete()->name('fk_audlog_target_user__usr');
                }
                if (! Schema::hasColumn('audit_logs', 'module_key')) {
                    $table->string('module_key', 100)->nullable()->after('action');
                }
                if (! Schema::hasColumn('audit_logs', 'subject_type')) {
                    $table->string('subject_type', 191)->nullable()->after('module_key');
                }
                if (! Schema::hasColumn('audit_logs', 'subject_id')) {
                    $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
                }
                if (! Schema::hasColumn('audit_logs', 'ip')) {
                    $table->string('ip', 45)->nullable()->after('url');
                }
                if (! Schema::hasColumn('audit_logs', 'meta')) {
                    $table->json('meta')->nullable()->after('extra_attributes');
                }
            });
        }

        // cashflow_projections: add missing columns for enhanced projection tracking
        if (Schema::hasTable('cashflow_projections')) {
            Schema::table('cashflow_projections', function (Blueprint $table) {
                if (! Schema::hasColumn('cashflow_projections', 'period_type')) {
                    $table->string('period_type', 30)->default('daily')->after('projection_date');
                }
                if (! Schema::hasColumn('cashflow_projections', 'variance')) {
                    $table->decimal('variance', 18, 4)->default(0)->after('actual_balance');
                }
                if (! Schema::hasColumn('cashflow_projections', 'inflow_breakdown')) {
                    $table->json('inflow_breakdown')->nullable()->after('variance');
                }
                if (! Schema::hasColumn('cashflow_projections', 'outflow_breakdown')) {
                    $table->json('outflow_breakdown')->nullable()->after('inflow_breakdown');
                }
            });
        }

        // credit_notes: add missing columns for accounting integration
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (! Schema::hasColumn('credit_notes', 'amount')) {
                    $table->decimal('amount', 18, 2)->default(0)->after('type');
                }
                if (! Schema::hasColumn('credit_notes', 'applied_date')) {
                    $table->date('applied_date')->nullable()->after('issue_date');
                }
                if (! Schema::hasColumn('credit_notes', 'auto_apply')) {
                    $table->boolean('auto_apply')->default(false)->after('is_refunded');
                }
                if (! Schema::hasColumn('credit_notes', 'journal_entry_id')) {
                    $table->foreignId('journal_entry_id')->nullable()->after('notes')
                        ->constrained('journal_entries')->nullOnDelete()->name('fk_crnt_journal__je');
                }
                if (! Schema::hasColumn('credit_notes', 'posted_to_accounting')) {
                    $table->boolean('posted_to_accounting')->default(false)->after('journal_entry_id');
                }
                if (! Schema::hasColumn('credit_notes', 'posted_at')) {
                    $table->timestamp('posted_at')->nullable()->after('posted_to_accounting');
                }
                if (! Schema::hasColumn('credit_notes', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('approved_at')
                        ->constrained('users')->nullOnDelete()->name('fk_crnt_updated_by__usr');
                }
            });
        }

        // credit_note_applications: add applied_amount alias column
        if (Schema::hasTable('credit_note_applications') && ! Schema::hasColumn('credit_note_applications', 'applied_amount')) {
            Schema::table('credit_note_applications', function (Blueprint $table) {
                $table->decimal('applied_amount', 18, 2)->default(0)->after('sale_id');
            });
        }

        // debit_notes: add missing columns for accounting integration
        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                if (! Schema::hasColumn('debit_notes', 'amount')) {
                    $table->decimal('amount', 18, 2)->default(0)->after('type');
                }
                if (! Schema::hasColumn('debit_notes', 'applied_date')) {
                    $table->date('applied_date')->nullable()->after('issue_date');
                }
                if (! Schema::hasColumn('debit_notes', 'auto_apply')) {
                    $table->boolean('auto_apply')->default(false)->after('is_refunded');
                }
                if (! Schema::hasColumn('debit_notes', 'journal_entry_id')) {
                    $table->foreignId('journal_entry_id')->nullable()->after('notes')
                        ->constrained('journal_entries')->nullOnDelete()->name('fk_dbnt_journal__je');
                }
                if (! Schema::hasColumn('debit_notes', 'posted_to_accounting')) {
                    $table->boolean('posted_to_accounting')->default(false)->after('journal_entry_id');
                }
                if (! Schema::hasColumn('debit_notes', 'posted_at')) {
                    $table->timestamp('posted_at')->nullable()->after('posted_to_accounting');
                }
                if (! Schema::hasColumn('debit_notes', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('approved_at')
                        ->constrained('users')->nullOnDelete()->name('fk_dbnt_updated_by__usr');
                }
            });
        }

        // installment_payments: add missing columns
        if (Schema::hasTable('installment_payments')) {
            Schema::table('installment_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('installment_payments', 'paid_by')) {
                    $table->foreignId('paid_by')->nullable()->after('notes')
                        ->constrained('users')->nullOnDelete()->name('fk_instpay_paid_by__usr');
                }
                if (! Schema::hasColumn('installment_payments', 'payment_reference')) {
                    $table->string('payment_reference', 100)->nullable()->after('payment_method');
                }
            });
        }

        // installment_plans: add missing columns
        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                if (! Schema::hasColumn('installment_plans', 'num_installments')) {
                    $table->unsignedSmallInteger('num_installments')->default(0)->after('interest_rate');
                }
                if (! Schema::hasColumn('installment_plans', 'installment_amount')) {
                    $table->decimal('installment_amount', 18, 4)->default(0)->after('num_installments');
                }
                if (! Schema::hasColumn('installment_plans', 'remaining_amount')) {
                    $table->decimal('remaining_amount', 18, 4)->default(0)->after('down_payment');
                }
                if (! Schema::hasColumn('installment_plans', 'notes')) {
                    $table->text('notes')->nullable()->after('status');
                }
                if (! Schema::hasColumn('installment_plans', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('notes')
                        ->constrained('users')->nullOnDelete()->name('fk_instpln_created_by__usr');
                }
            });
        }

        // leave_accrual_rules: add missing columns
        if (Schema::hasTable('leave_accrual_rules')) {
            Schema::table('leave_accrual_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_accrual_rules', 'prorate_on_joining')) {
                    $table->boolean('prorate_on_joining')->default(true)->after('prorate_for_new_hires');
                }
                if (! Schema::hasColumn('leave_accrual_rules', 'prorate_on_leaving')) {
                    $table->boolean('prorate_on_leaving')->default(false)->after('prorate_on_joining');
                }
                if (! Schema::hasColumn('leave_accrual_rules', 'waiting_period_months')) {
                    $table->unsignedTinyInteger('waiting_period_months')->default(0)->after('prorate_on_leaving');
                }
                if (! Schema::hasColumn('leave_accrual_rules', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('effective_to')
                        ->constrained('users')->nullOnDelete()->name('fk_lvacr_created_by__usr');
                }
            });
        }

        // leave_adjustments: add missing columns (model uses 'amount' instead of 'days')
        if (Schema::hasTable('leave_adjustments')) {
            Schema::table('leave_adjustments', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_adjustments', 'amount')) {
                    $table->decimal('amount', 5, 2)->default(0)->after('adjustment_type');
                }
                if (! Schema::hasColumn('leave_adjustments', 'notes')) {
                    $table->text('notes')->nullable()->after('reason');
                }
                if (! Schema::hasColumn('leave_adjustments', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('adjusted_by')
                        ->constrained('users')->nullOnDelete()->name('fk_lvadj_approved_by__usr');
                }
                if (! Schema::hasColumn('leave_adjustments', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
                if (! Schema::hasColumn('leave_adjustments', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('approved_at')
                        ->constrained('users')->nullOnDelete()->name('fk_lvadj_created_by__usr');
                }
            });
        }

        // leave_balances: add missing columns for enhanced balance tracking
        if (Schema::hasTable('leave_balances')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_balances', 'opening_balance')) {
                    $table->decimal('opening_balance', 5, 2)->default(0)->after('year');
                }
                if (! Schema::hasColumn('leave_balances', 'annual_quota')) {
                    $table->decimal('annual_quota', 5, 2)->default(0)->after('opening_balance');
                }
                if (! Schema::hasColumn('leave_balances', 'accrued')) {
                    $table->decimal('accrued', 5, 2)->default(0)->after('annual_quota');
                }
                if (! Schema::hasColumn('leave_balances', 'used')) {
                    $table->decimal('used', 5, 2)->default(0)->after('accrued');
                }
                if (! Schema::hasColumn('leave_balances', 'pending')) {
                    $table->decimal('pending', 5, 2)->default(0)->after('used');
                }
                if (! Schema::hasColumn('leave_balances', 'available')) {
                    $table->decimal('available', 5, 2)->default(0)->after('pending');
                }
                if (! Schema::hasColumn('leave_balances', 'carry_forward_from_previous')) {
                    $table->decimal('carry_forward_from_previous', 5, 2)->default(0)->after('available');
                }
                if (! Schema::hasColumn('leave_balances', 'carry_forward_expiry_date')) {
                    $table->date('carry_forward_expiry_date')->nullable()->after('carry_forward_from_previous');
                }
                if (! Schema::hasColumn('leave_balances', 'notes')) {
                    $table->text('notes')->nullable()->after('last_accrual_date');
                }
            });
        }

        // leave_encashments: add missing columns
        if (Schema::hasTable('leave_encashments')) {
            Schema::table('leave_encashments', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_encashments', 'encashment_number')) {
                    $table->string('encashment_number', 50)->nullable()->after('id');
                    $table->index('encashment_number', 'idx_lvenc_number');
                }
                if (! Schema::hasColumn('leave_encashments', 'rate_per_day')) {
                    $table->decimal('rate_per_day', 18, 2)->default(0)->after('days_encashed');
                }
                if (! Schema::hasColumn('leave_encashments', 'currency')) {
                    $table->string('currency', 10)->default('USD')->after('total_amount');
                }
                if (! Schema::hasColumn('leave_encashments', 'processed_by')) {
                    $table->foreignId('processed_by')->nullable()->after('approved_at')
                        ->constrained('users')->nullOnDelete()->name('fk_lvenc_processed_by__usr');
                }
                if (! Schema::hasColumn('leave_encashments', 'processed_at')) {
                    // Use approved_at as reference since processed_by might not exist yet
                    $table->timestamp('processed_at')->nullable()->after('approved_at');
                }
                if (! Schema::hasColumn('leave_encashments', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('notes')
                        ->constrained('users')->nullOnDelete()->name('fk_lvenc_created_by__usr');
                }
            });
        }

        // leave_request_approvals: add approval_level column (model uses this instead of 'level')
        if (Schema::hasTable('leave_request_approvals') && ! Schema::hasColumn('leave_request_approvals', 'approval_level')) {
            Schema::table('leave_request_approvals', function (Blueprint $table) {
                $table->unsignedSmallInteger('approval_level')->default(1)->after('approver_id');
            });
        }
    }

    public function down(): void
    {
        // account_mappings
        if (Schema::hasTable('account_mappings') && Schema::hasColumn('account_mappings', 'module_name')) {
            Schema::table('account_mappings', function (Blueprint $table) {
                $table->dropColumn('module_name');
            });
        }

        // audit_logs
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $columns = ['action', 'module_key', 'subject_type', 'subject_id', 'ip', 'meta'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('audit_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('audit_logs', 'performed_by_id')) {
                    $table->dropForeign('fk_audlog_performed_by__usr');
                    $table->dropColumn('performed_by_id');
                }
                if (Schema::hasColumn('audit_logs', 'impersonating_as_id')) {
                    $table->dropForeign('fk_audlog_impersonating__usr');
                    $table->dropColumn('impersonating_as_id');
                }
                if (Schema::hasColumn('audit_logs', 'target_user_id')) {
                    $table->dropForeign('fk_audlog_target_user__usr');
                    $table->dropColumn('target_user_id');
                }
            });
        }

        // cashflow_projections
        if (Schema::hasTable('cashflow_projections')) {
            Schema::table('cashflow_projections', function (Blueprint $table) {
                $columns = ['period_type', 'variance', 'inflow_breakdown', 'outflow_breakdown'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('cashflow_projections', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // credit_notes
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                $columns = ['amount', 'applied_date', 'auto_apply', 'posted_to_accounting', 'posted_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('credit_notes', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('credit_notes', 'journal_entry_id')) {
                    $table->dropForeign('fk_crnt_journal__je');
                    $table->dropColumn('journal_entry_id');
                }
                if (Schema::hasColumn('credit_notes', 'updated_by')) {
                    $table->dropForeign('fk_crnt_updated_by__usr');
                    $table->dropColumn('updated_by');
                }
            });
        }

        // credit_note_applications
        if (Schema::hasTable('credit_note_applications') && Schema::hasColumn('credit_note_applications', 'applied_amount')) {
            Schema::table('credit_note_applications', function (Blueprint $table) {
                $table->dropColumn('applied_amount');
            });
        }

        // debit_notes
        if (Schema::hasTable('debit_notes')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                $columns = ['amount', 'applied_date', 'auto_apply', 'posted_to_accounting', 'posted_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('debit_notes', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('debit_notes', 'journal_entry_id')) {
                    $table->dropForeign('fk_dbnt_journal__je');
                    $table->dropColumn('journal_entry_id');
                }
                if (Schema::hasColumn('debit_notes', 'updated_by')) {
                    $table->dropForeign('fk_dbnt_updated_by__usr');
                    $table->dropColumn('updated_by');
                }
            });
        }

        // installment_payments
        if (Schema::hasTable('installment_payments')) {
            Schema::table('installment_payments', function (Blueprint $table) {
                if (Schema::hasColumn('installment_payments', 'paid_by')) {
                    $table->dropForeign('fk_instpay_paid_by__usr');
                    $table->dropColumn('paid_by');
                }
                if (Schema::hasColumn('installment_payments', 'payment_reference')) {
                    $table->dropColumn('payment_reference');
                }
            });
        }

        // installment_plans
        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                $columns = ['num_installments', 'installment_amount', 'remaining_amount', 'notes'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('installment_plans', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('installment_plans', 'created_by')) {
                    $table->dropForeign('fk_instpln_created_by__usr');
                    $table->dropColumn('created_by');
                }
            });
        }

        // leave_accrual_rules
        if (Schema::hasTable('leave_accrual_rules')) {
            Schema::table('leave_accrual_rules', function (Blueprint $table) {
                $columns = ['prorate_on_joining', 'prorate_on_leaving', 'waiting_period_months'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('leave_accrual_rules', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('leave_accrual_rules', 'created_by')) {
                    $table->dropForeign('fk_lvacr_created_by__usr');
                    $table->dropColumn('created_by');
                }
            });
        }

        // leave_adjustments
        if (Schema::hasTable('leave_adjustments')) {
            Schema::table('leave_adjustments', function (Blueprint $table) {
                $columns = ['amount', 'notes'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('leave_adjustments', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('leave_adjustments', 'approved_by')) {
                    $table->dropForeign('fk_lvadj_approved_by__usr');
                    $table->dropColumn('approved_by');
                }
                if (Schema::hasColumn('leave_adjustments', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }
                if (Schema::hasColumn('leave_adjustments', 'created_by')) {
                    $table->dropForeign('fk_lvadj_created_by__usr');
                    $table->dropColumn('created_by');
                }
            });
        }

        // leave_balances
        if (Schema::hasTable('leave_balances')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                $columns = ['opening_balance', 'annual_quota', 'accrued', 'used', 'pending', 'available', 'carry_forward_from_previous', 'carry_forward_expiry_date', 'notes'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('leave_balances', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // leave_encashments
        if (Schema::hasTable('leave_encashments')) {
            Schema::table('leave_encashments', function (Blueprint $table) {
                if (Schema::hasColumn('leave_encashments', 'encashment_number')) {
                    $table->dropIndex('idx_lvenc_number');
                    $table->dropColumn('encashment_number');
                }
                $columns = ['rate_per_day', 'currency'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('leave_encashments', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('leave_encashments', 'processed_by')) {
                    $table->dropForeign('fk_lvenc_processed_by__usr');
                    $table->dropColumn('processed_by');
                }
                if (Schema::hasColumn('leave_encashments', 'processed_at')) {
                    $table->dropColumn('processed_at');
                }
                if (Schema::hasColumn('leave_encashments', 'created_by')) {
                    $table->dropForeign('fk_lvenc_created_by__usr');
                    $table->dropColumn('created_by');
                }
            });
        }

        // leave_request_approvals
        if (Schema::hasTable('leave_request_approvals') && Schema::hasColumn('leave_request_approvals', 'approval_level')) {
            Schema::table('leave_request_approvals', function (Blueprint $table) {
                $table->dropColumn('approval_level');
            });
        }
    }
};
