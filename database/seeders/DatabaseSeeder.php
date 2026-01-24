<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in order of dependency
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            BranchSeeder::class,         // Branches must exist before modules link to them
            ModulesSeeder::class,         // This now also links core modules to branches
            UsersSeeder::class,
            CurrenciesSeeder::class,
            TaxesSeeder::class,
            UnitsOfMeasureSeeder::class,
            DepartmentsSeeder::class,
            WarehousesSeeder::class,
            ProductCategoriesSeeder::class,
            SystemSettingsSeeder::class,
        ]);
    }
}
