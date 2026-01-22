<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: fixed assets
 * 
 * Fixed asset management, depreciation, and maintenance.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fixed assets
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_fxast_branch__brnch');
            $table->string('asset_code', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('location', 191)->nullable();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_fxast_assigned__usr');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 18, 4)->default(0);
            $table->decimal('salvage_value', 18, 4)->default(0);
            $table->unsignedSmallInteger('useful_life_months')->nullable();
            $table->string('depreciation_method', 30)->default('straight_line'); // straight_line, declining_balance, units_of_production
            $table->decimal('accumulated_depreciation', 18, 4)->default(0);
            $table->decimal('current_value', 18, 4)->default(0);
            $table->foreignId('asset_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->name('fk_fxast_asset_acct__acct');
            $table->foreignId('depreciation_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->name('fk_fxast_depr_acct__acct');
            $table->foreignId('expense_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete()
                ->name('fk_fxast_exp_acct__acct');
            $table->string('status', 30)->default('active'); // active, disposed, under_maintenance
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_value', 18, 4)->nullable();
            $table->text('disposal_notes')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('warranty_vendor', 191)->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'asset_code'], 'uq_fxast_branch_code');
            $table->index('branch_id', 'idx_fxast_branch_id');
            $table->index('status', 'idx_fxast_status');
            $table->index('category', 'idx_fxast_category');
            $table->index('assigned_to', 'idx_fxast_assigned');
            $table->index(['branch_id', 'id'], 'idx_fxast_branch_id_id');
        });

        // Asset depreciation records
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')
                ->constrained('fixed_assets')
                ->cascadeOnDelete()
                ->name('fk_astdep_asset__fxast');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_astdep_branch__brnch');
            $table->date('depreciation_date');
            $table->string('period', 20)->nullable(); // e.g., "2026-01"
            $table->decimal('depreciation_amount', 18, 4);
            $table->decimal('accumulated_depreciation', 18, 4);
            $table->decimal('book_value', 18, 4);
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete()
                ->name('fk_astdep_journal__je');
            $table->string('status', 30)->default('pending'); // pending, posted
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_astdep_created_by__usr');
            $table->timestamps();

            $table->index('asset_id', 'idx_astdep_asset_id');
            $table->index('branch_id', 'idx_astdep_branch_id');
            $table->index('depreciation_date', 'idx_astdep_date');
            $table->index('status', 'idx_astdep_status');
        });

        // Asset maintenance logs
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')
                ->constrained('fixed_assets')
                ->cascadeOnDelete()
                ->name('fk_astml_asset__fxast');
            $table->date('maintenance_date');
            $table->string('maintenance_type', 50); // preventive, corrective, emergency
            $table->text('description')->nullable();
            $table->decimal('cost', 18, 4)->default(0);
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->name('fk_astml_vendor__supp');
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_astml_performed_by__usr');
            $table->date('next_maintenance_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_astml_created_by__usr');
            $table->timestamps();

            $table->index('asset_id', 'idx_astml_asset_id');
            $table->index('maintenance_date', 'idx_astml_date');
            $table->index('maintenance_type', 'idx_astml_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_logs');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('fixed_assets');
    }
};
