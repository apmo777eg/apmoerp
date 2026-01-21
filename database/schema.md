# APMO ERP Database Schema Documentation

This document describes all database tables, columns, constraints, indexes, and their classifications for the APMO ERP system.

## Table Classification Legend

| Classification | Description |
|----------------|-------------|
| **GLOBAL** | System-wide reference data, not branch-scoped |
| **BRANCH-OWNED** | Data belongs to a specific branch |
| **USER-OWNED** | Data belongs to a specific user |
| **PIVOT** | Many-to-many relationship table |

---

## 1. Core Tables

### 1.1 branches
**Classification:** GLOBAL (reference table for branch isolation)

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint unsigned | No | Primary key |
| name | varchar(191) | No | Branch name |
| name_ar | varchar(191) | Yes | Arabic name |
| code | varchar(50) | No | Unique branch code |
| is_active | boolean | No | Active status |
| address | varchar(500) | Yes | Branch address |
| phone | varchar(50) | Yes | Phone number |
| email | varchar(191) | Yes | Email address |
| timezone | varchar(50) | No | Timezone (default: UTC) |
| currency | varchar(10) | No | Default currency |
| is_main | boolean | No | Is main branch flag |
| parent_id | bigint unsigned | Yes | FK to branches |
| settings | json | Yes | Branch settings |
| created_at | timestamp | Yes | Creation timestamp |
| updated_at | timestamp | Yes | Last update timestamp |
| deleted_at | timestamp | Yes | Soft delete timestamp |

**Indexes:** uq_brnch_code (unique), idx_brnch_is_active, idx_brnch_is_main, idx_brnch_parent_id

---

### 1.2 users
**Classification:** BRANCH-OWNED

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint unsigned | No | Primary key |
| name | varchar(191) | No | User full name |
| email | varchar(191) | No | Email (unique) |
| password | varchar(255) | No | Hashed password |
| phone | varchar(50) | Yes | Phone number |
| username | varchar(100) | Yes | Username (unique) |
| locale | varchar(10) | No | Preferred locale |
| timezone | varchar(50) | No | User timezone |
| branch_id | bigint unsigned | Yes | FK to branches |
| avatar | varchar(500) | Yes | Avatar path |
| preferences | json | Yes | User preferences |
| is_active | boolean | No | Active status |
| last_login_at | timestamp | Yes | Last login time |
| last_login_ip | varchar(45) | Yes | Last login IP |
| max_discount_percent | decimal(5,2) | Yes | Max discount allowed |
| daily_discount_limit | decimal(18,4) | Yes | Daily discount limit |
| can_modify_price | boolean | No | Can modify prices |
| email_verified_at | timestamp | Yes | Email verification |
| two_factor_enabled | boolean | No | 2FA enabled |
| created_at | timestamp | Yes | Creation timestamp |
| updated_at | timestamp | Yes | Last update timestamp |
| deleted_at | timestamp | Yes | Soft delete timestamp |

**Indexes:** uq_usr_email, uq_usr_username, idx_usr_branch_id, idx_usr_is_active

---

### 1.3 modules
**Classification:** GLOBAL

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint unsigned | No | Primary key |
| key | varchar(50) | No | Module key (unique) |
| slug | varchar(100) | No | URL slug (unique) |
| name | varchar(191) | No | Module name |
| name_ar | varchar(191) | Yes | Arabic name |
| version | varchar(20) | No | Module version |
| is_core | boolean | No | Is core module |
| is_active | boolean | No | Active status |
| description | text | Yes | Description |
| icon | varchar(50) | Yes | Icon class |
| color | varchar(20) | Yes | Theme color |
| sort_order | smallint unsigned | No | Display order |
| has_inventory | boolean | No | Has inventory feature |
| has_variations | boolean | No | Has product variations |
| default_settings | json | Yes | Default settings |

**Indexes:** uq_mod_key, uq_mod_slug, idx_mod_is_active, idx_mod_is_core

---

## 2. Permissions Tables (Spatie)

### 2.1 permissions
**Classification:** GLOBAL

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| name | varchar(125) | Permission name |
| guard_name | varchar(125) | Guard name |

### 2.2 roles
**Classification:** GLOBAL

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| name | varchar(125) | Role name |
| guard_name | varchar(125) | Guard name |

### 2.3 model_has_permissions, model_has_roles, role_has_permissions
**Classification:** PIVOT

---

## 3. Master Data Tables

### 3.1 currencies
**Classification:** GLOBAL

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| code | varchar(10) | ISO currency code (unique) |
| name | varchar(100) | Currency name |
| symbol | varchar(10) | Currency symbol |
| is_base | boolean | Is base currency |
| is_active | boolean | Active status |
| decimal_places | tinyint unsigned | Decimal places |

### 3.2 currency_rates
**Classification:** GLOBAL

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| from_currency | varchar(10) | Source currency |
| to_currency | varchar(10) | Target currency |
| rate | decimal(18,6) | Exchange rate |
| effective_date | date | Rate effective date |

### 3.3 units_of_measure
**Classification:** GLOBAL

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| name | varchar(100) | Unit name |
| symbol | varchar(20) | Unit symbol |
| type | varchar(50) | Unit type |
| base_unit_id | bigint unsigned | FK to base unit |
| conversion_factor | decimal(18,6) | Conversion factor |
| is_base_unit | boolean | Is base unit |

### 3.4 taxes
**Classification:** BRANCH-OWNED

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| branch_id | bigint unsigned | FK to branches |
| code | varchar(30) | Tax code |
| name | varchar(100) | Tax name |
| rate | decimal(8,4) | Tax rate |
| type | varchar(30) | percentage/fixed |
| is_compound | boolean | Is compound tax |
| is_inclusive | boolean | Is tax inclusive |

---

## 4. Product Tables

### 4.1 product_categories
**Classification:** BRANCH-OWNED

Hierarchical product categorization with parent-child relationships.

### 4.2 products
**Classification:** BRANCH-OWNED

Main product table with extensive fields for:
- Basic info (name, SKU, barcode)
- Pricing (cost, price, MSRP, wholesale)
- Inventory (min_stock, reorder_point, stock_quantity)
- Tracking (is_serialized, is_batch_tracked)
- Physical attributes (length, width, height, weight)
- Service attributes (hourly_rate, service_duration)

**Key Financial Columns (decimal(18,4)):**
- standard_cost, cost, default_price, price
- msrp, wholesale_price
- min_stock, reorder_point, stock_quantity

### 4.3 product_variations
**Classification:** BRANCH-OWNED (via product)

Product variants with SKU, attributes, price, and stock.

### 4.4 product_price_tiers
**Classification:** BRANCH-OWNED

Tiered pricing based on quantity ranges.

---

## 5. Customer & Supplier Tables

### 5.1 customers
**Classification:** BRANCH-OWNED

| Key Financial Columns | Type |
|-----------------------|------|
| credit_limit | decimal(18,4) |
| balance | decimal(18,4) |
| discount_percent | decimal(5,2) |
| loyalty_points | int unsigned |

### 5.2 suppliers
**Classification:** BRANCH-OWNED

Similar structure to customers with:
- Banking info (bank_name, bank_account, bank_iban)
- Lead time and minimum order settings
- Rating and preference flags

---

## 6. Warehouse & Inventory Tables

### 6.1 warehouses
**Classification:** BRANCH-OWNED

| Column | Type | Description |
|--------|------|-------------|
| id | bigint unsigned | Primary key |
| branch_id | bigint unsigned | FK to branches |
| name | varchar(191) | Warehouse name |
| code | varchar(50) | Warehouse code |
| type | varchar(30) | standard/transit/virtual |
| manager_id | bigint unsigned | FK to users |
| allow_negative_stock | boolean | Allow negative |

### 6.2 inventory_batches
**Classification:** BRANCH-OWNED

Batch tracking with expiry dates and unit costs.

### 6.3 inventory_serials
**Classification:** BRANCH-OWNED

Serial number tracking with warranty dates.

### 6.4 stock_movements
**Classification:** BRANCH-OWNED

Polymorphic table tracking all inventory changes:
- movement_type: purchase, sale, transfer_in, transfer_out, adjustment, return
- reference_type + reference_id: links to source transaction
- stock_before, stock_after: running balance

### 6.5 stock_adjustments / adjustment_items
**Classification:** BRANCH-OWNED

Stock count and adjustment functionality.

### 6.6 transfers / transfer_items
**Classification:** BRANCH-OWNED

Inter-warehouse and inter-branch stock transfers.

---

## 7. Sales Tables

### 7.1 pos_sessions
**Classification:** BRANCH-OWNED

POS cashier sessions with opening/closing balances.

### 7.2 sales
**Classification:** BRANCH-OWNED

| Key Financial Columns | Type |
|-----------------------|------|
| subtotal | decimal(18,4) |
| discount_amount | decimal(18,4) |
| tax_amount | decimal(18,4) |
| shipping_amount | decimal(18,4) |
| total_amount | decimal(18,4) |
| paid_amount | decimal(18,4) |
| exchange_rate | decimal(18,8) |

**Indexes:** status, payment_status, sale_date, customer_id, branch_id

### 7.3 sale_items
**Classification:** BRANCH-OWNED (via sale)

Line items with quantity, unit_price, cost_price, discounts, taxes.

### 7.4 sale_payments
**Classification:** BRANCH-OWNED (via sale)

Payment records supporting multiple payment methods.

---

## 8. Purchase Tables

### 8.1 purchase_requisitions
**Classification:** BRANCH-OWNED

Internal purchase requests with approval workflow.

### 8.2 supplier_quotations
**Classification:** BRANCH-OWNED

Supplier quotes for comparison.

### 8.3 purchases
**Classification:** BRANCH-OWNED

Purchase orders with similar structure to sales.

### 8.4 goods_received_notes (GRN)
**Classification:** BRANCH-OWNED

Receiving inspection with quantity acceptance/rejection.

---

## 9. Accounting Tables

### 9.1 fiscal_periods
**Classification:** BRANCH-OWNED

Accounting periods with open/closed/locked status.

### 9.2 accounts
**Classification:** BRANCH-OWNED

Chart of accounts with hierarchical structure.
- type: asset, liability, equity, revenue, expense
- account_category, sub_category for grouping

### 9.3 journal_entries
**Classification:** BRANCH-OWNED

| Key Columns | Type | Description |
|-------------|------|-------------|
| total_debit | decimal(18,4) | Total debits |
| total_credit | decimal(18,4) | Total credits |
| status | varchar(30) | draft/posted/void |
| entry_date | date | Transaction date |

### 9.4 journal_entry_lines
**Classification:** BRANCH-OWNED (via journal_entry)

Individual debit/credit entries.

### 9.5 bank_accounts
**Classification:** BRANCH-OWNED

Bank account management with balances.

### 9.6 bank_transactions
**Classification:** BRANCH-OWNED

Bank transaction register.

### 9.7 bank_reconciliations
**Classification:** BRANCH-OWNED

Bank statement reconciliation.

---

## 10. HR & Payroll Tables

### 10.1 hr_employees
**Classification:** BRANCH-OWNED

Comprehensive employee records:
- Personal info, contact, emergency contact
- Employment details (position, department, hire_date)
- Salary and allowances (all decimal(18,4))
- Leave balances

### 10.2 shifts
**Classification:** BRANCH-OWNED

Work shift definitions with times and overtime rules.

### 10.3 attendances
**Classification:** BRANCH-OWNED

Daily attendance with clock in/out, GPS coordinates.

### 10.4 leave_types, leave_balances, leave_requests
**Classification:** BRANCH-OWNED

Leave management with accrual rules and approval workflow.

### 10.5 payrolls
**Classification:** BRANCH-OWNED

Monthly payroll with:
- Earnings (basic_salary, allowances, overtime, bonus)
- Deductions (tax, insurance, loans, absences)
- Net salary calculation

---

## 11. Fixed Assets Tables

### 11.1 fixed_assets
**Classification:** BRANCH-OWNED

Asset register with depreciation tracking.

| Key Financial Columns | Type |
|-----------------------|------|
| purchase_cost | decimal(18,4) |
| salvage_value | decimal(18,4) |
| accumulated_depreciation | decimal(18,4) |
| current_value | decimal(18,4) |

### 11.2 asset_depreciations
**Classification:** BRANCH-OWNED

Periodic depreciation records.

### 11.3 asset_maintenance_logs
**Classification:** BRANCH-OWNED

Maintenance history.

---

## 12. Manufacturing Tables

### 12.1 work_centers
**Classification:** BRANCH-OWNED

Production work centers with capacity and cost rates.

### 12.2 bills_of_materials
**Classification:** BRANCH-OWNED

BOM headers with yield and estimated cost.

### 12.3 bom_items, bom_operations
**Classification:** BRANCH-OWNED

BOM components and manufacturing operations.

### 12.4 production_orders
**Classification:** BRANCH-OWNED

Production work orders with planned/actual quantities and costs.

---

## 13. Project Management Tables

### 13.1 projects
**Classification:** BRANCH-OWNED

Project headers with budget, billing type, progress tracking.

### 13.2 project_tasks
**Classification:** BRANCH-OWNED

Hierarchical task management with assignments.

### 13.3 project_milestones
**Classification:** BRANCH-OWNED

Project milestones with deliverables.

### 13.4 project_expenses, project_time_logs
**Classification:** BRANCH-OWNED

Expense and time tracking for projects.

---

## 14. Rental Management Tables

### 14.1 properties
**Classification:** BRANCH-OWNED

Property buildings/locations.

### 14.2 rental_units
**Classification:** BRANCH-OWNED

Individual rental units with rates.

### 14.3 tenants
**Classification:** BRANCH-OWNED

Tenant/lessee information.

### 14.4 rental_contracts
**Classification:** BRANCH-OWNED

Lease agreements with terms and conditions.

### 14.5 rental_invoices, rental_payments
**Classification:** BRANCH-OWNED

Rental billing and payment tracking.

---

## 15. Vehicle Management Tables

### 15.1 vehicle_models
**Classification:** GLOBAL

Reference table for vehicle makes/models (for auto parts).

### 15.2 vehicles
**Classification:** BRANCH-OWNED

Vehicle inventory.

### 15.3 vehicle_contracts, vehicle_payments
**Classification:** BRANCH-OWNED

Vehicle sales/lease contracts.

---

## 16. Ticketing/Support Tables

### 16.1 ticket_priorities, ticket_sla_policies, ticket_categories
**Classification:** GLOBAL

Ticket configuration.

### 16.2 tickets
**Classification:** BRANCH-OWNED

Support tickets with SLA tracking.

### 16.3 ticket_replies
**Classification:** BRANCH-OWNED

Ticket conversation thread.

---

## 17. Workflow Tables

### 17.1 workflow_definitions
**Classification:** BRANCH-OWNED

Approval workflow templates.

### 17.2 workflow_instances
**Classification:** BRANCH-OWNED

Active workflow instances (polymorphic: entity_type + entity_id).

### 17.3 workflow_approvals
**Classification:** BRANCH-OWNED

Approval stage records.

---

## 18. Document Management Tables

### 18.1 documents
**Classification:** BRANCH-OWNED

Document storage with versioning.

### 18.2 document_versions
**Classification:** BRANCH-OWNED

Version history.

### 18.3 document_shares
**Classification:** BRANCH-OWNED

Document access control.

### 18.4 attachments
**Classification:** BRANCH-OWNED (polymorphic)

Generic file attachments for any entity.

---

## 19. Reporting Tables

### 19.1 report_definitions
**Classification:** GLOBAL

Report metadata and configuration.

### 19.2 scheduled_reports
**Classification:** USER-OWNED

User scheduled report jobs.

### 19.3 saved_report_views
**Classification:** USER-OWNED

Saved filter/column configurations.

---

## 20. Dashboard Tables

### 20.1 dashboard_widgets
**Classification:** GLOBAL

Available widget definitions.

### 20.2 user_dashboard_layouts
**Classification:** USER-OWNED

User dashboard configurations.

### 20.3 user_dashboard_widgets
**Classification:** USER-OWNED

Widget placement per user.

---

## 21. Alert & Monitoring Tables

### 21.1 alert_rules
**Classification:** BRANCH-OWNED

Alert configuration with thresholds and conditions.

### 21.2 alert_instances
**Classification:** BRANCH-OWNED

Triggered alert records.

### 21.3 low_stock_alerts
**Classification:** BRANCH-OWNED

Stock-specific alerts.

---

## 22. Pivot Tables Summary

| Table | Relationship |
|-------|--------------|
| branch_user | branches ↔ users |
| branch_modules | branches ↔ modules |
| branch_admins | branches ↔ users (admin permissions) |
| employee_shifts | hr_employees ↔ shifts |
| document_tag | documents ↔ document_tags |
| product_compatibilities | products ↔ vehicle_models |
| task_dependencies | project_tasks ↔ project_tasks |
| model_has_roles | morphable ↔ roles |
| model_has_permissions | morphable ↔ permissions |
| role_has_permissions | roles ↔ permissions |

---

## Statistics Summary

| Metric | Count |
|--------|-------|
| Total Tables | ~120 |
| Branch-owned Tables | ~85 |
| Global Tables | ~25 |
| User-owned Tables | ~10 |
| Pivot Tables | ~10 |
| Foreign Key Constraints | ~200+ |
| Indexes | ~300+ |

---

## Naming Conventions

### Index Names
- `idx_<table_abbr>_<column>` - Regular indexes
- `uq_<table_abbr>_<columns>` - Unique constraints
- `fk_<table_abbr>_<column>__<ref_table_abbr>` - Foreign keys

### Table Abbreviations
| Table | Abbreviation |
|-------|--------------|
| branches | brnch |
| users | usr |
| products | prd |
| customers | cust |
| suppliers | supp |
| warehouses | wh |
| sales | sale |
| purchases | purch |
| journal_entries | je |
| hr_employees | hremp |

---

## Decimal Precision Standards

| Data Type | Precision | Usage |
|-----------|-----------|-------|
| Money/Currency | decimal(18,4) | prices, costs, totals, balances |
| Exchange Rates | decimal(18,8) | currency conversion rates |
| Quantities | decimal(18,4) | stock quantities |
| Percentages | decimal(5,2) | tax rates, discounts |
| Large Percentages | decimal(8,4) | rates that may exceed 100% |

---

## Audit Trail Strategy

### Branch-owned Transactional Tables
- `branch_id` - Required FK
- `created_by` - FK to users (nullable for system jobs)
- `updated_by` - FK to users (nullable)
- `deleted_by` - FK to users (only with softDeletes)
- `timestamps()` - created_at, updated_at
- `softDeletes()` - deleted_at

### Global Reference Tables
- `timestamps()` only
- Optional `created_by` for admin-managed tables

### User-owned Tables
- `user_id` - Primary ownership FK
- `timestamps()` - created_at, updated_at
