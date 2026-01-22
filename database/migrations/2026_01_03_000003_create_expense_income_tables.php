<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: expense and income categories and transactions
 * 
 * Expense and income tracking.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Expense categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_expcat_branch__brnch');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'name'], 'uq_expcat_branch_name');
            $table->index('branch_id', 'idx_expcat_branch_id');
            $table->index('is_active', 'idx_expcat_is_active');
        });

        // Income categories
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_inccat_branch__brnch');
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'name'], 'uq_inccat_branch_name');
            $table->index('branch_id', 'idx_inccat_branch_id');
            $table->index('is_active', 'idx_inccat_is_active');
        });

        // Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_exp_branch__brnch');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('expense_categories')
                ->nullOnDelete()
                ->name('fk_exp_category__expcat');
            $table->string('reference_number', 50);
            $table->date('expense_date');
            $table->decimal('amount', 18, 4);
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('taxes')
                ->nullOnDelete()
                ->name('fk_exp_tax__tax');
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4);
            $table->string('payment_method', 50)->nullable();
            $table->foreignId('bank_account_id')
                ->nullable()
                ->constrained('bank_accounts')
                ->nullOnDelete()
                ->name('fk_exp_bank__bnkacct');
            $table->string('status', 30)->default('pending'); // pending, approved, paid, void
            $table->text('description')->nullable();
            $table->string('vendor_name', 191)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_exp_journal__je');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_exp_created_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_exp_approved_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_exp_branch_ref');
            $table->index('branch_id', 'idx_exp_branch_id');
            $table->index('category_id', 'idx_exp_category_id');
            $table->index('expense_date', 'idx_exp_date');
            $table->index('status', 'idx_exp_status');
            $table->index('created_by', 'idx_exp_created_by');
            $table->index(['branch_id', 'id'], 'idx_exp_branch_id_id');
        });

        // Incomes
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_inc_branch__brnch');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('income_categories')
                ->nullOnDelete()
                ->name('fk_inc_category__inccat');
            $table->string('reference_number', 50);
            $table->date('income_date');
            $table->decimal('amount', 18, 4);
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('taxes')
                ->nullOnDelete()
                ->name('fk_inc_tax__tax');
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4);
            $table->string('payment_method', 50)->nullable();
            $table->foreignId('bank_account_id')
                ->nullable()
                ->constrained('bank_accounts')
                ->nullOnDelete()
                ->name('fk_inc_bank__bnkacct');
            $table->string('status', 30)->default('received'); // received, pending, void
            $table->text('description')->nullable();
            $table->string('payer_name', 191)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_inc_journal__je');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_inc_created_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'reference_number'], 'uq_inc_branch_ref');
            $table->index('branch_id', 'idx_inc_branch_id');
            $table->index('category_id', 'idx_inc_category_id');
            $table->index('income_date', 'idx_inc_date');
            $table->index('status', 'idx_inc_status');
            $table->index('created_by', 'idx_inc_created_by');
            $table->index(['branch_id', 'id'], 'idx_inc_branch_id_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('income_categories');
        Schema::dropIfExists('expense_categories');
    }
};
