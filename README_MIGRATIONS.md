# APMO ERP Database Migrations

This document provides instructions for running and verifying the database migrations for the APMO ERP system.

## Prerequisites

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer installed
- Laravel 10.x or higher

## Database Configuration

Ensure your `.env` file has the correct database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apmoerp
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

## Running Migrations

### Fresh Installation

For a fresh database installation, run:

```bash
# Create the database first (if not exists)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS apmoerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run all migrations
php artisan migrate
```

### Migration Order

The migrations are ordered by date prefix to ensure dependencies are created in the correct order:

1. **2026_01_01_*** - Core tables (branches, users, permissions, modules)
2. **2026_01_02_*** - Master data (currencies, units, taxes, warehouses, products, customers, suppliers, stores)
3. **2026_01_03_*** - Accounting (accounts, journal entries, bank accounts, expenses, fixed assets)
4. **2026_01_04_*** - Transactions (sales, purchases, inventory, returns)
5. **2026_01_05_*** - Operations (HR, payroll, rentals, vehicles, manufacturing, projects)
6. **2026_01_06_*** - Support (tickets, documents, workflows, alerts)
7. **2026_01_07_*** - Reporting & UI (reports, dashboards, user preferences)
8. **2026_01_08_*** - Laravel framework tables (cache, sessions, jobs)

### Rollback

To rollback the last batch of migrations:

```bash
php artisan migrate:rollback
```

To rollback all migrations:

```bash
php artisan migrate:reset
```

### Refresh (Development Only)

To drop all tables and re-run all migrations:

```bash
php artisan migrate:fresh
```

## Verification Checklist

After running migrations, verify the following:

### 1. Table Count
```sql
SELECT COUNT(*) as table_count FROM information_schema.tables 
WHERE table_schema = 'apmoerp' AND table_type = 'BASE TABLE';
```
Expected: ~120 tables

### 2. Foreign Key Constraints
```sql
SELECT COUNT(*) as fk_count FROM information_schema.table_constraints 
WHERE table_schema = 'apmoerp' AND constraint_type = 'FOREIGN KEY';
```
Expected: ~200+ foreign keys

### 3. Index Count
```sql
SELECT COUNT(DISTINCT index_name) as index_count FROM information_schema.statistics 
WHERE table_schema = 'apmoerp';
```
Expected: ~300+ indexes

### 4. Core Tables Exist
```sql
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'apmoerp' 
AND table_name IN (
    'branches', 'users', 'roles', 'permissions',
    'products', 'customers', 'suppliers',
    'sales', 'purchases', 'journal_entries',
    'hr_employees', 'payrolls', 'projects'
);
```
All listed tables should exist.

### 5. Engine and Charset Verification
```sql
SELECT table_name, engine, table_collation 
FROM information_schema.tables 
WHERE table_schema = 'apmoerp' 
AND (engine != 'InnoDB' OR table_collation != 'utf8mb4_unicode_ci');
```
Should return empty (all tables use InnoDB and utf8mb4_unicode_ci).

### 6. Verify Key Tables Structure

#### Branches Table
```sql
DESCRIBE branches;
```

#### Products Table
```sql
DESCRIBE products;
```

#### Sales Table
```sql
DESCRIBE sales;
```

## Common Issues & Solutions

### Issue: Foreign Key Constraint Fails
If you get a foreign key constraint error during migration:

1. Check that migrations are running in the correct order
2. Ensure referenced tables exist before the referencing table
3. Check that column types match between foreign key and primary key

### Issue: Identifier Too Long
MySQL has a 64-character limit for identifiers. All index and FK names in these migrations are explicitly named and kept under this limit.

### Issue: Charset Mismatch
Ensure your MySQL server default charset is utf8mb4:

```sql
SHOW VARIABLES LIKE 'character_set%';
SHOW VARIABLES LIKE 'collation%';
```

## Migration Statistics

| Category | Count |
|----------|-------|
| Migration Files | 35+ |
| Total Tables | ~120 |
| Branch-owned Tables | ~85 |
| Global Tables | ~25 |
| User-owned Tables | ~10 |
| Pivot Tables | ~10 |
| Foreign Keys | ~200+ |
| Indexes | ~300+ |

## Naming Conventions Used

### Tables
- Snake_case plural names (e.g., `products`, `sale_items`)
- Pivot tables use singular connected by underscore (e.g., `branch_user`)

### Indexes
- `idx_<table_abbr>_<column>` - Regular indexes
- `uq_<table_abbr>_<column>` - Unique constraints
- `fk_<table_abbr>_<column>__<ref_table>` - Foreign keys

### Table Abbreviations
| Full Name | Abbreviation |
|-----------|--------------|
| branches | brnch |
| users | usr |
| products | prd |
| product_categories | prdcat |
| customers | cust |
| suppliers | supp |
| warehouses | wh |
| sales | sale |
| sale_items | salei |
| purchases | purch |
| purchase_items | purchi |
| journal_entries | je |
| journal_entry_lines | jel |
| hr_employees | hremp |
| workflow_definitions | wfdef |
| workflow_instances | wfinst |

## Decimal Precision Standards

| Data Type | Precision | Usage |
|-----------|-----------|-------|
| Money amounts | decimal(18,4) | Prices, costs, totals |
| Exchange rates | decimal(18,8) | Currency conversion |
| Quantities | decimal(18,4) | Stock quantities |
| Percentages | decimal(5,2) | Tax rates, discounts |

## Audit Trail Columns

Branch-owned transactional tables include:
- `branch_id` - FK to branches (required)
- `created_by` - FK to users (nullable)
- `updated_by` - FK to users (nullable)
- `deleted_by` - FK to users (only with softDeletes)
- `created_at`, `updated_at` - Timestamps
- `deleted_at` - Soft delete timestamp

## Testing the Schema

### Laravel Artisan Commands
```bash
# Check migration status
php artisan migrate:status

# Show pending migrations
php artisan migrate:status | grep -E "No|Pending"

# Fresh migrate with seeding (if seeders exist)
php artisan migrate:fresh --seed
```

### Database Connection Test
```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::table('branches')->count();
```

## Support

For issues related to these migrations, check:
1. Database/schema.md for table documentation
2. Database/diagram.mmd for ER diagram
3. Individual migration files for column details
