<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Align module icons with the UI (emoji-based icons)
        $map = [
            'pos' => '🛒',
            'inventory' => '📦',
            'sales' => '💰',
            'purchases' => '🚚',
            'crm' => '👥',
            'accounting' => '🧮',
            'hr' => '👤',
            'rental' => '🏠',
            'projects' => '📁',
            'manufacturing' => '⚙️',
            'vehicles' => '🚛',
            'general' => '📦',
            'spare_parts' => '⚙️',
            'wood' => '🪵',
            'warehouse' => '🏬',
            'fixed_assets' => '🏢',
            'helpdesk' => '🎫',
            'documents' => '📄',
            'reports' => '📊',
            'settings' => '⚙️',
        ];

        foreach ($map as $moduleKey => $icon) {
            DB::table('modules')
                ->where('module_key', $moduleKey)
                ->update(['icon' => $icon]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: We intentionally do not revert icons.
    }
};
