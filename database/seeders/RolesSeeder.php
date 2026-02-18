<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RolesSeeder - Seeds default roles with their permissions
 *
 * Roles hierarchy:
 * - Super Admin: Full system access
 * - Admin: Branch-level administration
 * - Manager: Department/team management
 * - Accountant: Financial operations
 * - HR Manager: Human resources operations
 * - Sales Manager: Sales team management
 * - Salesperson: Point of sale and basic sales
 * - Warehouse Manager: Inventory management
 * - Warehouse Staff: Stock operations
 * - Cashier: POS-only access
 * - Employee: Basic self-service access
 */
class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache before seeding
        app()['cache']->forget('spatie.permission.cache');

        $roles = $this->getRolesWithPermissions();

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            // Sync permissions
            $validPermissions = Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('name')
                ->toArray();

            $role->syncPermissions($validPermissions);
        }

        // Clear cache after seeding
        app()['cache']->forget('spatie.permission.cache');
    }

    /**
     * Get all roles with their assigned permissions
     */
    protected function getRolesWithPermissions(): array
    {
        return [
            'Super Admin' => $this->getSuperAdminPermissions(),
            'Admin' => $this->getAdminPermissions(),
            'Manager' => $this->getManagerPermissions(),
            'Accountant' => $this->getAccountantPermissions(),
            'HR Manager' => $this->getHRManagerPermissions(),
            'Sales Manager' => $this->getSalesManagerPermissions(),
            'Salesperson' => $this->getSalespersonPermissions(),
            'Warehouse Manager' => $this->getWarehouseManagerPermissions(),
            'Warehouse Staff' => $this->getWarehouseStaffPermissions(),
            'Cashier' => $this->getCashierPermissions(),
            'Employee' => $this->getEmployeePermissions(),
            'Viewer' => $this->getViewerPermissions(),
        ];
    }

    /**
     * Super Admin - Full system access
     */
    protected function getSuperAdminPermissions(): array
    {
        return Permission::where('guard_name', 'web')->pluck('name')->toArray();
    }

    /**
     * Admin - Branch-level administration (most permissions except system-critical)
     */
    protected function getAdminPermissions(): array
    {
        return [
            // Dashboard
            'dashboard.view',
            // Users
            'users.manage',
            'users.create',
            'users.edit',
            // Roles
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.manage',
            // Branches
            'branches.view',
            'branches.settings',
            'branch.employees.manage',
            'branch.reports.view',
            'branch.settings.manage',
            // Warehouses
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.manage',
            // Settings
            'settings.view',
            'settings.manage',
            'settings.update',
            'settings.branch',
            'settings.currency.manage',
            // POS
            'pos.use',
            'pos.offline.report.view',
            // Inventory
            'inventory.view',
            'inventory.products.view',
            'inventory.products.manage',
            'inventory.categories.view',
            'inventory.categories.manage',
            'inventory.units.view',
            'inventory.units.manage',
            'inventory.stock.alerts.view',
            'products.view-cost',
            'products.create',
            'products.update',
            'products.import',
            'products.image.upload',
            'stock.adjust',
            'stock.transfer',
            // Sales
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.void',
            'sales.return',
            'sales.manage',
            'sales.export',
            'sales.import',
            'sales.installments.view',
            // Purchases
            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.manage',
            'purchases.approve',
            'purchases.cancel',
            'purchases.pay',
            'purchases.receive',
            'purchases.return',
            'purchases.import',
            'purchases.export',
            'purchases.requisitions.view',
            'purchases.requisitions.create',
            'purchases.requisitions.approve',
            'purchases.requisitions.manage',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.manage',
            'customers.manage.all',
            'customers.view-financial',
            'customers.view-sales',
            'customers.loyalty.manage',
            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.manage',
            'suppliers.view-financial',
            'suppliers.view-purchases',
            // Banking
            'banking.view',
            'banking.create',
            'banking.edit',
            'banking.reconcile',
            // Accounting
            'accounting.view',
            'accounting.create',
            'accounting.update',
            // Expenses/Income
            'expenses.view',
            'expenses.manage',
            'expenses.create',
            'expenses.edit',
            'income.view',
            'income.manage',
            'income.create',
            'income.edit',
            // Fixed Assets
            'fixed-assets.view',
            'fixed-assets.create',
            'fixed-assets.edit',
            'fixed-assets.manage',
            // HRM
            'hrm.view',
            'hrm.employees.view',
            'hrm.employees.create',
            'hrm.employees.edit',
            'hrm.employees.assign',
            'hrm.attendance.view',
            'hrm.attendance.create',
            'hrm.attendance.manage',
            'hrm.payroll.view',
            'hrm.payroll.run',
            'hrm.payroll.manage',
            'hrm.shifts.view',
            'hrm.shifts.manage',
            'hrm.manage',
            'hrm.view-reports',
            'hr.manage-employees',
            'hr.view-reports',
            // Projects
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.manage',
            'projects.tasks.manage',
            'projects.expenses.manage',
            'projects.timelogs.manage',
            // Rental
            'rental.view',
            'rental.units.view',
            'rental.units.create',
            'rental.units.update',
            'rental.units.manage',
            'rental.units.status',
            'rental.properties.view',
            'rental.properties.create',
            'rental.properties.update',
            'rental.tenants.view',
            'rental.tenants.create',
            'rental.tenants.update',
            'rental.tenants.archive',
            'rental.contracts.view',
            'rental.contracts.create',
            'rental.contracts.update',
            'rental.contracts.manage',
            'rental.contracts.renew',
            'rental.contracts.terminate',
            'rental.invoices.collect',
            'rental.invoices.penalty',
            'rental.view-reports',
            // Documents
            'documents.view',
            'documents.create',
            'documents.edit',
            'documents.manage',
            'documents.share',
            'documents.download',
            'documents.tags.create',
            'documents.tags.manage',
            // Helpdesk
            'helpdesk.view',
            'helpdesk.create',
            'helpdesk.edit',
            'helpdesk.manage',
            'helpdesk.reply',
            // Reports
            'reports.view',
            'reports.manage',
            'reports.sales.view',
            'reports.inventory.charts',
            'reports.inventory.export',
            'reports.pos.charts',
            'reports.pos.export',
            'reports.templates.manage',
            'reports.scheduled.manage',
            // Logs
            'logs.audit.view',
            'logs.activity.view',
            'logs.login.view',
            // Media
            'media.view',
            'media.view-others',
            'media.upload',
            'media.manage',
            'media.manage-all',
            'media.delete',
            // Modules
            'modules.manage',
            // Stores
            'stores.view',
            'store.api.products',
            'store.api.orders',
            'store.reports.dashboard',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Manager - Department/team management
     */
    protected function getManagerPermissions(): array
    {
        return [
            'dashboard.view',
            // Inventory
            'inventory.view',
            'inventory.products.view',
            'inventory.products.manage',
            'inventory.categories.view',
            'inventory.stock.alerts.view',
            'products.view-cost',
            'products.create',
            'products.update',
            'stock.adjust',
            'stock.transfer',
            // Sales
            'sales.view',
            'sales.update',
            'sales.return',
            'sales.installments.view',
            // Purchases
            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.approve',
            'purchases.receive',
            // Customers
            'purchases.requisitions.view',
            'purchases.requisitions.create',
            'purchases.requisitions.approve',
            'purchases.requisitions.manage',

            'customers.view',
            'customers.create',
            'customers.update',
            'customers.manage',
            'customers.view-sales',
            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.view-purchases',
            // HRM
            'hrm.view',
            'hrm.employees.view',
            'hrm.attendance.view',
            'hrm.attendance.create',
            // Projects
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.tasks.manage',
            'projects.timelogs.manage',
            // Documents
            'documents.view',
            'documents.create',
            'documents.edit',
            'documents.share',
            'documents.download',
            // Helpdesk
            'helpdesk.view',
            'helpdesk.create',
            'helpdesk.edit',
            'helpdesk.reply',
            // Reports
            'reports.view',
            'reports.sales.view',
            'reports.inventory.charts',
            // Media
            'media.view',
            'media.upload',
            'media.manage',
            // System
            'system.view-notifications',
            // POS
            'pos.use',
        ];
    }

    /**
     * Accountant - Financial operations
     */
    protected function getAccountantPermissions(): array
    {
        return [
            'dashboard.view',
            // Accounting
            'accounting.view',
            'accounting.create',
            'accounting.update',
            // Banking
            'banking.view',
            'banking.create',
            'banking.edit',
            'banking.reconcile',
            // Expenses/Income
            'expenses.view',
            'expenses.manage',
            'expenses.create',
            'expenses.edit',
            'income.view',
            'income.manage',
            'income.create',
            'income.edit',
            // Fixed Assets
            'fixed-assets.view',
            'fixed-assets.create',
            'fixed-assets.edit',
            // Sales (view only for reconciliation)
            'sales.view',
            'sales.installments.view',
            // Purchases
            'purchases.view',
            'purchases.pay',
            // Customers (financial view)
            'customers.view',
            'customers.view-financial',
            // Suppliers (financial view)
            'suppliers.view',
            'suppliers.view-financial',
            // HRM (payroll)
            'hrm.view',
            'hrm.payroll.view',
            'hrm.payroll.run',
            'hrm.payroll.manage',
            // Reports
            'reports.view',
            'reports.sales.view',
            // Documents
            'documents.view',
            'documents.create',
            'documents.download',
            // Media
            'media.view',
            'media.upload',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * HR Manager - Human resources operations
     */
    protected function getHRManagerPermissions(): array
    {
        return [
            'dashboard.view',
            // HRM (full access)
            'hrm.view',
            'hrm.employees.view',
            'hrm.employees.create',
            'hrm.employees.edit',
            'hrm.employees.assign',
            'hrm.attendance.view',
            'hrm.attendance.create',
            'hrm.attendance.manage',
            'hrm.payroll.view',
            'hrm.payroll.run',
            'hrm.payroll.manage',
            'hrm.shifts.view',
            'hrm.shifts.manage',
            'hrm.manage',
            'hrm.view-reports',
            'hr.manage-employees',
            'hr.view-reports',
            // Branch employees
            'branch.employees.manage',
            // Documents
            'documents.view',
            'documents.create',
            'documents.edit',
            'documents.share',
            'documents.download',
            // Reports
            'reports.view',
            // Media
            'media.view',
            'media.upload',
            'media.manage',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Sales Manager - Sales team management
     */
    protected function getSalesManagerPermissions(): array
    {
        return [
            'dashboard.view',
            // POS
            'pos.use',
            'pos.offline.report.view',
            // Sales
            'sales.view',
            'sales.update',
            'sales.void',
            'sales.return',
            'sales.installments.view',
            // Inventory (view)
            'inventory.view',
            'inventory.products.view',
            'products.view-cost',
            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.manage',
            'customers.view-sales',
            'customers.loyalty.manage',
            // Reports
            'reports.view',
            'reports.sales.view',
            'reports.pos.charts',
            'reports.pos.export',
            // Documents
            'documents.view',
            'documents.create',
            'documents.download',
            // Helpdesk
            'helpdesk.view',
            'helpdesk.create',
            'helpdesk.reply',
            // Media
            'media.view',
            'media.upload',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Salesperson - Point of sale and basic sales operations
     */
    protected function getSalespersonPermissions(): array
    {
        return [
            'dashboard.view',
            // POS
            'pos.use',
            // Sales (limited)
            'sales.view',
            'sales.return',
            // Inventory (view only)
            'inventory.view',
            'inventory.products.view',
            // Customers (limited)
            'customers.view',
            'customers.create',
            // Helpdesk
            'helpdesk.view',
            'helpdesk.create',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Warehouse Manager - Inventory management
     */
    protected function getWarehouseManagerPermissions(): array
    {
        return [
            'dashboard.view',
            // Warehouses (full)
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.manage',
            // Inventory (full)
            'inventory.view',
            'inventory.products.view',
            'inventory.products.manage',
            'inventory.categories.view',
            'inventory.categories.manage',
            'inventory.units.view',
            'inventory.stock.alerts.view',
            'products.view-cost',
            'products.create',
            'products.update',
            'products.import',
            'products.image.upload',
            'stock.adjust',
            'stock.transfer',
            // Purchases (receiving)
            'purchases.view',
            'purchases.receive',
            'purchases.requisitions.view',

            // Suppliers (view)
            'suppliers.view',
            // Reports
            'reports.view',
            'reports.inventory.charts',
            'reports.inventory.export',
            // Documents
            'documents.view',
            'documents.create',
            'documents.download',
            // Media
            'media.view',
            'media.upload',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Warehouse Staff - Basic stock operations
     */
    protected function getWarehouseStaffPermissions(): array
    {
        return [
            'dashboard.view',
            // Inventory (limited)
            'inventory.view',
            'inventory.products.view',
            'inventory.stock.alerts.view',
            'stock.adjust',
            'stock.transfer',
            // Purchases (receiving only)
            'purchases.view',
            'purchases.receive',
            'purchases.requisitions.view',

            // Media
            'media.view',
            'media.upload',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Cashier - POS-only access
     */
    protected function getCashierPermissions(): array
    {
        return [
            'dashboard.view',
            // POS
            'pos.use',
            // Sales (limited)
            'sales.view',
            // Inventory (view only)
            'inventory.products.view',
            // Customers (limited)
            'customers.view',
            'customers.create',
            // System
            'system.view-notifications',
        ];
    }

    /**
     * Employee - Basic self-service access
     */
    protected function getEmployeePermissions(): array
    {
        return [
            'dashboard.view',
            // Self-service
            'employee.self.attendance',
            'employee.self.leave-request',
            'employee.self.payslip-view',
            // Helpdesk
            'helpdesk.view',
            'helpdesk.create',
            // Documents (personal)
            'documents.view',
            'documents.download',
            // Purchases Requisitions
            'purchases.requisitions.view',
            'purchases.requisitions.create',

            // System
            'system.view-notifications',
        ];
    }

    /**
     * Viewer - Read-only access to main modules
     */
    protected function getViewerPermissions(): array
    {
        return [
            'dashboard.view',
            // View-only permissions
            'inventory.view',
            'inventory.products.view',
            'inventory.categories.view',
            'sales.view',
            'purchases.view',
            'customers.view',
            'suppliers.view',
            'reports.view',
            'documents.view',
            'documents.download',
            'system.view-notifications',
        ];
    }
}
