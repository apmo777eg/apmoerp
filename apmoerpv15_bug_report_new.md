# APMO ERP v15 — Bug Report (NEW + Still-Unfixed only)

_Generated: 2026-01-14 13:00 UTC_

**Scope:** codebase only (ignored `database/`, seeders, migrations).

This report includes:
- **New bugs detected in v15**, and
- **Bugs previously reported in v14 that are still present**.

---

## Summary

- **New bugs:** 5
- **Still-unfixed from v14:** 2


---

## New bugs in v15

### NEW-V15-CRITICAL-01 — Warehouse status is an accessor (getStatusAttribute) but code filters with where('status','active') → SQL error / wrong results

**Severity:** CRITICAL  
**Type:** Logic / Query bug


**Evidence**

- `/app/Livewire/Warehouse/Transfers/Form.php` (line **142**) — Warehouse::...->where('status','active')
- `/app/Livewire/Warehouse/Movements/Index.php` (line **100**) — \App\Models\Warehouse::...->where('status','active')
- `/app/Livewire/Warehouse/Adjustments/Form.php` (line **175**) — Warehouse::...->where('status','active')
- `/app/Models/Warehouse.php` — getStatusAttribute() returns is_active ? 'active' : 'inactive'


**Impact**

These pages will crash on DBs without a real `warehouses.status` column, or will silently mis-filter if column exists from older schema.


**Suggested fix**

Replace with where('is_active', true) OR add a real `status` column and keep it synced. Prefer `is_active` since accessor already maps it.

---

### NEW-V15-CRITICAL-02 — Routes reference controller methods that do not exist (Admin reports/modules/auth central HRM) → endpoints will 500

**Severity:** CRITICAL  
**Type:** Routing / Runtime crash


**Evidence**

- `/routes/api/admin.php` (line **100**) — Admin Reports routes call usage/performance/errors/finance* methods
- `/app/Http/Controllers/Admin/ReportsController.php` — Controller only has finance(), topProducts()
- `/routes/api/admin.php` (line **39**) — ModuleCatalogController routes expect store/show/update/destroy
- `/app/Http/Controllers/Admin/ModuleCatalogController.php` — Only index() exists


**Impact**

A set of API endpoints is unusable; route caching & automated tests may fail; clients relying on these endpoints break.


**Suggested fix**

Either (A) implement the missing public methods, or (B) remove/disable the routes. Prefer adding Feature tests to ensure every route action resolves.

---

### NEW-V15-HIGH-01 — AccountingService::createEntry defaults branch_id to 1 when none provided → can post journal entries to wrong branch

**Severity:** HIGH  
**Type:** Finance / Multi-branch integrity


**Evidence**

- `/app/Services/AccountingService.php` (line **641**) — $branchId = $data['branch_id'] ?? auth()->user()?->branch_id ?? 1;


**Impact**

Background jobs / system-generated postings without explicit branch_id can silently post into Branch #1 (data corruption).


**Suggested fix**

Do not default to 1. Require branch_id in $data, or derive it from the referenced entity (sale/purchase/transfer) and throw if missing.

---

### NEW-V15-HIGH-02 — Several admin/central forms default branch_id to 1 for create flows (when user has no branch) → records created in wrong branch

**Severity:** HIGH  
**Type:** Multi-branch logic


**Evidence**

- `/app/Livewire/Accounting/Accounts/Form.php` (line **94**) — $account->branch_id = $user->branch_id ?? 1;
- `/app/Livewire/Accounting/JournalEntries/Form.php` (line **177**) — $entry->branch_id = $user->branch_id ?? 1;
- `/app/Livewire/Warehouse/Transfers/Form.php` (line **102**) — 'branch_id' => $user->branch_id ?? 1,


**Impact**

Super-admin users (no branch_id) can unknowingly create accounting/warehouse records in Branch #1; can cause cross-branch leakage.


**Suggested fix**

If user has no branch_id, require selecting a branch in the form; do not default. Add validation + UI dropdown.

---

### NEW-V15-MEDIUM-01 — BankingService returns floats for monetary amounts after bcmath aggregation → precision loss

**Severity:** MEDIUM  
**Type:** Finance precision


**Evidence**

- `/app/Services/BankingService.php` (line **179**) — 'total_inflows' => (float) $inflows,
- `/app/Services/BankingService.php` (line **181**) — 'net_cashflow' => (float) $netCashflow,
- `/app/Services/BankingService.php` (line **253**) — return (float) $this->getAccountBalance($accountId);


**Impact**

Rounding/precision issues can appear in large volumes or when chaining calculations; mismatch vs stored decimals.


**Suggested fix**

Return money as string-decimal (e.g., '1234.56') or as integer minor units. Only cast/format at presentation layer.

---

## Still-unfixed (from v14)

### STILL-V14-CRITICAL-01 — Stock system inconsistency remains (products.stock_quantity vs stock_movements; warehouse-scoping cannot be represented by single column)

**Severity:** CRITICAL  
**Type:** Inventory / ERP integrity


**Evidence**

- `/app/Http/Controllers/Api/V1/InventoryController.php` (line **144**) — writes forceFill(['stock_quantity' => calculated]) based on warehouse_id
- `/app/Http/Controllers/Admin/Reports/InventoryReportsExportController.php` (line **69**) — reads $product->stock_quantity for export
- `/app/Console/Commands/CheckDatabaseIntegrity.php` (line **156**) — checks stock_quantity < 0 (treats as source of truth)


**Impact**

Reports, exports, and API show inconsistent stock; multi-warehouse stock cannot be accurate; reconciliation & loss alerts may be wrong.


**Suggested fix**

Make StockService/stock_movements the single source of truth. If you need caching, store per-warehouse in a separate table (product_warehouse_stock) updated transactionally with movements, and never write warehouse-specific values into products.stock_quantity.

---

### STILL-V14-HIGH-01 — Finance cashflow endpoint still computed from Sales/Purchases paid_amount (not bank/GL) → inaccurate cashflow

**Severity:** HIGH  
**Type:** Finance / Reporting logic


**Evidence**

- `/app/Http/Controllers/Branch/ReportsController.php` (line **84**) — cashflow sums DB::table('sales')->sum('paid_amount') and purchases->sum('paid_amount')


**Impact**

Cashflow can be materially wrong (refunds, bank fees, journals, adjustments, opening balances, transfers, partial payments, etc.).


**Suggested fix**

Compute cashflow from BankTransaction/BankingService or from posted journal entries (cash/bank accounts) with proper date filters.

---

## Appendix — Detected route/controller mismatches (top)

### App\Http\Controllers\Admin\ReportsController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/ReportsController.php`

  - `routes/api/admin.php` line **100** → method `usage` missing

  - `routes/api/admin.php` line **101** → method `performance` missing

  - `routes/api/admin.php` line **102** → method `errors` missing

  - `routes/api/admin.php` line **105** → method `financeSales` missing

  - `routes/api/admin.php` line **106** → method `financePurchases` missing

  - `routes/api/admin.php` line **107** → method `financePnl` missing

  - `routes/api/admin.php` line **108** → method `financeCashflow` missing

  - `routes/api/admin.php` line **109** → method `financeAging` missing



### App\Http\Controllers\Admin\BranchModuleController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/BranchModuleController.php`

  - `routes/api/admin.php` line **48** → method `attach` missing

  - `routes/api/admin.php` line **49** → method `detach` missing

  - `routes/api/admin.php` line **52** → method `updateSettings` missing

  - `routes/api/admin.php` line **56** → method `enable` missing

  - `routes/api/admin.php` line **57** → method `disable` missing



### App\Http\Controllers\Admin\ModuleFieldController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/ModuleFieldController.php`

  - `routes/api/admin.php` line **63** → method `store` missing

  - `routes/api/admin.php` line **64** → method `show` missing

  - `routes/api/admin.php` line **65** → method `update` missing

  - `routes/api/admin.php` line **66** → method `destroy` missing

  - `routes/api/admin.php` line **69** → method `reorder` missing



### App\Http\Controllers\Admin\ModuleCatalogController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/ModuleCatalogController.php`

  - `routes/api/admin.php` line **39** → method `store` missing

  - `routes/api/admin.php` line **40** → method `show` missing

  - `routes/api/admin.php` line **41** → method `update` missing

  - `routes/api/admin.php` line **42** → method `destroy` missing



### App\Http\Controllers\Admin\HrmCentral\AttendanceController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/HrmCentral/AttendanceController.php`

  - `routes/api/admin.php` line **121** → method `store` missing

  - `routes/api/admin.php` line **122** → method `update` missing

  - `routes/api/admin.php` line **123** → method `deactivate` missing



### App\Http\Controllers\Auth\AuthController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Auth/AuthController.php`

  - `routes/api/auth.php` line **10** → method `refresh` missing

  - `routes/api/auth.php` line **17** → method `changePassword` missing

  - `routes/api/auth.php` line **19** → method `revokeOtherSessions` missing



### App\Http\Controllers\Admin\HrmCentral\PayrollController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/HrmCentral/PayrollController.php`

  - `routes/api/admin.php` line **127** → method `show` missing

  - `routes/api/admin.php` line **130** → method `pay` missing



### App\Http\Controllers\Admin\HrmCentral\LeaveController

- Controller file: `/mnt/data/apmoerpv15/app/Http/Controllers/Admin/HrmCentral/LeaveController.php`

  - `routes/api/admin.php` line **134** → method `approve` missing

  - `routes/api/admin.php` line **135** → method `reject` missing


