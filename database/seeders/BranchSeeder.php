<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * BranchSeeder - Seeds a default branch for the ERP system
 */
class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branch = Branch::updateOrCreate(
            ['code' => 'HQ'],
            [
                'name' => 'Main Branch',
                'name_ar' => 'الفرع الرئيسي',
                'code' => 'HQ',
                'address' => '123 Business District, Cairo, Egypt',
                'phone' => '+20 2 1234 5678',
                'email' => 'info@ghanem-erp.com',
                'is_active' => true,
                'is_main' => true,
                'timezone' => 'Africa/Cairo',
                'currency' => 'EGP',
                'settings' => [
                    'working_hours' => [
                        'start' => '09:00',
                        'end' => '18:00',
                    ],
                    'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                    'fiscal_year_start' => '01-01',
                    'default_tax_rate' => 14,
                ],
            ]
        );

        // Create a secondary branch for testing multi-branch functionality
        Branch::updateOrCreate(
            ['code' => 'BR1'],
            [
                'name' => 'Downtown Branch',
                'name_ar' => 'فرع وسط البلد',
                'code' => 'BR1',
                'address' => '45 Downtown Street, Cairo, Egypt',
                'phone' => '+20 2 9876 5432',
                'email' => 'downtown@ghanem-erp.com',
                'is_active' => true,
                'is_main' => false,
                'timezone' => 'Africa/Cairo',
                'currency' => 'EGP',
                'parent_id' => $branch->id,
                'settings' => [
                    'working_hours' => [
                        'start' => '10:00',
                        'end' => '22:00',
                    ],
                    'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                    'fiscal_year_start' => '01-01',
                    'default_tax_rate' => 14,
                ],
            ]
        );
    }
}
