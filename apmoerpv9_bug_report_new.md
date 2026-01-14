# APMO ERP v9 — Bug Report (New + Still-Unfixed)

Scan target: `apmoerpv9.zip` (Laravel 12 / Livewire 4)
Scope: **All project files** (code-level review). **Database migrations/seeders ignored** per request.

This report includes:
1) **Newly discovered bugs in v9** (not listed in the previous v8 report)
2) **Bugs from v8 report that are still not fixed**

---

## 1) Newly discovered bugs in v9

### V9-CRITICAL-01 — Inventory “source of truth” is inconsistent (stock_quantity vs stock_movements)
**Impact:** Inventory, alerts, reorder suggestions, and automation can be **wrong** (ERP integrity issue).  
You currently have two parallel stock systems:
- Some code uses **`stock_movements`** as the source of truth (correct ledger approach).
- Many other places still rely on **`products.stock_quantity`** (and even directly mutate it), **without consistently syncing it** with stock movements.

#### Evidence (examples)
- Product scopes & logic still use `stock_quantity` for stock status:
  - `app/Models/Product.php` (LowStock/OutOfStock/InStock) **lines ~273–299**, and adjustment methods **~395–453**.
- Reorder logic uses `stock_quantity`:
  - `app/Services/StockReorderService.php` **lines 30–58** (`whereRaw('stock_quantity <= reorder_point')`, etc.).
- Workflow automation/alerts use `stock_quantity`:
  - `app/Services/WorkflowAutomationService.php` **lines 18–35**.
- API creates/updates product stock by directly setting `stock_quantity` (no stock movement ledger entry):
  - `app/Http/Controllers/Api/V1/ProductsController.php` **lines 205–210** (`$product->stock_quantity = $quantity;`).

#### Why this is a bug
- Stock deduction/addition on sales/purchases is done through **stock movements** listeners/repository, not by updating `products.stock_quantity`.
- Any report/alert/query that still uses `stock_quantity` will drift from reality (especially with returns, adjustments, or concurrency).

#### Fix direction
Pick **one**:
1) Make `stock_movements` the source of truth and **remove / stop using** `stock_quantity` in queries, scopes, services, alerts, reorder logic.
2) If you must keep `stock_quantity`, then **strictly maintain it**:
   - Update it in the same DB transaction whenever a stock movement is created (centralized in `StockMovementRepository::create()`), and ensure all write paths (sales/purchase/returns/imports/API) go through that same mechanism.

---

### V9-CRITICAL-02 — Return refunds are linked to the wrong return entity (ReturnNote vs SalesReturn mismatch)
**Impact:** Refund records can become **orphaned/mislinked**, breaking refunds reporting + audit trail.

#### Evidence
- `app/Services/SaleService.php` creates a **ReturnNote** then creates **ReturnRefund** with `sales_return_id = $note->id`:
  - `app/Services/SaleService.php` **lines ~87–136**.
- `app/Models/ReturnRefund.php` clearly models `sales_return_id` as a relation to `SalesReturn`:
  - `app/Models/ReturnRefund.php` **lines 14–58** (`salesReturn(): belongsTo(SalesReturn::class)`).
- `ReturnNote` is explicitly a legacy/simple return model:
  - `app/Models/ReturnNote.php` **lines 10–40** (legacy note; advanced returns should use `SalesReturn`).

#### Why this is a bug
`ReturnRefund.sales_return_id` expects a `sales_returns.id`, but `SaleService` is passing a `return_notes.id`.  
Even if IDs overlap by chance, the relation is logically wrong and will break joins and dashboards.

#### Fix direction
- Either:
  - Refactor `SaleService::handleReturn()` to create a real `SalesReturn` and use `SalesReturnService` refund flow, **or**
  - Introduce a separate `ReturnNoteRefund` (or add `return_note_id` to refunds) and keep the systems separate.
- Avoid maintaining two parallel return subsystems unless you have a strict migration plan.

---

### V9-HIGH-03 — Sale returns still mutate `sales.paid_amount` even when refund is pending
**Impact:** Customer balance/aging/cashflow can become **incorrect**, and auditability is reduced.

#### Evidence
- `SaleService::handleReturn()` creates a refund record but still updates paid amount:
  - `app/Services/SaleService.php` **lines ~115–145** (creates `ReturnRefund` then `bcsub()` from `$sale->paid_amount`).

#### Why this is a bug
- A refund in **pending** status should not necessarily reduce what was “paid” historically.
- The correct approach is: payments/refunds should be derived from payment/refund ledgers (e.g., SalePayment + ReturnRefund completed), not by mutating a single aggregate field.

#### Fix direction
- Make `paid_amount` a derived value (or update it only when refund is **completed**, not pending).
- Ensure financial reports and statements read from payment/refund tables.

---

### V9-HIGH-04 — Branch scoping is disabled in console contexts (queue workers = console)
**Impact:** Potential **cross-branch data leakage** in jobs/queues/commands.

#### Evidence
- `BranchScope` returns early when no user AND running in console:
  - `app/Models/Scopes/BranchScope.php` **lines 94–103**.

#### Why this is a bug
Many ERP actions run via queue workers (which run in console mode). If those jobs query scoped models without setting branch context explicitly, they may operate on **all branches**.

#### Fix direction
- Ensure queued jobs set a branch context (e.g., store branch_id in job payload and set it in `BranchContextManager` before queries).
- Or make scoping fail-closed for queue workers unless an explicit override is set.

---

### V9-MEDIUM-05 — Unit conversion factor is not validated (0/negative factor breaks stock deduction)
**Impact:** Inventory can be over-sold or not deducted properly when UoM config is bad.

#### Evidence
- `UpdateStockOnSale` uses:
  - `conversion_factor ?? 1.0` and multiplies without validating it:
  - `app/Listeners/UpdateStockOnSale.php` **lines 27–33**.

#### Fix direction
- Validate `conversion_factor > 0` (and preferably > tiny epsilon), otherwise throw and stop the sale completion workflow.

---

## 2) Bugs from v8 report that are still not fixed

### STILL-V8-HIGH-U06 — Stock movement duplicate guard can drop legitimate lines (needs line-item uniqueness)
**Impact:** Incorrect inventory ledger if a sale/purchase has multiple lines with the same product & same quantity.

#### Evidence
- Sale stock movement duplicate check ignores `sale_item_id`:
  - `app/Listeners/UpdateStockOnSale.php` **lines 59–66**.
- Purchase stock movement duplicate check ignores `purchase_item_id`:
  - `app/Listeners/UpdateStockOnPurchase.php` **lines 37–44**.

#### Why still unfixed
Even with the improved check (warehouse + exact quantity), it can still treat a second legitimate line as “duplicate” and skip creating the movement.

#### Fix direction
- Include a stable line identifier in the uniqueness check:
  - For sales: `sale_item_id` (or at least `sale_item_id`-equivalent reference).
  - For purchases: `purchase_item_id`.
- Or enforce uniqueness at DB level with a composite unique key including a line-item reference.

---

### STILL-V8-HIGH-N06 — AR/AP aging logic still uses `paid_amount` aggregates instead of payment ledgers
**Impact:** Aging reports can be wrong when payments are tracked in payment tables (SalePayment/PurchasePayment/ReturnRefund).

#### Evidence
- AR aging:
  - `app/Services/FinancialReportService.php` **lines 267–320** (queries `paid_amount < total_amount` and computes outstanding using `paid_amount`).
- AP aging:
  - `app/Services/FinancialReportService.php` **lines 326–380** (same pattern).

#### Fix direction
- Calculate outstanding from payment tables (`SalePayment`, `PurchasePayment`) and subtract completed refunds (`ReturnRefund`).
- Keep `paid_amount` only as a cached value if needed, but validate it against the ledger.

---

## Notes
- I did **not** re-list bugs that were already fixed between v8 → v9.
- If you want, I can also generate **patch suggestions per bug** (exact code diffs) in the same order as this report.
