# APMO ERP v10 — Bug Report (New + Still-Unfixed)

Scan target: `apmoerpv10.zip` (Laravel 12 / Livewire 4)
Scope: **All project files** (code-level review). **Database migrations/seeders ignored** per request.

This report includes ONLY:
1) **Newly discovered bugs in v10** (not listed in the previous v9 report)
2) **Bugs from v9 report that are still not fixed in v10**

---

## 1) Newly discovered bugs in v10

### V10-CRITICAL-01 — Stock calculations are NOT branch/warehouse scoped (cross-branch inventory leakage + wrong reorder/alerts)
**Impact:** In a multi-branch ERP, stock levels, low-stock flags, reorder suggestions, and automations can be **wrong**, and users may indirectly see/act on inventory that belongs to **other branches**.

**Root cause:** The “stock_movements is source of truth” update was implemented using **global** stock aggregation (SUM over all warehouses), but it does **not** filter by warehouse/branch in many core code paths.

**Evidence (examples):**
- Global stock expression sums all movements for a product (no warehouse filter):
  - `app/Services/StockService.php` lines **85–94** (expression: `SUM(quantity)` by `product_id` only).
- Product stock-status helpers use global stock (no warehouse/branch):
  - `app/Models/Product.php` lines **340–372** (calls `StockService::getCurrentStock($this->id)` without `$warehouseId`).
- Reorder engine uses the global expression + global stock again:
  - `app/Services/StockReorderService.php` lines **34–48** (uses `getStockCalculationExpression('products.id')`),
  - and lines **133–171** (calls `getCurrentStock($product->id)` again).
- Sales velocity used for reorder has **no branch filter**, so branch-specific reorder is calculated using all branches’ sales:
  - `app/Services/StockReorderService.php` lines **112–120** (join sales without filtering by `branch_id`).

**Why this is a bug**
Even if `products` are filtered by `branch_id`, the stock calculation is still a **global** sum over all branches’ warehouses because `stock_movements` is not branch-scoped at the table level, and the calculation does not join `warehouses.branch_id`.

**Fix direction**
- Decide the correct “stock dimension” for your ERP:
  - If stock is **per warehouse**: always require `warehouse_id` in queries/calculations.
  - If stock is **per branch**: aggregate `stock_movements` through `warehouses.branch_id`.
- Implement branch-aware stock helpers:
  - Add `StockService::getCurrentStockForBranch($productId, $branchId)` (join warehouses),
  - and `getBranchStockCalculationExpression($productIdColumn, $branchIdValueOrColumn)`.
- Update all callers (Product helpers, Reorder service, Automation service, dashboards) to use the branch/warehouse-aware methods.

---

### V10-HIGH-02 — `StockMovementRepository::create()` can record incorrect `stock_before/stock_after` under concurrency when there are no existing rows
**Impact:** `stock_before` / `stock_after` can become **incorrect** during concurrent stock writes (especially the first movement for a product+warehouse), breaking audit trails and any logic depending on those fields.

**Evidence:**
- The method attempts to lock the latest movement row to serialize writers:
  - `app/Repositories/StockMovementRepository.php` lines **140–145**.
- But if there is **no existing movement**, `first()` returns null and **no row is locked**, then both concurrent transactions can compute:
  - `currentStock = SUM(quantity)` as the same value before either commit (lines **146–153**),
  - and both create movements with identical `stock_before`, producing a misleading history.

**Fix direction**
- Use a deterministic lock even when no movement exists:
  - Option A: lock the **warehouse row** (or a `(product_id,warehouse_id)` aggregate row) with `lockForUpdate()` to serialize writers.
  - Option B: maintain a `inventory_balances` table per `(product_id, warehouse_id)` and lock/update it transactionally (preferred for ERP scale).
- If keeping only `stock_movements`, consider `SELECT ... FOR UPDATE` on `warehouses` (or a dedicated lock table) as the serialization anchor.

---

### V10-HIGH-03 — Branch scoping depends on `fillable` (mass-assignment config), which can silently disable branch isolation
**Impact:** A model can accidentally become **unscoped** (cross-branch leakage) if `branch_id` exists in the DB but is not included in `$fillable`. This is a security/segmentation risk.

**Evidence:**
- BranchScope treats “has branch_id column” as “branch_id is in fillable”:
  - `app/Models/Scopes/BranchScope.php` lines **170–177**.

**Why this is a bug**
`$fillable` is a **mass-assignment** whitelist, not a schema/source-of-truth for whether a table has `branch_id`. A developer can omit `branch_id` from `$fillable` for valid reasons (guarding), and the scope would stop applying without any error.

**Fix direction**
- Use a more reliable signal:
  - Option A: explicit interface/trait marker + explicit property (e.g., `protected bool $usesBranchScope = true;`),
  - Option B: cached schema inspection (`Schema::hasColumn($table,'branch_id')`) with memoization to avoid perf hits,
  - Option C: keep a central list of branch-scoped models.

---

### V10-MEDIUM-04 — Correlated subquery stock expressions will be slow on large product lists
**Impact:** Inventory pages (low stock, reorder, dashboards) may degrade badly at scale due to per-row correlated subqueries.

**Evidence:**
- `StockService::getStockCalculationExpression()` returns a correlated subquery:
  - `app/Services/StockService.php` lines **85–94**.
- It is used inside Product scopes and reorder queries:
  - `app/Models/Product.php` lines **274–308**,
  - `app/Services/StockReorderService.php` lines **34–72**.

**Fix direction**
- Replace correlated subqueries with join + group aggregate:
  - Precompute stock in a subquery grouped by `product_id` (and branch/warehouse where needed) and join it once.
- Or maintain an `inventory_balances` table.

---

## 2) Bugs from v9 report that are still not fixed in v10

### STILL-V9-CRITICAL-01 — Inventory source-of-truth inconsistency persists (`products.stock_quantity` still mutable + not transactionally synced to movements)
**Impact:** Inventory reports/alerts that read `products.stock_quantity` can drift from ledger movements.

**Evidence:**
- Direct mutation of `products.stock_quantity` still exists:
  - `app/Models/Product.php` lines **420–451** (`addStock()` / `subtractStock()`).
- Stock movement creation does **not** update cached `stock_quantity`:
  - `app/Repositories/StockMovementRepository.php` lines **112–156** (creates movement only).
- Services/controllers still read `products.stock_quantity` directly (example):
  - `app/Services/AutomatedAlertService.php` lines **30–77** (builds alerts using `$product->stock_quantity`).

**Fix direction**
- Either fully remove `stock_quantity` usage everywhere, or
- Make it a **strictly maintained cache** updated in the same transaction whenever a movement is written (centralized write path).

---

### STILL-V9-HIGH-04 — Branch scope still effectively disabled for many console/queue contexts (risk of cross-branch job behavior)
**Impact:** Queue workers/scheduled jobs (console mode) can run queries without a user context and therefore without branch filters, potentially operating across **all branches**.

**Evidence:**
- When `$user` is null, BranchScope only fail-closes for non-console; in console it returns without applying any filter:
  - `app/Models/Scopes/BranchScope.php` lines **94–103**.
- Queue listeners/jobs are `ShouldQueue` (console workers) and will commonly run without `auth()`.

**Fix direction**
- Make branch context explicit for jobs:
  - Always persist `branch_id` on the job payload and set a branch context before queries,
  - or fail-closed in console unless command is explicitly safe and branch context is set.
- Add integration tests for queued jobs to ensure branch isolation.

---

## Notes
- This report intentionally does **not** re-list items that were already fixed between v9 → v10.
