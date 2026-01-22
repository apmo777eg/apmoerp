<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: stores and integrations
 * 
 * E-commerce store integrations and orders.
 * 
 * Classification: BRANCH-OWNED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Stores
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('type', 50); // woocommerce, shopify, magento
            $table->string('url', 500)->nullable();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_store_branch__brnch');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id', 'idx_store_branch_id');
            $table->index('is_active', 'idx_store_is_active');
            $table->index('type', 'idx_store_type');
        });

        // Store integrations
        Schema::create('store_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->name('fk_storint_store__store');
            $table->string('platform', 50);
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('webhook_secret', 255)->nullable();
            $table->boolean('webhooks_registered')->default(false);
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('store_id', 'idx_storint_store_id');
            $table->index('is_active', 'idx_storint_is_active');
        });

        // Store tokens
        Schema::create('store_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->name('fk_stortkn_store__store');
            $table->string('name', 100);
            $table->string('token', 80)->unique('uq_stortkn_token');
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_stortkn_store_id');
        });

        // Store orders
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete()
                ->name('fk_stord_store__store');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete()
                ->name('fk_stord_branch__brnch');
            $table->string('external_order_id', 100);
            $table->string('status', 50)->default('pending');
            $table->string('currency', 10)->default('USD');
            $table->decimal('total', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('shipping_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_order_id'], 'uq_stord_store_external');
            $table->index('branch_id', 'idx_stord_branch_id');
            $table->index('status', 'idx_stord_status');
        });

        // Store sync logs
        Schema::create('store_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->name('fk_storsync_store__store');
            $table->string('type', 50); // products, orders, inventory
            $table->string('direction', 20); // push, pull
            $table->string('status', 30)->default('pending'); // pending, running, completed, failed
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_success')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_storsync_store_id');
            $table->index('status', 'idx_storsync_status');
            $table->index('type', 'idx_storsync_type');
        });

        // Product store mappings
        Schema::create('product_store_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdstm_product__prd');
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->name('fk_prdstm_store__store');
            $table->string('external_id', 100)->nullable();
            $table->string('external_sku', 100)->nullable();
            $table->json('external_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'store_id'], 'uq_prdstm_prod_store');
            $table->index('store_id', 'idx_prdstm_store_id');
            $table->index('external_id', 'idx_prdstm_external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_store_mappings');
        Schema::dropIfExists('store_sync_logs');
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('store_tokens');
        Schema::dropIfExists('store_integrations');
        Schema::dropIfExists('stores');
    }
};
