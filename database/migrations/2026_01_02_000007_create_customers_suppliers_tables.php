<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: customers and suppliers
 * 
 * Customer and supplier master data.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_cust_branch__brnch');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('type', 30)->default('individual'); // individual, company
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('website', 191)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('commercial_register', 50)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('contact_person', 191)->nullable();
            $table->string('contact_position', 100)->nullable();
            $table->foreignId('price_group_id')
                ->nullable()
                ->constrained('price_groups')
                ->nullOnDelete()
                ->name('fk_cust_price_grp__prcgrp');
            $table->decimal('credit_limit', 18, 4)->default(0);
            $table->decimal('balance', 18, 4)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->string('loyalty_tier', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('source', 50)->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender', 20)->nullable();
            $table->json('extra_attributes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_cust_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_cust_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_cust_branch_code');
            $table->index('branch_id', 'idx_cust_branch_id');
            $table->index('is_active', 'idx_cust_is_active');
            $table->index('type', 'idx_cust_type');
            $table->index('email', 'idx_cust_email');
            $table->index('phone', 'idx_cust_phone');
            $table->index('price_group_id', 'idx_cust_price_group');
            $table->index(['branch_id', 'id'], 'idx_cust_branch_id_id');
        });

        // Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_supp_branch__brnch');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->string('name_ar', 191)->nullable();
            $table->string('type', 30)->default('company'); // individual, company
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('website', 191)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('commercial_register', 50)->nullable();
            $table->string('contact_person', 191)->nullable();
            $table->string('contact_position', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->string('bank_swift', 20)->nullable();
            $table->decimal('balance', 18, 4)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->string('currency', 10)->nullable();
            $table->decimal('credit_limit', 18, 4)->default(0);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->decimal('minimum_order_amount', 18, 4)->nullable();
            $table->decimal('shipping_cost', 18, 4)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('product_categories')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_supp_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_supp_updated_by__usr');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code'], 'uq_supp_branch_code');
            $table->index('branch_id', 'idx_supp_branch_id');
            $table->index('is_active', 'idx_supp_is_active');
            $table->index('is_preferred', 'idx_supp_is_preferred');
            $table->index('type', 'idx_supp_type');
            $table->index('email', 'idx_supp_email');
            $table->index(['branch_id', 'id'], 'idx_supp_branch_id_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
