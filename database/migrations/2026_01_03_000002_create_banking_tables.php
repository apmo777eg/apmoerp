<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: bank accounts and transactions
 *
 * Bank account management and reconciliation.
 *
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bank accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_bnkacct_branch__brnch');
            $table->string('account_number', 50);
            $table->string('account_name', 191);
            $table->string('bank_name', 191);
            $table->string('bank_branch', 191)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('account_type', 30)->default('checking'); // checking, savings, credit
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('current_balance', 18, 4)->default(0);
            $table->date('opening_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_bnkacct_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_bnkacct_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'account_number'], 'uq_bnkacct_branch_number');
            $table->index('branch_id', 'idx_bnkacct_branch_id');
            $table->index('status', 'idx_bnkacct_status');
            $table->index(['branch_id', 'id'], 'idx_bnkacct_branch_id_id');
        });

        // Bank reconciliations
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')
                ->constrained('bank_accounts')
                ->cascadeOnDelete()
                ->name('fk_bnkrec_account__bnkacct');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_bnkrec_branch__brnch');
            $table->string('reconciliation_number', 50);
            $table->date('statement_date');
            $table->date('reconciliation_date')->nullable();
            $table->decimal('statement_balance', 18, 4)->default(0);
            $table->decimal('book_balance', 18, 4)->default(0);
            $table->decimal('difference', 18, 4)->default(0);
            $table->string('status', 30)->default('draft'); // draft, in_progress, completed
            $table->text('notes')->nullable();
            $table->json('adjustments')->nullable();
            $table->foreignId('reconciled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_bnkrec_reconciled_by__usr');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_bnkrec_approved_by__usr');
            $table->timestamps();

            $table->unique(['bank_account_id', 'reconciliation_number'], 'uq_bnkrec_acct_number');
            $table->index('branch_id', 'idx_bnkrec_branch_id');
            $table->index('status', 'idx_bnkrec_status');
            $table->index('statement_date', 'idx_bnkrec_stmt_date');
        });

        // Bank transactions
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')
                ->constrained('bank_accounts')
                ->cascadeOnDelete()
                ->name('fk_bnktxn_account__bnkacct');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_bnktxn_branch__brnch');
            $table->string('reference_number', 50)->nullable();
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('type', 30); // deposit, withdrawal, transfer, fee, interest
            $table->decimal('amount', 18, 4);
            $table->decimal('balance_after', 18, 4)->nullable();
            $table->string('payee_payer', 191)->nullable();
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending'); // pending, cleared, reconciled, void
            $table->foreignId('reconciliation_id')
                ->nullable()
                ->constrained('bank_reconciliations')
                ->nullOnDelete()
                ->name('fk_bnktxn_reconciliation__bnkrec');
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_bnktxn_journal__je');
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_bnktxn_created_by__usr');
            $table->timestamps();

            $table->index('bank_account_id', 'idx_bnktxn_account_id');
            $table->index('branch_id', 'idx_bnktxn_branch_id');
            $table->index('transaction_date', 'idx_bnktxn_txn_date');
            $table->index('status', 'idx_bnktxn_status');
            $table->index('type', 'idx_bnktxn_type');
            $table->index('reconciliation_id', 'idx_bnktxn_reconciliation');
            $table->index(['related_type', 'related_id'], 'idx_bnktxn_related');
        });

        // Cashflow projections
        Schema::create('cashflow_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_cfproj_branch__brnch');
            $table->date('projection_date');
            $table->string('period_type', 30)->default('daily'); // NEW-005 FIX: Added for model compatibility
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('expected_inflows', 18, 4)->default(0);
            $table->decimal('expected_outflows', 18, 4)->default(0);
            $table->decimal('projected_balance', 18, 4)->default(0);
            $table->decimal('actual_inflows', 18, 4)->default(0);
            $table->decimal('actual_outflows', 18, 4)->default(0);
            $table->decimal('actual_balance', 18, 4)->default(0);
            $table->decimal('variance', 18, 4)->default(0); // NEW-005 FIX: Added for model compatibility
            $table->json('inflow_breakdown')->nullable(); // NEW-005 FIX: Added for model compatibility
            $table->json('outflow_breakdown')->nullable(); // NEW-005 FIX: Added for model compatibility
            $table->string('status', 30)->default('projected'); // projected, actual
            $table->json('inflow_details')->nullable();
            $table->json('outflow_details')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_cfproj_created_by__usr');
            $table->timestamps();

            $table->unique(['branch_id', 'projection_date'], 'uq_cfproj_branch_date');
            $table->index('branch_id', 'idx_cfproj_branch_id');
            $table->index('projection_date', 'idx_cfproj_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_projections');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_accounts');
    }
};
