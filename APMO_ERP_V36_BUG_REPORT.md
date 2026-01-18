# APMO ERP — Bug Report (v36)

**Project Version:** apmoerpv36  
**Laravel:** v12.44.0  
**Livewire:** v4.0.1 (Upgraded) ✅

> هذا التقرير يحتوي فقط على:
> - Bugs جديدة تم اكتشافها في v36
> - Bugs قديمة ما زالت موجودة (غير مُصلحة بعد)
> ولا يحتوي على Bugs قديمة تم إصلاحها بالفعل.

---

## ✅ STILL UNFIXED (Old Bugs that remain)

### 1) (MED) Services Layer uses `abort_if()` (Architecture + Behavior Bug)
**Type:** Logic/Architecture  
**Severity:** Medium  
**File:** `app/Services/SalesReturnService.php`  
**Evidence:** استخدام abort_if داخل Service في عدة مواضع (مثل 64, 94, 146, 207, 223, 284).  
**Impact:**  
- صعوبة اختبار الـ service  
- كسر الفصل بين طبقة الـ Domain وطبقة HTTP  
- السلوك يصبح مرتبطًا بـ HTTP status codes بدل Exceptions  
**Recommendation:** استبدال abort_if بـ DomainException/ValidationException مع handling في Controller/Livewire.

---

### 2) (MED) Inventory valuation inconsistency (in-transit included in one place, excluded elsewhere)
**Type:** Finance/Logic  
**Severity:** Medium  
**Files:**  
- `app/Services/CostingService.php` (lines ~259–335) — includes in-transit inventory in total value  
- `app/Livewire/Admin/Branch/Reports.php` (lines ~95–114) — calculates inventory value from `stock_quantity` only  
**Impact:**  
- تقارير المخزون/الأصول قد لا تتطابق بين الشاشات  
- قد يظهر فرق في “قيمة المخزون” حسب المكان الذي يعرضها  
**Recommendation:**  
- توحيد تعريف قيمة المخزون (Include in-transit everywhere OR nowhere)  
- أو توفير Toggle واضح/سياسة واحدة.

---

## 🆕 NEW BUGS FOUND (v36)

### 3) (CRIT) P&L report calculates COGS using Purchases Total (Wrong Accounting)
**Type:** Finance/Critical Logic  
**Severity:** Critical  
**File:** `app/Http/Controllers/Admin/ReportsController.php` (financePnl, lines ~178–226)  
**Problem:**  
- `cost_of_goods` محسوبة كـ مجموع مشتريات الفترة (`purchases.total_amount`)  
- وهذا **ليس COGS** في نظام ERP قائم على المخزون — COGS يجب أن يأتي من تكلفة الأصناف المباعة (sale_items cost / costing batches) وليس من الشراء داخل نفس الفترة.  
**Impact:**  
- تقرير الأرباح والخسائر غير صحيح ماليًا  
- أي تغيّر في المخزون (زيادة/نقص/ترحيل) يجعل الأرباح مضللة جدًا  
**Fix Direction:**  
- حساب COGS من تكلفة البيع الفعلية (مثل stock batches / unit_cost per sale item / inventory costing rules).

---

### 4) (HIGH) Cashflow report ignores transaction types + uses float math
**Type:** Finance/Logic  
**Severity:** High  
**File:** `app/Http/Controllers/Admin/ReportsController.php` (financeCashflow, lines ~233–262)  
**Problem:**  
- يحسب inflows فقط من `type=deposit`  
- ويحسـب outflows فقط من `type=withdrawal`  
- ويتجاهل أنواع مهمة مثل: transfers, fees, interest, adjustments … (حسب تصميم `bank_transactions`)  
- ويستخدم float في الحساب (`$netCashflow = $inflows - $outflows`)  
**Impact:**  
- Cashflow غير دقيق  
- احتمال فرق بسيط/كبير في أرقام التقرير  
**Fix Direction:**  
- استخدام mapping واضح لأنواع inflow/outflow أو عمود direction  
- واستخدام bcmath داخليًا.

---

### 5) (HIGH) Aging report relies on `paid_amount` field while other services calculate outstanding from payments ledger
**Type:** Finance/Data Integrity  
**Severity:** High  
**Files:**  
- `app/Http/Controllers/Admin/ReportsController.php` (financeAging, lines ~269–310) — uses `paid_amount` column  
- `app/Services/FinancialReportService.php` (lines ~270+) — explicitly avoids `paid_amount` ويحسب من payments ledger + refunds  
**Impact:**  
- تناقض بين تقارير الإدارة (Admin ReportsController) وتقارير FinancialReportService  
- إذا حصل أي سيناريو لا يحدّث paid_amount (imports/edge cases/refunds workflow) aging سيكون خاطئ  
**Fix Direction:**  
- توحيد المصدر: إما ضمان تحديث paid_amount دائمًا أو حساب outstanding من payments/refunds مثل FinancialReportService.

---

### 6) (HIGH) BankingService importTransactions duplicate-check broken for NULL reference numbers
**Type:** Logic/Data Integrity  
**Severity:** High  
**File:** `app/Services/BankingService.php` (importTransactions, lines ~200–240)  
**Problem:**  
- التحقق من التكرار:
  ```php
  ->where('reference_number', $txn['reference_number'] ?? '')
  ```
- لو reference_number غير موجود ⇒ يتحقق على `''` لكن عند التسجيل يتم حفظها `null`  
=> سيُستورد نفس Transaction أكثر من مرة عند غياب reference_number  
**Impact:**  
- تكرار قيود مالية / bank movements  
**Fix Direction:**  
- إذا reference null استخدم `whereNull('reference_number')` أو enforce required reference or use composite uniqueness.

---

### 7) (CRIT) API CustomersController can create customers with NULL branch_id (Orphan Data)
**Type:** Data Integrity / Multi-Branch  
**Severity:** Critical  
**File:** `app/Http/Controllers/Api/V1/CustomersController.php` (store, lines ~64–105)  
**Problem:**  
- يستخرج `branchId = $store?->branch_id`  
- ثم:
  ```php
  $validated['branch_id'] = $branchId;
  Customer::create($validated);
  ```
- بدون أي validate أن branchId موجود  
**Impact:**  
- لو Store Token مرتبط بـ Store بدون branch_id ⇒ ينشئ Customers بـ branch_id = NULL  
- هذه البيانات ستصبح غير مترابطة داخل ERP ومتوقع تختفي من معظم شاشات النظام بسبب branch scoping  
**Fix Direction:**  
- require store branch_id أو reject request (مثل OrdersController الذي يشترط branchId).

---

### 8) (HIGH) Multiple Analytics services use `created_at` instead of `sale_date` (Incorrect business analytics)
**Type:** Logic/Reporting  
**Severity:** High  
**Files:**  
- `app/Services/Analytics/ABCAnalysisService.php` (lines ~47–64)  
- `app/Services/Analytics/CustomerBehaviorService.php` (lines ~34–47)  
- `app/Services/Analytics/AdvancedAnalyticsService.php` (lines ~42–67 وغيرها)  
**Problem:**  
- تعتمد على created_at كمرجع زمني للمبيعات بدل sale_date (المنطقي في ERP).  
- في `ABCAnalysisService` يوجد أيضًا خطأ إضافي:  
  endOfDay ثم `toDateString()` → يفقد الوقت ويجعل whereBetween على created_at ينتهي عند 00:00  
**Impact:**  
- نتائج تحليلية مضللة (ABC / RFM / churn / trends)  
- اختلاف الأرقام عن تقارير sales المبنية على sale_date  
**Fix Direction:**  
- توحيد كل التحليلات على sale_date (أو posted_at حسب policy)  
- واستخدام Carbon datetime boundaries بدل date strings عندما يكون العمود datetime.

---

### 9) (MED) SalesAnalytics trend uses `created_at` while other metrics use `sale_date`
**Type:** Reporting Consistency  
**Severity:** Medium  
**File:** `app/Livewire/Reports/SalesAnalytics.php` (loadSalesTrend, lines ~199–240)  
**Problem:** loadSalesTrend يعمل grouping على `created_at`  
بينما TopProducts وغيرها تم تعديلها لتستخدم `sale_date`  
**Impact:**  
- Trend chart لن يتطابق مع باقي أرقام التحليلات  
**Fix Direction:**  
- استخدام sale_date في trend أو توحيد policy.

---

### 10) (HIGH) AdvancedAnalyticsService contains many placeholder methods returning empty values
**Type:** Logic/Feature Integrity  
**Severity:** High  
**File:** `app/Services/Analytics/AdvancedAnalyticsService.php` (lines ~385+)  
**Problem:** الكثير من الدوال ترجع `[]` أو `0` مثل:
- getPreviousPeriodSales returns 0  
- groupByDay/hour returns []  
- getTopProducts returns []  
- calculateInventoryValue returns 0  
**Impact:**  
- أي Dashboard يعتمد على الخدمة سيظهر بيانات ناقصة/صفرية بشكل دائم  
**Fix Direction:**  
- إما تنفيذها فعليًا أو إزالة feature/إخفاء endpoint حتى لا يضلل المستخدم.

---

## ✅ End of Report
