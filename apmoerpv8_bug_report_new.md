# APMO ERP — Bug Report (v8)

- **Archive scanned:** `apmoerpv8.zip`
- **Baseline for comparison:** `apmoerpv7_bug_report_new.md`
- **Date:** 2026-01-14
- **Framework:** Laravel ^12, Livewire ^4 beta, PHP ^8.2 (from composer.json)
- **Scope:** Full code scan (App / Routes / Config). **Database + seeders ignored**.

> This report contains **ONLY**:
> 1) **New bugs found in v8**, and
> 2) **Bugs reported before that are still not fixed**.
>
> Fixed items are intentionally omitted.

---

## Summary

- **CRITICAL:** 4
- **HIGH:** 7
- **MEDIUM:** 1

---

## New bugs in v8


### V8-CRITICAL-N01 — Super Admin branch access returns empty results (BranchContextManager empty-array sentinel conflicts with BranchScope)

**Severity:** CRITICAL


**Files / lines**

- `app/Services/BranchContextManager.php` (L109–L120)

- `app/Models/Scopes/BranchScope.php` (L113–L127)


**What’s wrong**

- `BranchContextManager::getAccessibleBranchIds()` يرجّع `[]` للـ Super Admin بمعنى “بدون فلترة”. لكن `BranchScope` يفسّر `count([]) === 0` على إنه “no access” ويضيف شرط مستحيل (empty result set).


**Impact**

- الـ Super Admin قد يرى **0 سجلات** في الموديلات التي تُطبق عليها BranchScope (Sales/Purchases/Customers…)، أو سلوك متناقض بين الشاشات.


**Suggested fix**

- غيّر البروتوكول: 
  - إمّا `getAccessibleBranchIds()` يرجّع قيمة sentinel واضحة لـ “ALL” (مثلاً `null` أو `['*']`)،
  - أو اجعل `BranchScope` يفحص `BranchContextManager::isSuperAdmin($user)` ويعمل `return;` بدون فلترة.
  - لازم تميّز بين “no access” و “all access” (عدم استخدام نفس القيمة `[]`).

---

### V8-HIGH-N02 — Non-atomic code/reference generation using count()+1 (race condition + duplicates across branches)

**Severity:** HIGH


**Files / lines**

- `app/Livewire/Hrm/Shifts/Form.php` (L86–L90)

- `app/Livewire/Manufacturing/WorkCenters/Form.php` (L95–L99)

- `app/Livewire/Projects/Form.php` (L99–L103)

- `app/Livewire/Warehouse/Warehouses/Form.php` (L68–L72)

- `app/Models/GoodsReceivedNote.php` (L46–L50)

- `app/Models/Project.php` (L66–L70)

- `app/Models/PurchaseRequisition.php` (L34–L38)

- `app/Models/SupplierQuotation.php` (L38–L42)


**What’s wrong**

- عدة موديلات/Forms بتولّد `reference_number` / `code` باستخدام `whereDate(...)->count() + 1` بدون lock/transaction. غالبًا مفيش branch filter داخل العدّ.


**Impact**

- Duplication في أرقام المستندات (GRN/PRQ/SQ/…)، وده بيكسر الترابط في ERP (بحث/تكامل/تقارير) خصوصًا مع تزامن عمليات أو تعدد فروع.


**Suggested fix**

- استخدم counter per-branch داخل transaction مع `lockForUpdate()`، أو sequence/UUID، أو unique index + retry loop. وأضف `branch_id` في العدّ لو الأرقام per-branch.

---

## Still not fixed (from v7 and earlier)


### STILL-V7-CRITICAL-U02 — Stock source-of-truth still inconsistent (products.stock_quantity vs stock_movements)

**Severity:** CRITICAL


**Files / lines**

- `app/Services/Dashboard/DashboardDataService.php` (L196–L219)


**What’s wrong**

- الـ dashboard/alerts مازالت تعتمد على `products.stock_quantity`، بينما حساب المخزون في خدمات أخرى يعتمد على `stock_movements` (SUM(quantity)).


**Impact**

- Low-stock alerts + dashboards + أي منطق يعتمد على stock_quantity ممكن يكون غلط (مخاطرة مالية وتشغيلية).


**Suggested fix**

- وحّد المصدر: إمّا احسب دائمًا من stock_movements، أو اجعل stock_quantity مشتق ومُحدث بشكل Transactional/trigger وحدّد بوضوح “source of truth”.

---

### STILL-V7-CRITICAL-U03 — Accounting entries can still be unbalanced when shipping_amount != 0 (Sale/Purchase journals)

**Severity:** CRITICAL


**Files / lines**

- `app/Services/AccountingService.php` (L103–L168)

- `app/Services/AccountingService.php` (L173–L273)


**What’s wrong**

- قيود البيع/الشراء تقيد subtotal/tax/discount، لكن لا تضيف أي line لـ `shipping_amount` (موجود في Sale/Purchase). ده يسبب فرق في التوازن يساوي shipping_amount.


**Impact**

- Journal entries غير متوازنة أو posted بشكل خاطئ → تقارير مالية/GL غير صحيحة.


**Suggested fix**

- أضف line واضح لـ shipping (Shipping Income/Expense أو Clearing) بحيث الديبت=الكريدت دائمًا، واستخدم bcmath/decimal-safe math.

---

### STILL-V7-CRITICAL-U04 — Sale return still mutates paid_amount without creating a refund/payment record

**Severity:** CRITICAL


**Files / lines**

- `app/Services/SaleService.php` (L110–L124)


**What’s wrong**

- `handleReturn()` يقلل `$sale->paid_amount` مباشرة بعد حساب refund بدون إنشاء Refund/Payment entity أو FinancialTransaction.


**Impact**

- Audit trail ناقص، وممكن cashflow/aging/income التقارير تطلع غلط، وصعب reconciliation.


**Suggested fix**

- سجّل refund ككيان مستقل (ReturnRefund/Payment) + قيد محاسبي/ledger، وخلي paid_amount محسوب من payment records وليس قيمة يتم تعديلها يدويًا.

---

### STILL-V7-HIGH-U05 — External order ID still inconsistent (API uses reference_number; store sync uses external_reference)

**Severity:** HIGH


**Files / lines**

- `app/Http/Controllers/Api/V1/OrdersController.php` (L138–L178)

- `app/Services/Store/StoreSyncService.php` (L313–L338)


**What’s wrong**

- Orders API يضع external_id في `reference_number` بينما `StoreSyncService` يستخدم `external_reference` في الـ idempotency والربط.


**Impact**

- Duplicate orders / فشل update لأن نفس external id مخزن في عمود مختلف حسب القناة.


**Suggested fix**

- وحّد الحقل للـ external ids (يفضّل external_reference) واجعل reference_number داخلي فقط، وعدّل كل lookups/idempotency accordingly.

---

### STILL-V7-HIGH-U06 — Stock movement duplicate guard still too coarse and can skip legitimate movements

**Severity:** HIGH


**Files / lines**

- `app/Listeners/UpdateStockOnSale.php` (L54–L79)

- `app/Listeners/UpdateStockOnPurchase.php` (L21–L46)


**What’s wrong**

- فحص duplicate يعتمد على reference_id + product_id + sign(quantity) فقط، بدون warehouse_id/line_id/qty exact/uom، فبيمنع تسجيل movements صحيحة.


**Impact**

- مخزون غير صحيح، missing movements، وتقارير المخزون والقيمة غلط.


**Suggested fix**

- اجعل uniqueness على مستوى line (sale_item_id/purchase_item_id) + warehouse_id + movement_type + qty exact. ويفضل unique index في DB.

---

### STILL-V7-HIGH-U07 — Voiding a sale still does not reverse stock/accounting

**Severity:** HIGH


**Files / lines**

- `app/Services/SaleService.php` (L134–L154)


**What’s wrong**

- `voidSale()` يغيّر status فقط بدون reverse stock_movements وبدون عمل reversal للقيود/المعاملات المالية.


**Impact**

- بيانات ERP غير متسقة: sale void لكن المخزون/المحاسبة مازالت متأثرة.


**Suggested fix**

- نفّذ void flow كامل: reverse movements + reversal journal entry + ربطه بالسيل الأصلي.

---

### STILL-V7-HIGH-U08 — Purchase payments still not recorded as payment entities (only mutates paid_amount)

**Severity:** HIGH


**Files / lines**

- `app/Services/PurchaseService.php` (L204–L249)


**What’s wrong**

- `PurchaseService::pay()` يزيد `paid_amount` ويحدّث `payment_status` بدون إنشاء Payment/FinancialTransaction record.


**Impact**

- Audit trail ناقص + cashflow/aging/GL تقارير غير دقيقة.


**Suggested fix**

- أضف كيان PurchasePayment/FinancialTransaction + قيود محاسبية، واجعل paid_amount محسوبًا من الدفعات.

---

### STILL-V7-HIGH-N06 — AR/AP aging reports still use paid_amount (ignores payment records / refunds)

**Severity:** HIGH


**Files / lines**

- `app/Services/FinancialReportService.php` (L267–L347)


**What’s wrong**

- Aging يعتمد على `paid_amount < total_amount` ثم يحسب outstanding من paid_amount، بدون payments/ledger.


**Impact**

- Aging خاطئ بعد partial payments/refunds/manual edits → مخاطرة مالية كبيرة.


**Suggested fix**

- احسب paid/outstanding من payment tables أو من GL lines، أو استخدم summary view.

---

### STILL-V7-HIGH-N07 — StockService SUM-based stock calculation is not concurrency-safe (stock_before/after can be wrong)

**Severity:** HIGH


**Files / lines**

- `app/Services/StockService.php` (L20–L40)

- `app/Services/StockService.php` (L135–L170)


**What’s wrong**

- `getCurrentStock()` يعمل SUM بدون locking، و`adjustStock()` يحسب stock_before ثم ينشئ movement. عمليتان متزامنتان قد تسجلان stock_before/after غلط.


**Impact**

- عدم دقة المخزون، وظهور قيم stock_after خاطئة، وممكن السماح بخروج مخزون أكثر من المتاح في لحظات التزامن.


**Suggested fix**

- Locking strategy (ledger table per product+warehouse) أو SELECT ... FOR UPDATE على نطاق مناسب + retry.

---

### STILL-V7-MEDIUM-N08 — BankingService still returns float balances from decimal math (rounding risk)

**Severity:** MEDIUM


**Files / lines**

- `app/Services/BankingService.php` (L141–L156)

- `app/Services/BankingService.php` (L239–L249)


**What’s wrong**

- تستخدم bcmath داخليًا ثم تُرجع float. ده ممكن ينتج فروق rounding في التقارير مع كثرة العمليات.


**Impact**

- فروق صغيرة في balances/reconciliation وتقارير مالية.


**Suggested fix**

- ارجع string decimal أو Decimal DTO للتقارير، أو اجعل cast لمرحلة العرض فقط.

---
