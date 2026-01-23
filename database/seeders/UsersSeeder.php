<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * UsersSeeder - Seeds default users for the ERP system
 */
class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'HQ')->first();
        $downtownBranch = Branch::where('code', 'BR1')->first();

        // Super Admin - Full system access
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@ghanem-lvju-egypt.com'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@ghanem-lvju-egypt.com',
                'password' => Hash::make('0150386787'),
                'phone' => '+20 100 000 0001',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'en',
            ]
        );
        $superAdmin->assignRole('Super Admin');
        if ($mainBranch) {
            $superAdmin->branches()->syncWithoutDetaching([$mainBranch->id]);
            if ($downtownBranch) {
                $superAdmin->branches()->syncWithoutDetaching([$downtownBranch->id]);
            }
        }

        // Branch Admin
        $branchAdmin = User::updateOrCreate(
            ['email' => 'branch.admin@ghanem-erp.com'],
            [
                'name' => 'Branch Administrator',
                'email' => 'branch.admin@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0002',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $branchAdmin->assignRole('Admin');
        if ($mainBranch) {
            $branchAdmin->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@ghanem-erp.com'],
            [
                'name' => 'Ahmed Hassan',
                'email' => 'manager@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0003',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $manager->assignRole('Manager');
        if ($mainBranch) {
            $manager->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Accountant
        $accountant = User::updateOrCreate(
            ['email' => 'accountant@ghanem-erp.com'],
            [
                'name' => 'Sarah Mohamed',
                'email' => 'accountant@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0004',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $accountant->assignRole('Accountant');
        if ($mainBranch) {
            $accountant->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // HR Manager
        $hrManager = User::updateOrCreate(
            ['email' => 'hr@ghanem-erp.com'],
            [
                'name' => 'Fatima Ali',
                'email' => 'hr@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0005',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $hrManager->assignRole('HR Manager');
        if ($mainBranch) {
            $hrManager->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Sales Manager
        $salesManager = User::updateOrCreate(
            ['email' => 'sales.manager@ghanem-erp.com'],
            [
                'name' => 'Omar Khaled',
                'email' => 'sales.manager@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0006',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $salesManager->assignRole('Sales Manager');
        if ($mainBranch) {
            $salesManager->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Salesperson
        $salesperson = User::updateOrCreate(
            ['email' => 'salesperson@ghanem-erp.com'],
            [
                'name' => 'Mohamed Ibrahim',
                'email' => 'salesperson@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0007',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $salesperson->assignRole('Salesperson');
        if ($mainBranch) {
            $salesperson->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Warehouse Manager
        $warehouseManager = User::updateOrCreate(
            ['email' => 'warehouse.manager@ghanem-erp.com'],
            [
                'name' => 'Khaled Mahmoud',
                'email' => 'warehouse.manager@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0008',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $warehouseManager->assignRole('Warehouse Manager');
        if ($mainBranch) {
            $warehouseManager->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Warehouse Staff
        $warehouseStaff = User::updateOrCreate(
            ['email' => 'warehouse.staff@ghanem-erp.com'],
            [
                'name' => 'Ali Saeed',
                'email' => 'warehouse.staff@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0009',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $warehouseStaff->assignRole('Warehouse Staff');
        if ($mainBranch) {
            $warehouseStaff->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Cashier
        $cashier = User::updateOrCreate(
            ['email' => 'cashier@ghanem-erp.com'],
            [
                'name' => 'Nour Ahmed',
                'email' => 'cashier@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0010',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $cashier->assignRole('Cashier');
        if ($mainBranch) {
            $cashier->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Employee
        $employee = User::updateOrCreate(
            ['email' => 'employee@ghanem-erp.com'],
            [
                'name' => 'Hassan Youssef',
                'email' => 'employee@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0011',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $employee->assignRole('Employee');
        if ($mainBranch) {
            $employee->branches()->syncWithoutDetaching([$mainBranch->id]);
        }

        // Downtown Branch Staff
        $downtownCashier = User::updateOrCreate(
            ['email' => 'downtown.cashier@ghanem-erp.com'],
            [
                'name' => 'Mona Fathy',
                'email' => 'downtown.cashier@ghanem-erp.com',
                'password' => Hash::make('password'),
                'phone' => '+20 100 000 0012',
                'branch_id' => $downtownBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ]
        );
        $downtownCashier->assignRole('Cashier');
        if ($downtownBranch) {
            $downtownCashier->branches()->syncWithoutDetaching([$downtownBranch->id]);
        }
    }
}
