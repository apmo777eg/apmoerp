<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: accounting tables
 * 
 * Chart of accounts, fiscal periods, and journal entries.
 * 
 * Classification: BRANCH-OWNED (accounting is branch-scoped)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fiscal periods
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_fscprd_branch__brnch');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('period');
            $table->string('name', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('open'); // open, closed, locked
            $table->timestamps();

            $table->unique(['branch_id', 'year', 'period'], 'uq_fscprd_branch_year_period');
            $table->index('branch_id', 'idx_fscprd_branch_id');
            $table->index('status', 'idx_fscprd_status');
            $table->index(['start_date', 'end_date'], 'idx_fscprd_dates');
        });

        // Accounts (Chart of Accounts)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_acct_branch__brnch');
            $table->string('account_number', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('type', 30); // asset, liability, equity, revenue, expense
            $table->string('currency_code', 10)->nullable();
            $table->boolean('requires_currency')->default(false);
            $table->string('account_category', 50)->nullable();
            $table->string('sub_category', 50)->nullable();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->name('fk_acct_parent__acct');
            $table->decimal('balance', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_account')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'account_number'], 'uq_acct_branch_number');
            $table->index('branch_id', 'idx_acct_branch_id');
            $table->index('type', 'idx_acct_type');
            $table->index('account_category', 'idx_acct_category');
            $table->index('parent_id', 'idx_acct_parent_id');
            $table->index('is_active', 'idx_acct_is_active');
            $table->index(['branch_id', 'id'], 'idx_acct_branch_id_id');
        });

        // Account mappings (for automatic journal entries)
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_acctmap_branch__brnch');
            $table->string('mapping_key', 100);
            $table->string('mapping_type', 50);
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->name('fk_acctmap_account__acct');
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'mapping_key', 'mapping_type'], 'uq_acctmap_branch_key_type');
            $table->index('branch_id', 'idx_acctmap_branch_id');
            $table->index('mapping_type', 'idx_acctmap_mapping_type');
        });

        // Journal entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_je_branch__brnch');
            $table->foreignId('fiscal_period_id')
                ->nullable()
                ->constrained('fiscal_periods')
                ->nullOnDelete()
                ->name('fk_je_fiscal_period__fscprd');
            $table->string('reference_number', 50);
            $table->string('type', 30)->default('standard'); // standard, opening, closing, reversal
            $table->date('entry_date');
            $table->text('description')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 30)->default('draft'); // draft, posted, void
            $table->decimal('total_debit', 18, 4)->default(0);
            $table->decimal('total_credit', 18, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->foreignId('reversed_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_je_reversed__je');
            $table->foreignId('reversed_by_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_je_reversed_by__je');
            $table->string('source_module', 50)->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->unsignedTinyInteger('fiscal_period')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_je_created_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_je_approved_by__usr');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_je_branch_ref');
            $table->index('branch_id', 'idx_je_branch_id');
            $table->index('status', 'idx_je_status');
            $table->index('entry_date', 'idx_je_entry_date');
            $table->index('type', 'idx_je_type');
            $table->index('fiscal_period_id', 'idx_je_fiscal_period');
            $table->index(['reference_type', 'reference_id'], 'idx_je_reference');
            $table->index('source_module', 'idx_je_source_module');
            $table->index('created_by', 'idx_je_created_by');
            $table->index(['branch_id', 'id'], 'idx_je_branch_id_id');
        });

        // Journal entry lines
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete()
                ->name('fk_jel_journal__je');
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete()
                ->name('fk_jel_account__acct');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->string('description', 500)->nullable();
            $table->string('reference', 191)->nullable();
            $table->timestamps();

            $table->index('journal_entry_id', 'idx_jel_journal_id');
            $table->index('account_id', 'idx_jel_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('fiscal_periods');
    }
};
