# APMO ERP v11 — Bug Report (New + Still-Unfixed)

Scan target: **apmoerpv11.zip** (code-level review). **Database migrations/seeders ignored** per request.

This report contains **ONLY**:
- **New bugs found in v11**, and
- **Bugs previously reported (v10/v9) that are still present in v11**

---

## Summary

- **New in v11:** 2
- **Still unfixed:** 3

---

## 1) Newly discovered bugs in v11

### V11-CRITICAL-01 — Warehouse queries use `status` as a DB column (but it is an accessor) → SQL errors

**Impact:** Any page/component that filters warehouses by `status='active'` will throw a SQL error (unknown column) and break warehouse-related screens (adjustments, transfers, movements).

**Root cause:** `Warehouse` model exposes `status` via `getStatusAttribute()` (computed from `is_active`), but there is no `status` column. Query builder `where('status', ...)` targets a DB column, not the accessor.

**Evidence:**

- `app/Models/Warehouse.php` lines **103–106**: `getStatusAttribute()` returns `'active'/'inactive'` from `is_active`.

- `app/Livewire/Warehouse/Adjustments/Form.php` line **175**: `Warehouse::...->where('status','active')`

- `app/Livewire/Warehouse/Movements/Index.php` line **100**: `Warehouse::...->where('status','active')`

- `app/Livewire/Warehouse/Transfers/Form.php` line **142**: `Warehouse::...->where('status','active')`


**Fix direction:** Replace `where('status','active')` with one of:
- `->where('is_active', true)` (recommended), or
- use the existing scope: `->active()` (if it exists and uses `is_active`).

---

### V11-HIGH-02 — InventoryController persists `products.stock_quantity` while dropping `warehouse_id`

**Impact:** When an API client updates stock for a **specific warehouse**, the system recalculates stock correctly for that warehouse, but then **stores product.stock_quantity using a calculation that ignores the warehouse**. This can overwrite the stored quantity with a branch-level (or mixed) value and cause wrong inventory in UI/reports.

**Evidence:**

- `app/Http/Controllers/Api/V1/InventoryController.php` line **108**: calculates `$oldQuantity` using `calculateCurrentStock($product->id, $warehouseId, $product->branch_id)`.

- Same controller line **142** (and **245**) recalculates using `calculateCurrentStock($product->id, null, $product->branch_id)` (warehouse dropped).

- Lines **145** (and **247**) persist: `forceFill(['stock_quantity' => $calculated])->save();`


**Fix direction:** Persist a value that matches your ERP’s stock dimension:
- If `products.stock_quantity` is **branch-level**, compute using a branch-aggregated sum via warehouses (no warehouse filter) intentionally and document it.
- If it is **warehouse-level**, you **must include `$warehouseId`** in the persisted calculation (or don’t persist at all; always compute from movements).

---

## 2) Previously reported bugs still not fixed in v11

### STILL-V10-CRITICAL-01 — Some automation/report paths still calculate stock globally (no branch/warehouse scoping)

**Impact:** Low-stock alerts / scheduled reports can be computed using **all branches’** stock movements, producing incorrect alerts and cross-branch leakage in a multi-branch ERP.

**Evidence:**

- `app/Services/WorkflowAutomationService.php` lines **22, 157**: uses `StockService::getStockCalculationExpression('products.id')` (global).

- `app/Services/WorkflowAutomationService.php` lines **33, 54, 170, 206, 224**: falls back to `StockService::getCurrentStock($product->id)` with **no branch/warehouse**.

- `app/Services/ScheduledReportService.php` line **117**: inventory low-stock filter uses a global subquery `SUM(quantity) FROM stock_movements WHERE product_id = products.id` (no branch/warehouse join).

- `app/Services/ScheduledReportService.php` line **117** also compares against `products.reorder_point` while the select uses `reorder_qty` (possible field mismatch).


**Fix direction:** Switch these paths to branch-aware expressions:
- Use `StockService::getBranchStockCalculationExpression(...)` (or `getCurrentStockForBranch`) and pass the correct branch id.
- For scheduled reports, decide whether the report is per-branch and add warehouse join/branch filter accordingly.

---

### STILL-V10-HIGH-02 — `StockMovementRepository::create()` computes stock_before/after using an unlocked SUM → incorrect under concurrency

**Impact:** With concurrent movements for the same product+warehouse, `stock_before`/`stock_after` can be wrong, which breaks audit trails and any logic relying on these fields.

**Evidence:**

- `app/Repositories/StockMovementRepository.php` line **157–162**: `$currentStock = ...->sum('quantity')` then sets `stock_before` and `stock_after`.


**Fix direction:** Calculate `stock_before/after` under a lock:
- Lock a per-(product_id,warehouse_id) row (e.g., a stock ledger row), or
- lock the warehouse/product row with `lockForUpdate()` and compute the sum consistently, or
- drop storing `stock_before/after` and compute on-demand for audits.

---

### STILL-V9-CRITICAL-01 — Inventory source-of-truth inconsistency: `products.stock_quantity` still mutated directly + clamped to 0

**Impact:** The codebase mixes two sources of truth:
- `stock_movements` (signed ledger), and
- `products.stock_quantity` (cached field).
This leads to mismatched inventory, especially with negative stock, transfers, and concurrent operations.

**Evidence:**

- `app/Models/Product.php` line **439**: `addStock()` increments `stock_quantity` directly.

- `app/Models/Product.php` line **459**: `subtractStock()` clamps to `max(0, ...)` which conflicts with other parts that preserve negative stock for auditing.


**Fix direction:** Pick one truth:
- Preferred for ERP: treat `stock_movements` as truth, and make `stock_quantity` either read-only cached value (updated consistently) or remove writes entirely.
- If you keep `stock_quantity`, update it transactionally alongside movement creation and **do not clamp** unless business rules require it.

---

## Notes
- Line numbers are based on the extracted `apmoerpv11` tree.
- DB schema was not validated (per request), so field-name mismatches are reported based on code consistency and model intent.
