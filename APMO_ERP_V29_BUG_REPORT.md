# APMO ERP (v29) — New / Unfixed Bugs Report

> **Scope:** Static code review (no DB/seeders). Whole project scanned after extracting `apmoerpv29.zip`.
>
> **Output rule:** This report contains **ONLY**:
> 1) **New bugs** found in v29, and
> 2) **Any remaining/unfixed bugs** that still exist.
>
> It intentionally **does not** list bugs that are already fixed.


---

## Quick summary (ملخص سريع)

- ✅ **Livewire upgraded to `v4.0.1` (latest)** and Laravel is `^12.0`.
- ✅ **Previously reported critical warehouse transfer receipt issues appear fixed** in `StockTransferService` (partial receive logic, qty_damaged validation, reference_id/type propagation).
- ⚠️ **The biggest remaining finance/ERP risk in v29:** returned items are restocked using **selling price** (unit_price) instead of **cost** (cost_price), which can **inflate inventory valuation** and distort COGS.
- ⚠️ **Store order sync still creates Sales with missing ERP-critical fields** (notably `created_by`, and SaleItem missing `warehouse_id` / `cost_price`), which can break audit/accounting consistency.

---

## Version verification (Laravel 12 + Livewire 4.0.1)

- `composer.json` requires Laravel `^12.0` and Livewire `^4.0.1`. (See `composer.json` lines **13–17**.)
- `composer.lock` confirms **Livewire `v4.0.1`** is installed.

---

## Findings (New + Remaining Bugs)

### V29-CRIT-01 — Inventory valuation wrong on Sales Returns (uses selling price instead of cost)

- **Severity:** CRITICAL (Finance + Inventory valuation)
- **Where:** `app/Services/SalesReturnService.php`
- **Lines:** 338–357
- **Problem:** Returned stock is restocked with `unitCost = unit_price` (selling price). This contradicts proper ERP valuation, where inventory should be valued at **cost** (e.g., `SaleItem.cost_price` or product standard cost).
  - Current code: `$unitCost = $item->unit_price ?? $item->saleItem?->unit_price ?? null;` then passed to `adjustStock(...)`. (SalesReturnService lines **338–357**)
- **Impact:**
  - Inflated inventory valuation
  - Incorrect COGS / gross margin
  - Financial reports become unreliable
- **Recommended fix:**
  1) Store `unit_cost` on `SalesReturnItem` when creating the return (preferably from `SaleItem.cost_price`).
  2) In restock, use: `saleItem->cost_price ?? product->cost` (NOT `unit_price`).

---

### V29-HIGH-02 — SalesReturnItem precision + missing unit_cost field cause rounding/valuation drift

- **Severity:** HIGH (Finance consistency + numeric precision)
- **Where:** `app/Models/SalesReturnItem.php`
- **Lines:** 32–58
- **Problems:**
  1) Quantities are cast to `decimal:3` while core inventory uses `decimal:4` widely (e.g., `SaleItem.quantity` is `decimal:4`). (SalesReturnItem lines **32–41**)
  2) Prices are cast to `decimal:2` (unit_price/discount/tax/line_total). If your system uses `decimal:4` amounts elsewhere, this creates rounding drift.
  3) No `unit_cost` field exists on the return item, forcing services to guess the valuation cost later.
- **Impact:**
  - Rounding errors on partial quantities/returns
  - Harder reconciliation between StockMovements, Sales, Returns, and Accounting
- **Recommended fix:**
  - Align decimals with the rest of the ERP (typically qty `decimal:4`, monetary `decimal:4` or enforce system-wide `decimal:2` strictly).
  - Add `unit_cost` column/cast so valuation is deterministic.

---

### V29-HIGH-03 — Store sync creates Sales missing ERP-critical fields (created_by) and SaleItems missing warehouse_id/cost_price

- **Severity:** HIGH (Audit + accounting coherence)
- **Where:** `app/Services/Store/StoreSyncService.php`

**A) Shopify order sync**
- **Lines:** 399–455
- **Bug:** Comment says “include created_by” but **it is not set** in the `Sale::create([...])` payload. (StoreSyncService lines **399–413**)
- **Also:** `sale->items()->create([...])` does not set `warehouse_id` nor `cost_price`. (StoreSyncService lines **447–454**)

**B) WooCommerce order sync**
- **Lines:** 565–621
- **Bug:** Same: `Sale::create([...])` without `created_by`. (StoreSyncService lines **565–579**)
- **Also:** SaleItem missing `warehouse_id` / `cost_price`. (StoreSyncService lines **613–620**)

- **Impact:**
  - Sales records without `created_by` can break audits, permissions, “who created this”, and some accounting logic.
  - Missing `cost_price` weakens COGS accuracy; missing `warehouse_id` on items can break per-warehouse analytics and reconciliation.
- **Recommended fix:**
  - Decide a deterministic system user for integrations (e.g., `integration_user_id`), and always populate `created_by`.
  - Populate `warehouse_id` on each sale item.
  - Populate `cost_price` for sale items using product cost at the time of sale (or the costing service).

---

### V29-MED-04 — Auth dependency inside Model method (Transfer::receive)

- **Severity:** MEDIUM (stability + architecture)
- **Where:** `app/Models/Transfer.php`
- **Lines:** 184–194
- **Problem:** `receive()` writes `received_by => $userId ?? auth()->id()`. (Transfer.php lines **184–194**)
  - If executed from CLI/queue/context without authenticated user, `auth()->id()` can be null.
- **Impact:**
  - Potential DB constraint violations if `received_by` is NOT NULL
  - Hidden coupling: Model depends on HTTP auth context
- **Recommended fix:**
  - Require `$userId` explicitly at service/controller layer and pass it to the model method.
  - Avoid calling `auth()` from Models.

---

### V29-MED-05 — Duplicate transfer systems remain (Transfer vs StockTransfer) → risk of inconsistent business rules

- **Severity:** MEDIUM (ERP coherence)
- **Where:**
  - `app/Models/Transfer.php` (basic transfers) — comment explicitly states there are 2 systems. (Transfer.php lines **13–17**)
  - `app/Models/StockTransfer.php` (advanced transfers)
  - Livewire UIs exist for both (`app/Livewire/Warehouse/Transfers/...` and `app/Livewire/Warehouse/StockTransfers/...`)
- **Problem:** Two overlapping workflows for the same real-world process increases risk of:
  - stock movement mismatch
  - approvals bypassed
  - reporting fragmentation
- **Recommended fix:**
  - Consolidate: either deprecate `Transfer` or make it a thin wrapper over `StockTransfer`.
  - Ensure all reports and inventory adjustments use a single source of truth.

---

### V29-MED-06 — Cashflow summary uses 2-decimals while account balance uses 4-decimals

- **Severity:** MEDIUM (finance precision)
- **Where:** `app/Services/BankingService.php`
- **Lines:** 161–190
- **Problem:**
  - Balance computation uses `bcadd/bcsub` scale **4** (BankingService lines **148–156**)
  - Cashflow summary uses scale **2** (BankingService lines **174–182**)
- **Impact:**
  - If your system stores bank transaction amounts with 3–4 decimals (or uses FX), cashflow totals can drift vs reconciliation/balances.
- **Recommended fix:**
  - Use consistent scale (prefer `4` internally) and round only at presentation.

---

### V29-LOW-07 — `minimum-stability: beta` is still enabled (risk of pulling beta dependencies)

- **Severity:** LOW/MED (release safety)
- **Where:** `composer.json`
- **Lines:** 99–100
- **Problem:** `"minimum-stability": "beta"` can allow beta packages to be installed if constraints permit. (composer.json lines **99–100**)
- **Impact:**
  - Unexpected package upgrades, instability in production
- **Recommended fix:**
  - Prefer `stable` unless you **must** consume a beta-only package; pin exact versions when necessary.

---

### V29-LOW-08 — InventoryTransit lacks received timestamp (audit gap)

- **Severity:** LOW (audit/reporting)
- **Where:** `app/Models/InventoryTransit.php`
- **Lines:** 110–116
- **Problem:** `markAsReceived()` only sets `status` and does not store a `received_at` timestamp. (InventoryTransit lines **110–116**)
- **Impact:**
  - Harder auditing and reporting (lead times, SLA, transit aging)
- **Recommended fix:**
  - Add `received_at` field and set it when receiving.

---

## Notes

- This is a **static** review; the highest confidence bugs are those that are clearly incorrect under ERP rules (valuation/cost) or obviously inconsistent with the project’s own schema contracts.
- If you want, I can also generate a **targeted test plan** (manual + automated) to reproduce each bug in UI/API and verify fixes.

