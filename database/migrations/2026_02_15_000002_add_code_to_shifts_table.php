<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }
        });

        // Add a unique index for (branch_id, code) when possible.
        // Some database drivers require the column to exist before adding the index.
        Schema::table('shifts', function (Blueprint $table) {
            // Avoid duplicate index creation
            $table->unique(['branch_id', 'code'], 'uq_shifts_branch_code');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique('uq_shifts_branch_code');

            if (Schema::hasColumn('shifts', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
