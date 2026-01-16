# APMO ERP (v28) — Bugs Report (NEW + Still-Unfixed)

> **Scope:** Deep code review of the v28 ZIP (code only). **Database/seeders ignored** as requested.
>
> This report contains **ONLY**:
> - **Newly found bugs** in v28, and/or
> - **Bugs that are still present** (not fixed yet)
>
> It also includes a short **regression verification** section to confirm that the most important v27 issues are truly fixed.

---

## ملخص سريع (أهم ما ستجده في التقرير)

1) **أخطر مشكلة حالياً (Transfers):** عند استلام التحويل (Receive) يتم **تعديل كل سجلات الـ in_transit إلى received** بدون مراعاة الاستلام الجزئي، وهذا قد يؤدي إلى **اختفاء/ضياع مخزون (vanishing stock)** أو عدم اتزان مخزون “في الطريق”.

2) **تحويلات المخزون (StockTransfer):** حركات المخزون الناتجة عن التحويلات يتم إنشاؤها بدون `reference_type/reference_id` → صعب جداً تتبعها في التقارير والمراجعة (Audit) وممكن تؤثر على أي منطق يعتمد على الـ reference.

3) **Banking (دقة مالية):** `BankingService` يستخدم decimal:4 في رصيد الحساب، لكن يحسب book balance / reconciliation scale=2 ويحوّل الفرق لـ float → **ممكن يحصل drift/rounding** في التوافق مع كشف البنك.

4) **Livewire:** المشروع مازال على `v4.0.0-beta.5` داخل `composer.lock`، بينما آخر stable (حالياً) `v4.0.1` — مطلوب ترقية فعلية حتى تكون “على الـ latest”.

---

## Environment snapshot (from `composer.lock`)

- `laravel/framework`: `v12.44.0`  
  Evidence: `composer.lock` lines 1606–1607
- `livewire/livewire`: `v4.0.0-beta.5`  
  Evidence: `composer.lock` lines 2636–2637

---

## Livewire upgrade note (required: v4.0.1 latest)

**Current state (v28):** you are **NOT** on Livewire stable yet. The lockfile still pins **`v4.0.0-beta.5`**.

**To actually upgrade to Livewire `v4.0.1` (stable):**
- Update `composer.json` requirement from `v4.0.0-beta.5` (or any beta) to `^4.0.1`.
- Run: `composer update livewire/livewire`.
- Re-test all Livewire components (especially file uploads, pagination, validation, and JS hooks), because stable releases may include breaking fixes compared to beta.

---

# NEW + STILL-UNFIXED BUGS (v28)

## V28-CRITICAL-01 — Receiving a transfer can “delete” in-transit stock on partial receipt

**Category:** Inventory / Transfers / ERP consistency  
**Severity:** CRITICAL

### Why this is a bug
In `receiveTransfer()`, the code marks **all** `InventoryTransit` rows for the product (and the transfer) as `received` regardless of:
- how many units were shipped,
- how many units were received,
- whether receipt is partial.

If partial receipt happens (e.g., shipped 100, received 60), the remaining 40 units should remain `in_transit` (or be handled explicitly), otherwise the system loses track of them.

### Evidence
- `app/Services/StockTransferService.php`
  - Builds `$qtyReceived` and `$qtyDamaged`, then:
  - Fetches transit records and marks them all received:
    - Lines 360–389 (especially 381–389)

- `app/Models/InventoryTransit.php`
  - `markAsReceived()` only updates status (no quantity reconciliation):
    - lines 113–116

### Impact
- “Vanishing stock” in logistics workflows.
- Incorrect in-transit dashboards/reports.
- Financial valuation drift if inventory-in-transit is part of valuation.

### Fix direction
- Make receipt **quantity-aware**:
  - Either split transit records and only mark the received quantity as received.
  - Or store received_quantity on transit rows and keep remainder active.
- Add database-level consistency checks (optional but recommended): ensure `sum(in_transit.quantity)` per transfer item aligns with `(qty_shipped - qty_received)`.

---

## V28-HIGH-02 — Missing validation: `qty_damaged` can exceed `qty_received`

**Category:** Inventory / Data integrity  
**Severity:** HIGH

### Why this is a bug
In `receiveTransfer()`, you validate both fields as `min:0` but you do **not** enforce:
- `qty_damaged <= qty_received`

Then you compute:
- `$qtyGood = $qtyReceived - $qtyDamaged`

If damaged > received, you store inconsistent item state and can hide errors (because good stock is only added when `$qtyGood > 0`).

### Evidence
- `app/Services/StockTransferService.php`
  - Validation: lines 323–330
  - Computation without guard: lines 360–372

### Impact
- Incorrect transfer KPIs (received vs damaged).
- Potential downstream logic bugs (completion rules, accounting).

### Fix direction
- Add validation rule: `lte:items.*.qty_received` or a custom validator per item.
- Reject inconsistent payloads with 422 before updating DB.

---

## V28-HIGH-03 — Stock movements for StockTransfer are not linked to their transfer (missing reference fields)

**Category:** Auditability / Reporting / Inventory traceability  
**Severity:** HIGH

### Why this is a bug
`StockService::adjustStock()` supports `referenceId` and `referenceType`, but `StockTransferService` calls it with both set to `null` for:
- Transfer out (shipping)
- Transfer in (receiving)
- Transfer damage adjustments

So stock movements exist, but are not traceable back to the originating transfer.

### Evidence
- `app/Services/StockTransferService.php`
  - Shipping transfer out: lines 266–277 (referenceId/referenceType are null at 273–275)
  - Receiving transfer in: lines 399–410 (null at 406–408)
  - Damaged adjustment: lines 416–429 (null at 424–426)

### Impact
- Reports that group by `reference_type` (or try to trace documents) won’t work.
- Hard to audit, reverse, or reconcile stock movements.
- Support/debugging becomes difficult (“where did this movement come from?”).

### Fix direction
- Pass a consistent reference, e.g.:
  - `referenceType = StockTransfer::class` (or a string key like `'stock_transfer'`)
  - `referenceId = $transfer->id`
- Optionally link at the **item** level: reference to `stock_transfer_items.id` for per-line traceability.

---

## V28-MEDIUM-04 — Banking decimal precision is inconsistent (decimal:4 vs bc scale=2, plus float casts)

**Category:** Finance / Accounting / Precision  
**Severity:** MEDIUM (can become HIGH in multi-currency / high-volume)

### Why this is a bug
`BankAccount` balances are cast as `decimal:4`, but `BankingService` calculates:
- `difference` with **scale 2**
- `calculateBookBalanceAt()` using **scale 2**
- then casts the difference to **float**

This can introduce rounding drift and reconciliation differences, especially if your amounts can have 3–4 decimals (common in some tax/currency setups).

### Evidence
- `app/Models/BankAccount.php`
  - `opening_balance` and `current_balance` are `decimal:4`.

- `app/Services/BankingService.php`
  - Updates current balance using scale=4: lines 31–37
  - But computes reconciliation diff with scale=2 and casts to float: lines 60–70
  - Book balance calculation uses bcadd/bcsub scale=2: lines 142–149
  - Reconciliation totals also use scale=2: lines 96–110

### Impact
- “Balanced” reconciliation might fail (or pass) incorrectly.
- Subtle rounding drift accumulates over time.

### Fix direction
- Standardize money math to **scale=4** everywhere for these columns.
- Keep difference as **string decimal** in DB, or at least avoid float casts internally.

---

## V28-MEDIUM-05 — `startReconciliation()` accepts `float $statementBalance` (precision loss risk)

**Category:** Finance / Input validation / Precision  
**Severity:** MEDIUM

### Why this is a bug
`startReconciliation()` signature uses `float $statementBalance`, then it is cast to string for bcmath. If the input came from UI or API as a decimal string, converting it to float first can lose precision.

### Evidence
- `app/Services/BankingService.php`
  - Method signature: line 49–54
  - bcmath usage: line 60

### Fix direction
- Accept `string $statementBalance` (validated with regex/decimal rules) and store as string-decimal.

---

## V28-MEDIUM-06 — Two transfer systems exist side-by-side (risk of inconsistent behavior)

**Category:** ERP consistency / Domain duplication  
**Severity:** MEDIUM

### Why this is a bug (from an ERP coherence perspective)
The codebase contains:
- A “simple” transfer module using `App\Models\Transfer` + Livewire `Warehouse/Transfers/*`.
- A “rich” stock transfer module using `StockTransfer`, `StockTransferService`, `InventoryTransit`, etc.

If both are active in production:
- users may create transfers in one module and expect the other’s reports to reflect them,
- stock movements and audit links differ,
- business rules can drift.

### Evidence
- `app/Livewire/Warehouse/Transfers/Index.php` uses `App\Models\Transfer`.
- `app/Services/StockTransferService.php` uses `StockTransfer` + `InventoryTransit`.

### Fix direction
- Decide a single “source of truth” transfer system.
- If one is legacy, hide it behind feature flags / permissions and plan a migration path.

---

# Regression verification (key v27 issues that appear fixed in v28)

> This section is **not** listing bugs. It confirms the most important previous report items are no longer present (based on code inspection).

1) **Branch context / route binding mismatch fixed**
- Middleware now correctly handles Branch model vs ID and sets BranchContextManager explicitly.
  - Evidence: `app/Http/Middleware/SetBranchContext.php` lines 37–45 and 110–116.
  - Middleware alias exists: `bootstrap/app.php` lines 101–117.

2) **Transfer/Return costing support added to stock movements**
- `StockService::adjustStock()` now supports `unit_cost` and `userId`.
  - Evidence: `app/Services/StockService.php` lines 225–292 (notably `unit_cost` at 285 and `userId` at 246–276).

3) **Bank reconciliation difference calculation fixed in Livewire wizard**
- Difference is now based on matched total (not raw system balance).
  - Evidence: `app/Livewire/Banking/Reconciliation.php` lines 242–281 (difference assignment at 280).

4) **Sales return now uses proper costing + stock movements repository**
- Sale returns restock uses the repository with `unit_cost` sourcing.
  - Evidence: `app/Services/SaleService.php` lines 167–189 (repository create includes `unit_cost` at 185).

---

## Notes
- This report is based on the code as shipped in the v28 ZIP. Some issues may depend on DB constraints (nullable vs not-null) or runtime configs.
- If you want, I can also add a “test checklist” (manual scenarios) to validate these fixes end-to-end after you implement them.
