<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: vehicle models and product compatibilities
 * 
 * Vehicle models for automotive parts compatibility.
 * 
 * Classification: GLOBAL (vehicle_models), BRANCH-OWNED (product_compatibilities via product)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vehicle models (global reference)
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('engine_type', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['brand', 'model', 'year_from'], 'uq_vehmod_brand_model_year');
            $table->index('brand', 'idx_vehmod_brand');
            $table->index('is_active', 'idx_vehmod_is_active');
        });

        // Product compatibilities (pivot)
        Schema::create('product_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->name('fk_prdcmp_product__prd');
            $table->foreignId('vehicle_model_id')
                ->constrained('vehicle_models')
                ->cascadeOnDelete()
                ->name('fk_prdcmp_vehicle__vehmod');
            $table->string('oem_number', 100)->nullable();
            $table->string('position', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'vehicle_model_id'], 'uq_prdcmp_prod_vehicle');
            $table->index('vehicle_model_id', 'idx_prdcmp_vehicle_id');
            $table->index('oem_number', 'idx_prdcmp_oem');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_compatibilities');
        Schema::dropIfExists('vehicle_models');
    }
};
