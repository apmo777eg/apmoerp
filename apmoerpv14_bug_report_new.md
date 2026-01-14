# APMO ERP v14 — NEW Bugs Report (compared to v13 verified report)

**Input:** `/mnt/data/apmoerpv14.zip` (extracted to `/mnt/data/apmoerpv14/`)

**Rule:** This report contains **only**:
- **New bugs found in v14**, and/or
- **Bugs still existing** from the last verified report.

✅ The 3 items from the **v13 verified report** appear fixed in v14:
- Dashboard totals no longer sum DB column `grand_total` (now `total_amount`).
- No Livewire self‑closing component tags (`<livewire:... />`) found.
- `Product::addStock()` / `subtractStock()` no longer silently mutate cached stock (they now throw a guard exception).

---

## NEW-V14-CRITICAL-01 — Stock source-of-truth is inconsistent across the project

### What’s wrong
The project has clearly moved toward **`stock_movements`** as the source of truth (see `StockService`, `StockMovementRepository`, and comments in multiple places), *but* many Livewire dashboards, reports, analytics services, and exports still use **`products.stock_quantity`** in calculations and filters.

This creates multiple classes of critical problems:
1) **Wrong inventory numbers** whenever `stock_quantity` drifts from stock movements (or is never updated).
2) **Multi‑warehouse corruption**: some flows recompute stock for a specific warehouse and then store it back into **a single global `products.stock_quantity` field**, overwriting any previous warehouse’s “cached” value.
3) **Hard failures** if `stock_quantity` is absent/renamed in schema, because many queries use it in SQL (`whereColumn`, `sum(DB::raw(...))`, etc).

### Evidence (examples, not exhaustive)

#### (A) API inventory update overwrites global stock cache with a warehouse-specific value
- **File:** `app/Http/Controllers/Api/V1/InventoryController.php`
- **Lines:** 142–150
- **Problem:** Recalculates using `$warehouseId` then writes to `products.stock_quantity`, which is not warehouse-keyed.
```php
$calculated = $this->calculateCurrentStock($product->id, $warehouseId, $product->branch_id);
$freshProduct->forceFill(['stock_quantity' => $calculated])->save();
```

#### (B) API product create/update can set `stock_quantity` without creating stock movements
- **File:** `app/Http/Controllers/Api/V1/ProductsController.php`
- **Create lines:** 206–229
  - Always sets `$product->stock_quantity = $quantity` (line 211) but only creates a stock movement if `$warehouseId` is provided (lines 214–229).
- **Update lines:** 288–321
  - If `warehouse_id` is missing, it updates cached `stock_quantity` and does **not** create a stock movement.

This guarantees inconsistency for integrations that do not pass a warehouse id (StockService totals will be 0 while UI shows cached stock).

#### (C) Branch reports use `stock_quantity` in SQL
- **File:** `app/Livewire/Admin/Branch/Reports.php`
- **Lines:** 93–106
```php
'total_value' => ... COALESCE(default_price, 0) * COALESCE(stock_quantity, 0)
->whereColumn('stock_quantity', '<=', 'min_stock')
->where('stock_quantity', '<=', 0)
```

#### (D) Dashboard module stats use `stock_quantity`
- **File:** `app/Livewire/Dashboard/CustomizableDashboard.php`
- **Lines:** 245–255

#### (E) KPI dashboard service uses `stock_quantity`
- **File:** `app/Services/Analytics/KPIDashboardService.php`
- **Lines:** 96–127

#### (F) Admin inventory export uses `stock_quantity`
- **File:** `app/Http/Controllers/Admin/Reports/InventoryReportsExportController.php`
- **Lines:** 36–86

### Impact
- Inventory value, low-stock alerts, out-of-stock lists, analytics KPIs, exports, and store integrations can show **incorrect or contradictory numbers**.
- In a multi-warehouse setup, calling API stock update for Warehouse A will overwrite cached stock that might have been reflecting Warehouse B (or a branch total), causing random UI behavior and wrong reordering decisions.

### Suggested fix
Pick **one** consistent model and apply it end-to-end:
- If `stock_movements` is the truth:
  - Remove `stock_quantity` from business logic and SQL filters.
  - Replace stock calculations with `StockService` expressions (branch/warehouse scoped).
  - For performance, introduce a proper **per-warehouse stock cache table** (e.g., `product_warehouse_stocks`) and update it transactionally.
- If you must keep `products.stock_quantity`:
  - Define it clearly as *branch total only* (never per-warehouse), and enforce updates from movements (no direct writes from APIs).
  - Reject API stock operations that do not specify the warehouse (or derive it deterministically).

---

## NEW-V14-HIGH-02 — P&L and cashflow endpoints are financially incorrect (logic/finance bug)

### What’s wrong
The branch `pnl()` endpoint calculates profit as `sales - purchases`, and the cashflow endpoint uses `sales.paid_amount` and `purchases.paid_amount` directly. In ERP accounting, this is not a correct P&L or cashflow calculation; it ignores:
- COGS vs purchases timing
- operating expenses
- returns/credit notes
- payments ledger (if payments are in separate tables)
- journal entries / GL-based reporting

### Evidence
- **File:** `app/Http/Controllers/Branch/ReportsController.php`
- **Lines:** 60–77 (P&L) and 79–96 (cashflow)
```php
$sales = DB::table('sales')->sum('total_amount');
$purchases = DB::table('purchases')->sum('total_amount');
return ... round($sales - $purchases, 2);

$in = DB::table('sales')->sum('paid_amount');
$out = DB::table('purchases')->sum('paid_amount');
```

### Impact
Financial reports can be materially wrong, especially with partial payments, inventory accruals, and expenses not represented by purchases.

### Suggested fix
- Generate P&L and cashflow from the **general ledger/journal entries** (or at least payments tables), not raw sales/purchases sums.
- If you need a quick “gross margin proxy” endpoint, label it explicitly (e.g., `gross_margin_proxy`) and document assumptions.

---

## NEW-V14-HIGH-03 — SQLite weekly grouping is wrong (Sunday vs Monday) causing inconsistent analytics

### What’s wrong
`DatabaseCompatibilityService::weekTruncateExpression()` claims “start of week (Monday)”, but the SQLite expression returns a Sunday-based week start, while MySQL and Postgres are Monday-based.

### Evidence
- **File:** `app/Services/DatabaseCompatibilityService.php`
- **Lines:** 140–152
```php
// comment: start of week (Monday)
'sqlite' => "DATE({$column}, 'weekday 0', '-7 days')",
default => "DATE(DATE_SUB({$column}, INTERVAL WEEKDAY({$column}) DAY))",
```

### Impact
Weekly charts/totals (e.g., sales analytics trend) shift between DB engines, producing inconsistent results across environments.

### Suggested fix
For SQLite, use Monday alignment (e.g., `'weekday 1', '-7 days'` depending on desired behavior) and add tests comparing drivers.

---

## NEW-V14-MEDIUM-04 — Stock movement creation does not fail fast when warehouse row is missing

### What’s wrong
`StockMovementRepository::create()` tries to lock the warehouse row as a “lock anchor”, but if the warehouse id is invalid it silently continues (lock query returns null). The later `StockMovement::create()` could still write an orphaned record depending on DB constraints.

### Evidence
- **File:** `app/Repositories/StockMovementRepository.php`
- **Lines:** 143–147
```php
DB::table('warehouses')
  ->where('id', $data['warehouse_id'])
  ->lockForUpdate()
  ->first(); // result ignored
```

### Impact
Can create stock movements with invalid warehouse references in environments without strict FK constraints (or if constraints are temporarily disabled), which then breaks reporting joins.

### Suggested fix
If the warehouse lock returns null, throw immediately (domain exception): `Invalid warehouse_id`.

---

## NEW-V14-MEDIUM-05 — Inventory valuation reports use sell price × cached stock (misleading ERP valuation)

### What’s wrong
Several reports compute inventory value as `default_price * stock_quantity`. For an ERP, valuation should typically be based on cost (standard/avg/FIFO) and actual on-hand quantities (from movements), not sell price and cached quantities.

### Evidence
- **File:** `app/Livewire/Admin/Branch/Reports.php` (line ~99)
- **File:** `app/Livewire/Dashboard/CustomizableDashboard.php` (line ~249)

### Impact
Overstates inventory value and produces misleading KPIs.

### Suggested fix
Use cost-based valuation (e.g., `cost` / `standard_cost`) and movement-based stock totals (StockService branch expression).

---

## Notes
- I ran a full PHP syntax lint over all non-vendor PHP files; no syntax errors were detected.
- If you want, I can also output a *complete call-site map* of every `stock_quantity` usage with a suggested replacement (StockService branch/warehouse expression).
