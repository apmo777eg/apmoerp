<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * PermissionsSeeder - Seeds all application permissions
 *
 * Permissions follow the pattern: module.entity.action or module.action
 * This comprehensive list is derived from code analysis of:
 * - Route middleware definitions
 * - Controller/Livewire component can() checks
 * - Policy definitions
 */
class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache before seeding
        app()['cache']->forget('spatie.permission.cache');

        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Clear cache after seeding
        app()['cache']->forget('spatie.permission.cache');
    }

    /**
     * Get all application permissions organized by module
     */
    protected function getPermissions(): array
    {
        return array_merge(
            $this->getSystemPermissions(),
            $this->getDashboardPermissions(),
            $this->getUserPermissions(),
            $this->getRolePermissions(),
            $this->getBranchPermissions(),
            $this->getSettingsPermissions(),
            $this->getPosPermissions(),
            $this->getInventoryPermissions(),
            $this->getWarehousePermissions(),
            $this->getSalesPermissions(),
            $this->getPurchasesPermissions(),
            $this->getCustomerPermissions(),
            $this->getSupplierPermissions(),
            $this->getAccountingPermissions(),
            $this->getBankingPermissions(),
            $this->getExpenseIncomePermissions(),
            $this->getFixedAssetPermissions(),
            $this->getHrmPermissions(),
            $this->getProjectPermissions(),
            $this->getRentalPermissions(),
            $this->getMotorcyclePermissions(),
            $this->getManufacturingPermissions(),
            $this->getDocumentPermissions(),
            $this->getHelpdeskPermissions(),
            $this->getReportPermissions(),
            $this->getLogPermissions(),
            $this->getMediaPermissions(),
            $this->getModulePermissions(),
            $this->getStorePermissions(),
            $this->getWoodPermissions(),
            $this->getSparesPermissions(),
            $this->getApiPermissions(),
        );
    }

    protected function getSystemPermissions(): array
    {
        return [
            'system.view-notifications',
            'system.backup.manage',
            'system.impersonate',
            'impersonate.users',
            'access-all-branches',
            'import.manage',
            'audit.view',
            'files.view',
            'files.delete',
            'permissions.view',
            'permissions.manage',
        ];
    }

    protected function getDashboardPermissions(): array
    {
        return [
            'dashboard.view',
        ];
    }

    protected function getUserPermissions(): array
    {
        return [
            'users.view',
            'users.manage',
            'users.create',
            'users.edit',
            'users.update',
        ];
    }

    protected function getRolePermissions(): array
    {
        return [
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.manage',
        ];
    }

    protected function getBranchPermissions(): array
    {
        return [
            'branches.view',
            'branches.view-all',
            'branches.create',
            'branches.update',
            'branches.manage',
            'branches.settings',
            'branches.modules.view',
            'branches.modules.manage',
            'branch.employees.manage',
            'branch.reports.view',
            'branch.settings.manage',
        ];
    }

    protected function getSettingsPermissions(): array
    {
        return [
            'settings.view',
            'settings.manage',
            'settings.update',
            'settings.branch',
            'settings.currency.manage',
            'settings.translations.view',
            'settings.translations.manage',
        ];
    }

    protected function getPosPermissions(): array
    {
        return [
            'pos.use',
            'pos.offline.report.view',
            'pos.daily-report.view',
            'pos.session.manage',
        ];
    }

    protected function getInventoryPermissions(): array
    {
        return [
            'inventory.view',
            'inventory.manage',
            'inventory.products.view',
            'inventory.products.create',
            'inventory.products.update',
            'inventory.products.manage',
            'inventory.categories.view',
            'inventory.categories.manage',
            'inventory.categories.edit',
            'inventory.categories.delete',
            'inventory.units.view',
            'inventory.units.manage',
            'inventory.units.delete',
            'inventory.stock.alerts.view',
            'products.view-cost',
            'products.create',
            'products.update',
            'products.manage',
            'products.delete',
            'products.import',
            'products.image.upload',
            'stock.adjust',
            'stock.transfer',
        ];
    }

    protected function getWarehousePermissions(): array
    {
        return [
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.manage',
            'warehouse.view',
            'warehouse.manage',
        ];
    }

    protected function getSalesPermissions(): array
    {
        return [
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.void',
            'sales.return',
            'sales.manage',
            'sales.export',
            'sales.import',
            'sales.installments.view',
            'sales.orders.create',
        ];
    }

    protected function getPurchasesPermissions(): array
    {
        return [
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
            'grn.create',
            'grn.update',
            'grn.delete',
            'grn.approve',
            'grn.reject',
            'grn.inspect',
        ];
    }

    protected function getCustomerPermissions(): array
    {
        return [
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.manage',
            'customers.manage.all',
            'customers.view-financial',
            'customers.view-sales',
            'customers.loyalty.manage',
        ];
    }

    protected function getSupplierPermissions(): array
    {
        return [
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.manage',
            'suppliers.view-financial',
            'suppliers.view-purchases',
        ];
    }

    protected function getAccountingPermissions(): array
    {
        return [
            'accounting.view',
            'accounting.create',
            'accounting.update',
        ];
    }

    protected function getBankingPermissions(): array
    {
        return [
            'banking.create',
            'banking.edit',
            'banking.view',
            'banking.reconcile',
        ];
    }

    protected function getExpenseIncomePermissions(): array
    {
        return [
            'expenses.manage',
            'expenses.view',
            'expenses.create',
            'expenses.edit',
            'income.manage',
            'income.view',
            'income.create',
            'income.edit',
            'income.delete',
            'incomes.manage', // Alias for backward compatibility
        ];
    }

    protected function getFixedAssetPermissions(): array
    {
        return [
            'fixed-assets.view',
            'fixed-assets.create',
            'fixed-assets.edit',
            'fixed-assets.manage',
        ];
    }

    protected function getHrmPermissions(): array
    {
        return [
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
            'hr.manage-employees',
            'hr.view-reports',
            'employee.self.attendance',
            'employee.self.leave-request',
            'employee.self.payslip-view',
        ];
    }

    protected function getProjectPermissions(): array
    {
        return [
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'projects.manage',
            'projects.tasks.view',
            'projects.tasks.manage',
            'projects.expenses.view',
            'projects.expenses.manage',
            'projects.expenses.approve',
            'projects.timelogs.view',
            'projects.timelogs.manage',
        ];
    }

    protected function getRentalPermissions(): array
    {
        return [
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
            'rental.contracts.manage-all',
            'rental.contracts.renew',
            'rental.contracts.terminate',
            'rental.invoices.collect',
            'rental.invoices.penalty',
            'rental.view-reports',
        ];
    }

    protected function getMotorcyclePermissions(): array
    {
        return [
            'motorcycle.vehicles.create',
            'motorcycle.vehicles.update',
            'motorcycle.warranties.create',
            'motorcycle.warranties.update',
        ];
    }

    protected function getManufacturingPermissions(): array
    {
        return [
            'manufacturing.view',
            'manufacturing.manage',
            'manufacturing.create',
            'manufacturing.update',
            'manufacturing.edit',
            'manufacturing.boms.manage',
            'manufacturing.orders.manage',
            'manufacturing.work_centers.manage',
        ];
    }

    protected function getDocumentPermissions(): array
    {
        return [
            'documents.view',
            'documents.create',
            'documents.edit',
            'documents.delete',
            'documents.manage',
            'documents.share',
            'documents.download',
            'documents.tags.create',
            'documents.tags.manage',
            'documents.versions.manage',
        ];
    }

    protected function getHelpdeskPermissions(): array
    {
        return [
            'helpdesk.view',
            'helpdesk.create',
            'helpdesk.edit',
            'helpdesk.delete',
            'helpdesk.manage',
            'helpdesk.reply',
            'helpdesk.assign',
            'helpdesk.close',
        ];
    }

    protected function getReportPermissions(): array
    {
        return [
            'reports.view',
            'reports.manage',
            'reports.export',
            'reports.aggregate',
            'reports.sales.view',
            'reports.inventory.charts',
            'reports.inventory.export',
            'reports.pos.charts',
            'reports.pos.export',
            'reports.templates.manage',
            'reports.scheduled.manage',
            // Aliases used by routes/sidebar (for compatibility)
            'reports.schedule',
            'reports.templates',
            // View-reports permissions used by sidebar
            'sales.view-reports',
            'inventory.view-reports',
            'pos.view-reports',
            'hrm.view-reports',
            'rental.view-reports',
        ];
    }

    protected function getLogPermissions(): array
    {
        return [
            'logs.audit.view',
            'logs.activity.view',
            'logs.login.view',
        ];
    }

    protected function getMediaPermissions(): array
    {
        return [
            'media.view',
            'media.view-others',
            'media.upload',
            'media.manage',
            'media.manage-all',
            'media.delete',
        ];
    }

    protected function getModulePermissions(): array
    {
        return [
            'modules.view',
            'modules.manage',
            'modules.fields.view',
            'modules.fields.manage',
        ];
    }

    protected function getStorePermissions(): array
    {
        return [
            'stores.view',
            'stores.edit',
            'stores.delete',
            'stores.sync',
            'stores.manage',
            'store.api.products',
            'store.api.orders',
            'store.reports.dashboard',
        ];
    }

    protected function getWoodPermissions(): array
    {
        return [
            'wood.conversions.create',
            'wood.conversions.update',
            'wood.waste.create',
        ];
    }

    protected function getSparesPermissions(): array
    {
        return [
            'spares.compatibility.update',
            'spares.compatibility.manage',
        ];
    }

    protected function getApiPermissions(): array
    {
        return [
            'api.docs.view',
        ];
    }
}
