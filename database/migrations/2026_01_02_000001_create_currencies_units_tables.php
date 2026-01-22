<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: currencies and units
 * 
 * Global reference tables for currencies and units of measure.
 * 
 * Classification: GLOBAL (system-wide reference data)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique('uq_curr_code');
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_curr_created_by__usr');
            $table->timestamps();

            $table->index('is_active', 'idx_curr_is_active');
            $table->index('is_base', 'idx_curr_is_base');
        });

        // Currency rates
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('rate', 18, 6);
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_currt_created_by__usr');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date'], 'uq_currt_pair_date');
            $table->index('effective_date', 'idx_currt_effective_date');
            $table->index('is_active', 'idx_currt_is_active');
        });

        // Units of measure
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('symbol', 20);
            $table->string('type', 50)->nullable();
            $table->foreignId('base_unit_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->name('fk_uom_base__uom');
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_uom_created_by__usr');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->name('fk_uom_updated_by__usr');
            $table->timestamps();

            $table->unique(['symbol', 'type'], 'uq_uom_symbol_type');
            $table->index('is_active', 'idx_uom_is_active');
            $table->index('type', 'idx_uom_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('currencies');
    }
};
