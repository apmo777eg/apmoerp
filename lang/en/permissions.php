<?php

/**
 * Permission labels and descriptions for the ERP system.
 *
 * Format: 'permission.slug' => 'Human-readable label'
 *
 * These translations are used in the Roles management UI and permission displays.
 */

return [
    // Dashboard
    'dashboard.view' => 'View Dashboard',

    // POS
    'pos.use' => 'Use POS Terminal',
    'pos.session.manage' => 'Manage POS Sessions',
    'pos.daily-report.view' => 'View POS Daily Report',
    'pos.offline.report.view' => 'View Offline Sales Report',
    'pos.view-reports' => 'View POS Reports',

    // Sales
    'sales.view' => 'View Sales',
    'sales.manage' => 'Manage Sales',
    'sales.return' => 'Process Sales Returns',
    'sales.export' => 'Export Sales Data',
    'sales.import' => 'Import Sales Data',
    'sales.view-reports' => 'View Sales Reports',

    // Purchases
    'purchases.view' => 'View Purchases',
    'purchases.manage' => 'Manage Purchases',
    'purchases.return' => 'Process Purchase Returns',
    'purchases.export' => 'Export Purchase Data',
    'purchases.import' => 'Import Purchase Data',
    'purchases.requisitions.view' => 'View Purchase Requisitions',
    'purchases.requisitions.create' => 'Create Purchase Requisitions',
    'purchases.requisitions.approve' => 'Approve Purchase Requisitions',
    'purchases.quotations.view' => 'View Quotations',
    'purchases.quotations.create' => 'Create Quotations',
    'purchases.quotations.accept' => 'Accept Quotations',
    'purchases.grn.view' => 'View Goods Received Notes',
    'purchases.grn.create' => 'Create Goods Received Notes',
    'purchases.grn.approve' => 'Approve Goods Received Notes',

    // Customers
    'customers.view' => 'View Customers',
    'customers.manage' => 'Manage Customers',
    'customers.manage.all' => 'Manage All Customers',
    'customers.export' => 'Export Customer Data',
    'customers.import' => 'Import Customer Data',
    'customers.loyalty.manage' => 'Manage Customer Loyalty',

    // Suppliers
    'suppliers.view' => 'View Suppliers',
    'suppliers.manage' => 'Manage Suppliers',
    'suppliers.export' => 'Export Supplier Data',
    'suppliers.import' => 'Import Supplier Data',

    // Inventory
    'inventory.view' => 'View Inventory',
    'inventory.manage' => 'Manage Inventory',
    'inventory.products.view' => 'View Products',
    'inventory.products.create' => 'Create Products',
    'inventory.products.update' => 'Update Products',
    'inventory.products.export' => 'Export Products',
    'inventory.products.import' => 'Import Products',
    'inventory.categories.view' => 'View Categories',
    'inventory.categories.manage' => 'Manage Categories',
    'inventory.units.view' => 'View Units of Measure',
    'inventory.units.manage' => 'Manage Units of Measure',
    'inventory.export' => 'Export Inventory Data',
    'inventory.import' => 'Import Inventory Data',
    'inventory.view-reports' => 'View Inventory Reports',
    'inventory.stock.alerts.view' => 'View Stock Alerts',

    // Warehouse
    'warehouse.view' => 'View Warehouses',
    'warehouse.manage' => 'Manage Warehouses',

    // Expenses
    'expenses.view' => 'View Expenses',
    'expenses.manage' => 'Manage Expenses',
    'expenses.export' => 'Export Expenses',
    'expenses.import' => 'Import Expenses',

    // Income
    'income.view' => 'View Income',
    'income.manage' => 'Manage Income',
    'income.export' => 'Export Income',
    'income.import' => 'Import Income',

    // Accounting
    'accounting.view' => 'View Accounting',
    'accounting.manage' => 'Manage Accounting',
    'accounting.create' => 'Create Journal Entries',

    // Banking
    'banking.view' => 'View Banking',
    'banking.create' => 'Create Bank Accounts',
    'banking.edit' => 'Edit Bank Accounts',
    'banking.delete' => 'Delete Bank Accounts',
    'banking.reconcile' => 'Reconcile Bank Accounts',

    // HRM
    'hrm.view' => 'View HR Module',
    'hrm.manage' => 'Manage HR Module',
    'hrm.employees.view' => 'View Employees',
    'hrm.employees.assign' => 'Assign Employees',
    'hrm.employees.export' => 'Export Employee Data',
    'hrm.employees.import' => 'Import Employee Data',
    'hrm.attendance.view' => 'View Attendance',
    'hrm.attendance.manage' => 'Manage Attendance',
    'hrm.payroll.view' => 'View Payroll',
    'hrm.payroll.manage' => 'Manage Payroll',
    'hrm.payroll.run' => 'Run Payroll',
    'hrm.shifts.view' => 'View Shifts',
    'hrm.shifts.manage' => 'Manage Shifts',
    'hrm.view-reports' => 'View HR Reports',
    'hr.view-reports' => 'View HR Reports',
    'hr.manage-employees' => 'Manage Employees',

    // Rental
    'rental.view' => 'View Rental Module',
    'rental.units.view' => 'View Rental Units',
    'rental.units.manage' => 'Manage Rental Units',
    'rental.properties.view' => 'View Properties',
    'rental.properties.create' => 'Create Properties',
    'rental.properties.update' => 'Update Properties',
    'rental.tenants.view' => 'View Tenants',
    'rental.tenants.create' => 'Create Tenants',
    'rental.tenants.update' => 'Update Tenants',
    'rental.contracts.view' => 'View Rental Contracts',
    'rental.contracts.manage' => 'Manage Rental Contracts',
    'rental.view-reports' => 'View Rental Reports',
    'rental.manage-units' => 'Manage Rental Units',
    'rental.manage-tenants' => 'Manage Tenants',
    'rental.manage-contracts' => 'Manage Contracts',
    'rentals.view' => 'View Rentals',

    // Manufacturing
    'manufacturing.view' => 'View Manufacturing',
    'manufacturing.create' => 'Create Manufacturing Records',
    'manufacturing.edit' => 'Edit Manufacturing Records',
    'manufacturing.delete' => 'Delete Manufacturing Records',
    'manufacturing.manage' => 'Manage Manufacturing',
    'manufacturing.approve' => 'Approve Manufacturing Orders',

    // Fixed Assets
    'fixed-assets.view' => 'View Fixed Assets',
    'fixed-assets.create' => 'Create Fixed Assets',
    'fixed-assets.edit' => 'Edit Fixed Assets',
    'fixed-assets.delete' => 'Delete Fixed Assets',
    'fixed-assets.manage' => 'Manage Fixed Assets',
    'fixed-assets.depreciate' => 'Run Depreciation',

    // Projects
    'projects.view' => 'View Projects',
    'projects.create' => 'Create Projects',
    'projects.edit' => 'Edit Projects',
    'projects.delete' => 'Delete Projects',
    'projects.manage' => 'Manage Projects',
    'projects.tasks.view' => 'View Project Tasks',
    'projects.tasks.manage' => 'Manage Project Tasks',
    'projects.milestones.view' => 'View Project Milestones',
    'projects.milestones.manage' => 'Manage Project Milestones',
    'projects.timelogs.view' => 'View Time Logs',
    'projects.timelogs.manage' => 'Manage Time Logs',
    'projects.expenses.view' => 'View Project Expenses',
    'projects.expenses.manage' => 'Manage Project Expenses',
    'projects.expenses.approve' => 'Approve Project Expenses',
    'projects.budget.view' => 'View Project Budget',

    // Documents
    'documents.view' => 'View Documents',
    'documents.create' => 'Create Documents',
    'documents.edit' => 'Edit Documents',
    'documents.delete' => 'Delete Documents',
    'documents.manage' => 'Manage Documents',
    'documents.share' => 'Share Documents',
    'documents.download' => 'Download Documents',
    'documents.versions.view' => 'View Document Versions',
    'documents.versions.manage' => 'Manage Document Versions',
    'documents.tags.manage' => 'Manage Document Tags',
    'documents.activities.view' => 'View Document Activities',

    // Helpdesk
    'helpdesk.view' => 'View Helpdesk',
    'helpdesk.create' => 'Create Tickets',
    'helpdesk.edit' => 'Edit Tickets',
    'helpdesk.delete' => 'Delete Tickets',
    'helpdesk.manage' => 'Manage Helpdesk',
    'helpdesk.assign' => 'Assign Tickets',
    'helpdesk.reply' => 'Reply to Tickets',
    'helpdesk.close' => 'Close Tickets',
    'tickets.view' => 'View Tickets',
    'tickets.create' => 'Create Tickets',
    'tickets.edit' => 'Edit Tickets',
    'tickets.delete' => 'Delete Tickets',
    'tickets.assign' => 'Assign Tickets',
    'tickets.resolve' => 'Resolve Tickets',
    'tickets.close' => 'Close Tickets',
    'tickets.reopen' => 'Reopen Tickets',
    'tickets.reply' => 'Reply to Tickets',
    'tickets.internal-notes' => 'Add Internal Notes',
    'tickets.categories.view' => 'View Ticket Categories',
    'tickets.categories.manage' => 'Manage Ticket Categories',
    'tickets.priorities.view' => 'View Ticket Priorities',
    'tickets.priorities.manage' => 'Manage Ticket Priorities',
    'tickets.sla.view' => 'View SLA Policies',
    'tickets.sla.manage' => 'Manage SLA Policies',

    // Media
    'media.view' => 'View Media Library',
    'media.upload' => 'Upload Media',
    'media.delete' => 'Delete Media',
    'media.manage' => 'Manage Media',
    'media.manage-all' => 'Manage All Media',
    'media.view-others' => 'View Others\' Media',

    // Reports
    'reports.view' => 'View Reports',
    'reports.export' => 'Export Reports',
    'reports.aggregate' => 'View Aggregate Reports',
    'reports.module.view' => 'View Module Reports',
    'reports.manage' => 'Manage Reports',
    'reports.schedule' => 'Schedule Reports',
    'reports.templates' => 'Manage Report Templates',
    'reports.hub.view' => 'View Reports Hub',
    'reports.pos.charts' => 'View POS Charts',
    'reports.inventory.charts' => 'View Inventory Charts',
    'reports.pos.export' => 'Export POS Reports',
    'reports.inventory.export' => 'Export Inventory Reports',
    'reports.scheduled.manage' => 'Manage Scheduled Reports',
    'reports.templates.manage' => 'Manage Report Templates',
    'reports.sales.view' => 'View Sales Reports',

    // Settings
    'settings.view' => 'View Settings',
    'settings.manage' => 'Manage Settings',
    'settings.branch' => 'Manage Branch Settings',
    'settings.translations.manage' => 'Manage Translations',
    'settings.currency.manage' => 'Manage Currencies',

    // Users & Roles
    'users.manage' => 'Manage Users',
    'roles.manage' => 'Manage Roles',
    'impersonate.users' => 'Impersonate Users',

    // Branches
    'branches.view' => 'View Branches',
    'branches.view-all' => 'View All Branches Data',
    'branches.manage' => 'Manage Branches',
    'branches.create' => 'Create Branches',
    'branches.edit' => 'Edit Branches',
    'branch.admin.manage' => 'Manage Branch Admins',
    'branch.users.manage' => 'Manage Branch Users',

    // Modules
    'modules.manage' => 'Manage Modules',

    // Stores
    'stores.view' => 'View Stores',
    'stores.manage' => 'Manage Stores',
    'store.reports.dashboard' => 'View Store Reports Dashboard',

    // Logs
    'logs.audit.view' => 'View Audit Logs',
    'logs.login.view' => 'View Login Logs',

    // System
    'system.view-notifications' => 'View Notifications',

    // Installments
    'sales.installments.view' => 'View Sales Installments',

    // Spares/Vehicle
    'spares.compatibility.manage' => 'Manage Spare Parts Compatibility',
    'spares.vehicle-models.view' => 'View Vehicle Models',

    // Additional permissions (added for completeness)
    'access-all-branches' => 'Access All Branches',
    'api.docs.view' => 'View API Documentation',
    'branch.employees.manage' => 'Manage Branch Employees',
    'branch.reports.view' => 'View Branch Reports',
    'branch.settings.manage' => 'Manage Branch Settings',
    'branches.settings' => 'Branch Settings',
    'branches.update' => 'Update Branches',
    'customers.create' => 'Create Customers',
    'customers.update' => 'Update Customers',
    'customers.view-financial' => 'View Customers Financial',
    'customers.view-sales' => 'View Customers Sales',
    'documents.tags.create' => 'Create Document Tags',
    'employee.self.attendance' => 'Employee Self Attendance',
    'employee.self.leave-request' => 'Employee Leave Request',
    'employee.self.payslip-view' => 'View Own Payslip',
    'hrm.attendance.create' => 'Create Attendance Records',
    'hrm.employees.create' => 'Create Employees',
    'hrm.employees.edit' => 'Edit Employees',
    'inventory.products.manage' => 'Manage Inventory Products',
    'logs.activity.view' => 'View Activity Logs',
    'manufacturing.update' => 'Update Manufacturing',
    'motorcycle.vehicles.create' => 'Create Vehicles',
    'motorcycle.vehicles.update' => 'Update Vehicles',
    'motorcycle.warranties.create' => 'Create Warranties',
    'motorcycle.warranties.update' => 'Update Warranties',
    'products.create' => 'Create Products',
    'products.image.upload' => 'Upload Product Images',
    'products.import' => 'Import Products',
    'products.update' => 'Update Products',
    'products.view-cost' => 'View Product Cost',
    'purchases.approve' => 'Approve Purchases',
    'purchases.cancel' => 'Cancel Purchases',
    'purchases.create' => 'Create Purchases',
    'purchases.pay' => 'Pay Purchases',
    'purchases.receive' => 'Receive Purchases',
    'purchases.update' => 'Update Purchases',
    'rental.contracts.create' => 'Create Rental Contracts',
    'rental.contracts.renew' => 'Renew Rental Contracts',
    'rental.contracts.terminate' => 'Terminate Rental Contracts',
    'rental.contracts.update' => 'Update Rental Contracts',
    'rental.invoices.collect' => 'Collect Rental Invoices',
    'rental.invoices.penalty' => 'Add Rental Invoice Penalties',
    'rental.tenants.archive' => 'Archive Tenants',
    'rental.units.create' => 'Create Rental Units',
    'rental.units.status' => 'Change Rental Unit Status',
    'rental.units.update' => 'Update Rental Units',
    'roles.create' => 'Create Roles',
    'roles.edit' => 'Edit Roles',
    'roles.view' => 'View Roles',
    'settings.translations.view' => 'View Translations',
    'settings.update' => 'Update Settings',
    'spares.compatibility.update' => 'Update Spare Parts Compatibility',
    'stock.adjust' => 'Adjust Stock',
    'stock.transfer' => 'Transfer Stock',
    'store.api.orders' => 'Store API Orders',
    'store.api.products' => 'Store API Products',
    'suppliers.create' => 'Create Suppliers',
    'suppliers.update' => 'Update Suppliers',
    'suppliers.view-financial' => 'View Suppliers Financial',
    'suppliers.view-purchases' => 'View Suppliers Purchases',
    'users.create' => 'Create Users',
    'users.edit' => 'Edit Users',
    'warehouses.create' => 'Create Warehouses',
    'warehouses.manage' => 'Manage Warehouses',
    'warehouses.update' => 'Update Warehouses',
    'warehouses.view' => 'View Warehouses',
    'wood.conversions.create' => 'Create Wood Conversions',
    'wood.conversions.update' => 'Update Wood Conversions',
    'wood.waste.create' => 'Create Wood Waste Records',

    'sales.create' => 'Create Sales',
    'sales.update' => 'Update Sales',
    'sales.void' => 'Void Sales',
    'expenses.create' => 'Create Expenses',
    'expenses.edit' => 'Edit Expenses',
    'income.create' => 'Create Income',
    'income.edit' => 'Edit Income',
    'projects.manage' => 'Manage Projects',
    'hrm.attendance.manage' => 'Manage Attendance',
    'hrm.payroll.manage' => 'Manage Payroll',
    'hrm.shifts.view' => 'View Shifts',
    'hrm.shifts.manage' => 'Manage Shifts',
    'rental.properties.view' => 'View Properties',
    'rental.tenants.view' => 'View Tenants',
    'rental.contracts.manage-all' => 'Manage All Contracts',
];
