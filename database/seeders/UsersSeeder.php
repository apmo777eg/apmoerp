<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * UsersSeeder - Seeds default users for the ERP system
 */
class UsersSeeder extends Seeder
{
    /**
     * Create/update a user in an idempotent and *safe* way.
     *
     * Key improvement: we do NOT silently reset passwords on re-seed.
     * You can force reset via SEED_FORCE_PASSWORD_RESET=true.
     */
    protected function seedUser(array $data, ?string $role = null, array $branchIds = []): User
    {
        $email = $data['email'] ?? null;
        if (! $email) {
            throw new \InvalidArgumentException('UsersSeeder: email is required');
        }

        $plainPassword = $data['password'] ?? env('SEED_DEFAULT_PASSWORD', 'password');
        unset($data['password']);

        $user = User::firstOrNew(['email' => $email]);

        // Only set password on create (unless explicitly forced)
        if (! $user->exists) {
            $user->password = Hash::make($plainPassword);
            // Default to verified in demo setups
            $user->email_verified_at = $data['email_verified_at'] ?? now();
        } elseif (filter_var(env('SEED_FORCE_PASSWORD_RESET', false), FILTER_VALIDATE_BOOL)) {
            $user->password = Hash::make($plainPassword);
        }

        // Avoid accidentally changing immutable keys
        unset($data['email']);

        $user->fill($data);
        $user->save();

        if ($role) {
            try {
                // Prefer syncRoles to avoid accumulating roles across re-seeds.
                $user->syncRoles([$role]);
            } catch (\Throwable $e) {
                // In case permissions package isn't ready yet in the seeding order.
                Log::warning('UsersSeeder role assignment failed', ['email' => $email, 'role' => $role, 'error' => $e->getMessage()]);
            }
        }

        // Ensure the user can access their branches in the UI (branch switcher uses branch_user pivot)
        $pivot = [];
        foreach ($branchIds as $branchId) {
            if ($branchId) {
                $pivot[(int) $branchId] = ['is_active' => true, 'activated_at' => now()];
            }
        }
        if (! empty($pivot)) {
            try {
                $user->branches()->syncWithoutDetaching($pivot);
            } catch (\Throwable $e) {
                Log::warning('UsersSeeder branch pivot attach failed', ['email' => $email, 'branches' => array_keys($pivot), 'error' => $e->getMessage()]);
            }
        }

        return $user;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'HQ')->first();
        $downtownBranch = Branch::where('code', 'BR1')->first();

        $defaultPassword = env('SEED_DEFAULT_PASSWORD', 'password');
        $superAdminEmail = env('SEED_SUPER_ADMIN_EMAIL', 'admin@ghanem-lvju-egypt.com');
        $superAdminPassword = env('SEED_SUPER_ADMIN_PASSWORD', '0150386787');

        // Super Admin - Full system access
        $superAdmin = $this->seedUser(
            [
                'name' => env('SEED_SUPER_ADMIN_NAME', 'System Administrator'),
                'email' => $superAdminEmail,
                'password' => $superAdminPassword,
                'phone' => env('SEED_SUPER_ADMIN_PHONE', '+20 100 000 0001'),
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => env('SEED_SUPER_ADMIN_LOCALE', 'en'),
            ],
            'Super Admin',
            array_values(array_filter([$mainBranch?->id, $downtownBranch?->id]))
        );

        // Branch Admin
        $this->seedUser(
            [
                'name' => 'Branch Administrator',
                'email' => 'branch.admin@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0002',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Admin',
            [$mainBranch?->id]
        );

        // Manager
        $this->seedUser(
            [
                'name' => 'Ahmed Hassan',
                'email' => 'manager@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0003',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Manager',
            [$mainBranch?->id]
        );

        // Accountant
        $this->seedUser(
            [
                'name' => 'Sarah Mohamed',
                'email' => 'accountant@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0004',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Accountant',
            [$mainBranch?->id]
        );

        // HR Manager
        $this->seedUser(
            [
                'name' => 'Fatima Ali',
                'email' => 'hr@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0005',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'HR Manager',
            [$mainBranch?->id]
        );

        // Sales Manager
        $this->seedUser(
            [
                'name' => 'Omar Khaled',
                'email' => 'sales.manager@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0006',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Sales Manager',
            [$mainBranch?->id]
        );

        // Salesperson
        $this->seedUser(
            [
                'name' => 'Mohamed Ibrahim',
                'email' => 'salesperson@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0007',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Salesperson',
            [$mainBranch?->id]
        );

        // Warehouse Manager
        $this->seedUser(
            [
                'name' => 'Khaled Mahmoud',
                'email' => 'warehouse.manager@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0008',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Warehouse Manager',
            [$mainBranch?->id]
        );

        // Warehouse Staff
        $this->seedUser(
            [
                'name' => 'Ali Saeed',
                'email' => 'warehouse.staff@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0009',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Warehouse Staff',
            [$mainBranch?->id]
        );

        // Cashier
        $this->seedUser(
            [
                'name' => 'Nour Ahmed',
                'email' => 'cashier@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0010',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Cashier',
            [$mainBranch?->id]
        );

        // Employee
        $this->seedUser(
            [
                'name' => 'Hassan Youssef',
                'email' => 'employee@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0011',
                'branch_id' => $mainBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Employee',
            [$mainBranch?->id]
        );

        // Downtown Branch Staff
        $this->seedUser(
            [
                'name' => 'Mona Fathy',
                'email' => 'downtown.cashier@ghanem-erp.com',
                'password' => $defaultPassword,
                'phone' => '+20 100 000 0012',
                'branch_id' => $downtownBranch?->id,
                'is_active' => true,
                'locale' => 'ar',
            ],
            'Cashier',
            [$downtownBranch?->id]
        );
    }
}
