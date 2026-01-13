# APMO ERP (apmoerpv2.zip) — Bug Report (NEW + Still-Unfixed)

**Scan date:** 2026-01-13  
**Framework target:** Laravel 12 + Livewire 4.0-beta.5 (from `composer.json`)  
**Scope:** Full codebase scan (models / services / controllers / Livewire / jobs / listeners). As requested, I did **not** focus on seeders; I only referenced schema expectations when the code itself explicitly depends on it (e.g., `CheckDatabaseIntegrity`).

---

## Executive summary (highest impact first)

1. **Branch isolation is currently unsafe/broken for non-super-admin users** because the **global `BranchScope`** is applied to **many tables that do not have `branch_id`** (e.g., `sale_items`, `purchase_items`, `stock_movements`). This will cause runtime SQL errors like `Unknown column sale_items.branch_id` or silently hide data depending on the table/schema. **This is a system-wide ERP-breaker.**
2. **Accounting journal entries can become unbalanced** for **partially paid sales** (missing Accounts Receivable line for unpaid remainder). This is a **finance-critical** correctness issue.
3. **Branch scope is disabled in console** (queues/scheduled jobs) → cross-branch processing/data exposure risk.
4. Several **reports/analytics modules are producing wrong numbers** due to incorrect column usage (e.g., `total`/`paid` instead of `total_amount`/`paid_amount`), and **some analytics methods are placeholders returning empty data**.
5. **Purchases created through `PurchaseService` store wrong totals** (`total_amount = subtotal` only), causing downstream finance/reporting mismatches.

---

# A) Bugs that are STILL present (were in the previous report and appear not fixed here)

## U-01 — Branch scope disabled in console (queues / scheduled jobs)
**Severity:** Critical (data isolation / correctness)

- **File:** `app/Models/Scopes/BranchScope.php`  
  **Lines:** 31–33

**Problem**
```php
if (app()->runningInConsole() && ! app()->runningUnitTests()) {
    return;
}
```
This disables branch scoping in:
- queue workers
- scheduler
- artisan commands that read/write data

**Impact**
Any queued listener/job that queries branch-scoped models can accidentally:
- read data across all branches
- write updates across branches
- generate reports/alerts for the wrong branch

**Recommended fix**
Replace the broad console condition with a narrow allowlist (only skip for migrations/seeders), e.g. via:
- checking specific commands, or
- an env/config flag (`branch_scope.disable=true`) used only for safe operations.

---

## U-02 — `HasBranch` auto-assign never sets `branch_id` (missing `currentBranchId()`)
**Severity:** High (records can be created with `branch_id = NULL`)

- **File:** `app/Traits/HasBranch.php`  
  **Lines:** 26–35

**Problem**
`branch_id` is set only if the model has a `currentBranchId()` method:
```php
if (empty($model->branch_id) && method_exists($model, 'currentBranchId')) {
    $branchId = $model->currentBranchId();
    if ($branchId) {
        $model->branch_id = $branchId;
    }
}
```
In this codebase, the trait that provides `currentBranchId()` is **not used by `BaseModel`**, so most models will never satisfy this condition.

**Impact**
- Records created via `Model::create()` (without manually passing `branch_id`) will remain `NULL`.
- With branch scoping enabled, these rows become invisible to branch users.

**Recommended fix**
Either:
- add `currentBranchId()` to `BaseModel`, sourcing from `BranchContextManager`, or
- refactor: set `branch_id` using `BranchContextManager::getCurrentBranchId()` directly in `HasBranch`.

---

## U-03 — POS idempotency not scoped by branch
**Severity:** High (cross-branch data collision)

- **File:** `app/Services/POSService.php`  
  **Lines:** 35–43

**Problem**
Idempotency uses only `client_uuid`:
```php
$existing = Sale::where('client_uuid', $clientUuid)->first();
```

**Impact**
Two different branches can reuse the same UUID (or a buggy POS client can), resulting in returning the wrong sale from another branch.

**Recommended fix**
Scope the check by branch:
- `->where('branch_id', $branchId)` (or current branch)

---

## U-04 — Purchases created via `PurchaseService` store incorrect totals
**Severity:** High (finance/reporting mismatch)

- **File:** `app/Services/PurchaseService.php`  
  **Lines:** 96–107

**Problem**
`total_amount` is set equal to subtotal only:
```php
$subtotal = array_sum(...);
$p = Purchase::create([
  ...
  'subtotal' => $subtotal,
  'total_amount' => $subtotal,
  ...
]);
```

**Impact**
If the purchase includes shipping, taxes, or discounts, the stored `total_amount` will be wrong.
Downstream effects:
- payables
- cashflow forecast
- profit reports
- inventory valuation (if tied to purchase totals)

**Recommended fix**
Compute header total consistently (e.g. `subtotal + tax_amount + shipping_amount - discount_amount`) and keep it aligned with UI Livewire purchase forms.

---

## U-05 — Accounting: partial payments create unbalanced journal entries
**Severity:** Critical (financial correctness)

- **File:** `app/Services/AccountingService.php`  
  **Lines:** 71–156

**Problem**
When there are payments, the code debits bank/cash for the payment amounts, and credits revenue/tax. **But if payments are partial**, it does **not** add an Accounts Receivable line for the unpaid remainder.

Key logic:
- Payments exist → debit payment accounts (lines 85–111)
- Receivable is only used when **no payments exist** and sale isn’t paid (lines 131–142)

**Impact**
- Journal entries become unbalanced (debits < credits).
- Financial statements become unreliable.

**Recommended fix**
If `total_amount > sum(payments)` then add:
- **Debit** Accounts Receivable for `(total_amount - paid_amount)`
Also: enforce `validateBalance()` before setting status to posted.

---

## U-06 — Audit logging disabled for the first 5 minutes in console
**Severity:** Medium (observability / compliance risk)

- **File:** `app/Traits/AuditsChanges.php`  
  **Lines:** 39–51

**Problem**
```php
if (app()->runningInConsole()) {
   ... if ($start && now()->diffInMinutes($start) < 5) return;
}
```

**Impact**
Queued jobs / schedule tasks executed soon after deploy/restart won’t be audited.

**Recommended fix**
Avoid time-window skipping; use explicit command/job allowlist or config flag.

---

## U-07 — Dashboard: `low_stock_count` / `out_of_stock_count` uses `count()` on a grouped query
**Severity:** Medium (wrong dashboard numbers)

- **File:** `app/Livewire/Components/DashboardWidgets.php`  
  **Lines:** 108–129

**Problem**
This query groups by product, but then uses `count()`:
```php
->groupBy('products.id', 'products.min_stock')
->havingRaw(...)
->count();
```
Laravel’s `count()` on a grouped query often returns the **count of the first group**, not the number of groups, leading to wrong results.

**Recommended fix**
Use one of:
- wrap as subquery and count rows
- `->get()->count()`
- `->distinct('products.id')->count('products.id')`

---

## U-08 — Stock source-of-truth inconsistency (products.stock_quantity vs stock_movements)
**Severity:** Medium/High (inventory numbers drift)

- **File:** `app/Listeners/UpdateStockOnSale.php`  
  **Lines:** 75–86

**Problem**
Sales create stock movements but do not update `products.stock_quantity`. Meanwhile, multiple dashboards/reports still rely on `products.stock_quantity`.

**Impact**
- Inventory widgets & KPIs can disagree
- Reorder alerts can trigger incorrectly

**Recommended fix**
Choose a single source of truth:
- Either keep `stock_quantity` updated transactionally, or
- remove usage of `stock_quantity` in KPIs/widgets and compute from `stock_movements` consistently.

---

## U-09 — StockService aggregates stock without an explicit branch constraint (can mix branches depending on data model)
**Severity:** Medium (inventory correctness risk)

- **File:** `app/Services/StockService.php`  
  **Lines:** 19–32

**Problem**
`StockService` sums `stock_movements.quantity` by `product_id` and optional `warehouse_id`, but does not enforce a branch boundary.

**Impact**
- If your ERP uses a **shared product master** across branches (same `product_id` referenced in multiple branches), calling `getCurrentStock($productId)`—especially with `warehouseId = null`—can mix stock across branches.
- Even if products are branch-specific today, this becomes a **future footgun** if you later centralize products.

**Recommended fix**
- If products are branch-specific: enforce this invariant explicitly (e.g., assert product belongs to current branch).
- If products are shared: add a branch filter by joining `warehouses` (or `products`) and filtering `warehouses.branch_id` / `products.branch_id`.


# B) Newly discovered bugs (NOT in the previous report)

## N-01 — **System-wide ERP breaker:** Branch scope applied to models/tables that do NOT have `branch_id`
**Severity:** Critical (multi-branch core is broken for non-super-admins)

### Evidence in code
1) `BaseModel` applies `HasBranch` to **all** inheriting models:
- **File:** `app/Models/BaseModel.php`  
  **Lines:** 35–38

2) `BranchScope` decides a model is branch-scoped if it has a `branch()` method:
- **File:** `app/Models/Scopes/BranchScope.php`  
  **Lines:** 111–123

But **every** `BaseModel` has `branch()` because `HasBranch` defines it. So `hasBranchIdColumn()` returns `true` even for tables without a `branch_id` column.

3) The project itself documents that some tables don’t have `branch_id`:
- **File:** `app/Console/Commands/CheckDatabaseIntegrity.php`  
  **Lines:** 69–76 (index checks show `stock_movements` indexed by `product_id`, `warehouse_id`, `created_at` only)  
  **Lines:** 113–120 (foreign keys show `sale_items`/`purchase_items` link to sale/purchase, not branch)

### Concrete examples
- **Stock movements** model is branch-scoped, but table is expected not to have branch_id:
  - `app/Models/StockMovement.php` lines 9–33
- **Sale items** model is branch-scoped, but table is expected not to have branch_id:
  - `app/Models/SaleItem.php` lines 9–40

### Impact
For non-super-admin users (branch users), any Eloquent query on these models can:
- throw SQL errors: `Unknown column <table>.branch_id`
- or hide rows if the column exists but is not populated

This breaks core ERP flows:
- viewing a sale and its items
- inventory movements
- receipts & deliveries
- manufacturing sub-entities
- store integrations

### Recommended fix
**Do not attach `HasBranch` at the `BaseModel` level** unless every inheriting table truly has `branch_id`.
Options:
1) Move `use HasBranch;` out of `BaseModel` and only include it on models whose tables have `branch_id`.
2) Keep the trait but add branch scope only if `Schema::hasColumn($table,'branch_id')` (with caching) and remove the `method_exists($model,'branch')` fallback.

**Appendix A below lists the highest-risk models that extend `BaseModel` but do not mention `branch_id`.**

---

## N-02 — Branch reports component uses non-existent columns (`total`, `paid`, `due_total`) and wrong sale item column
**Severity:** High (reports crash or show zeros)

- **File:** `app/Livewire/Admin/Branch/Reports.php`

**Problems**
1) Aggregates use `total`/`paid` columns (not defined on `Sale` model which uses `total_amount`/`paid_amount`):
- Lines 81–83:
  - `sum('total')`, `avg('total')`, `sum('paid')`

2) Uses `sum('due_total')` which is an **accessor**, not a DB column:
- Line 84

3) Top products uses `sale_items.total` but `SaleItem` uses `line_total`:
- Line 135 (`SUM(sale_items.total)`)

4) Daily sales uses `SUM(total)` on sales table:
- Line 150

**Impact**
- SQL errors (unknown columns) or incorrect KPI values.

**Recommended fix**
- Replace with:
  - `total_amount`, `paid_amount`
  - due can be computed as `SUM(total_amount - paid_amount)`
  - use `sale_items.line_total`

---

## N-03 — Advanced analytics uses a non-existent Sale field `total` and contains placeholder logic returning empty results
**Severity:** High (analytics dashboard unreliable)

- **File:** `app/Services/Analytics/AdvancedAnalyticsService.php`

**Problems**
1) Uses `$sales->sum('total')` where `Sale` model does not define `total`:
- Line 57

2) Lifetime value calculation uses `sum('total')` again:
- Line 306

3) Multiple methods are stubs returning empty arrays/zeros:
- Lines 382–517 (e.g., `getCustomerSegmentation`, `getLeadScoring`, `getBehavioralInsights`, etc.)

**Impact**
Advanced analytics dashboard will show zeros/empty insights even when data exists.

**Recommended fix**
- Replace `total` with `total_amount` or `grand_total` accessor.
- Implement or remove placeholder analytics blocks so the UI doesn’t present fake “empty” analytics.

---

## N-04 — Customer behavior: `avg_clv` reads a non-existent `withSum` alias
**Severity:** Medium (wrong metric)

- **File:** `app/Services/Analytics/CustomerBehaviorService.php`  
  **Line:** 328

**Problem**
Query uses:
```php
->withSum('sales', 'total_amount')
```
So the alias is `sales_sum_total_amount`, but the code reads:
```php
$customers->avg('sales_sum_grand_total')
```

**Impact**
`avg_clv` will always be 0 (or null → 0), making the KPI incorrect.

**Recommended fix**
Use `sales_sum_total_amount`.

---

## N-05 — Store integration → Sale conversion uses outdated field names (`total`, `discount_total`, etc.)
**Severity:** High (store orders cannot convert into correct sales)

- **File:** `app/Services/Store/StoreOrderToSaleService.php`

**Problem**
The converter only maps these fields if they exist in Sale fillable:
- `total`, `discount_total`, `shipping_total`, `tax_total`

But `Sale` uses:
- `total_amount`, `discount_amount`, `shipping_amount`, `tax_amount`, `subtotal`

So most totals are never written during conversion.

Also, item creation can proceed with `product_id = null` (lines 147–168 & 179–184), which can violate DB constraints or create unusable sale items.

**Impact**
- Converted sales have wrong totals
- Accounting/inventory downstream logic breaks

**Recommended fix**
- Add mapping cases for the current Sale schema (`total_amount`, `discount_amount`, etc.)
- Validate that each item resolves a `product_id` (or decide a safe fallback strategy)

---

## N-06 — Dashboard widgets count “active products” using `is_active` while product model uses `status`
**Severity:** Medium (wrong KPI, possible SQL error depending on schema)

- **File:** `app/Livewire/Components/DashboardWidgets.php`  
  **Line:** 105

**Problem**
```php
Product::where('is_active', true)->count();
```
But `Product` model uses `status` as the active indicator in many other services.

**Impact**
- Active product count may be wrong, or could error if `is_active` isn’t a real column.

**Recommended fix**
Standardize on a single field (`status = active`) and update this widget accordingly.

---

## N-07 — Dashboard concern: `payment_methods` chart filters by month without year
**Severity:** Low/Medium (wrong chart across years)

- **File:** `app/Livewire/Concerns/LoadsDashboardData.php`  
  **Lines:** 216–226

**Problem**
Uses:
```php
->whereMonth('sales.created_at', now()->month)
```
without `whereYear`, so January 2026 includes January 2025, etc.

**Recommended fix**
Add `->whereYear('sales.created_at', now()->year)`.

---

# Appendix A — Models extending `BaseModel` with NO `branch_id` reference (high risk)

These models are extremely likely to break under `BranchScope` for branch users (unknown column) or become invisible (if `branch_id` exists but isn’t set):

- `app/Models/AdjustmentItem.php`
- `app/Models/AlertRecipient.php`
- `app/Models/BomItem.php`
- `app/Models/BomOperation.php`
- `app/Models/DashboardWidget.php`
- `app/Models/Delivery.php`
- `app/Models/EmployeeShift.php`
- `app/Models/GRNItem.php`
- `app/Models/LeaveRequest.php`
- `app/Models/ManufacturingTransaction.php`
- `app/Models/ProductionOrderItem.php`
- `app/Models/ProductionOrderOperation.php`
- `app/Models/ProductStoreMapping.php`
- `app/Models/PurchaseItem.php`
- `app/Models/PurchaseRequisitionItem.php`
- `app/Models/Receipt.php`
- `app/Models/RentalPeriod.php`
- `app/Models/SaleItem.php`
- `app/Models/SearchHistory.php`
- `app/Models/StockMovement.php`
- `app/Models/StoreIntegration.php`
- `app/Models/StoreSyncLog.php`
- `app/Models/StoreToken.php`
- `app/Models/SupplierQuotationItem.php`
- `app/Models/TicketSLAPolicy.php`
- `app/Models/TransferItem.php`
- `app/Models/UserDashboardWidget.php`
- `app/Models/VehicleContract.php`
- `app/Models/VehiclePayment.php`
- `app/Models/Warranty.php`
- `app/Models/WorkflowRule.php`

