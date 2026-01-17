# APMO ERP (v31) — تقرير موحّد للـ Bugs (الجديدة + المتبقية)

> **المصادر المدمجة**:  
> 1) تقرير "Bugs الجديدة + المتبقية" التفصيلي.  
> 2) تقرير "Delta" الذي يحصي Bugs قديمة ما زالت موجودة + Bugs جديدة.  
>
> **ملاحظة مهمة (بدون تكرار)**: أي بند يغطيه التقرير التفصيلي سيتم **استبعاده من الملحق** لتجنب تكرار نفس الملفات/المواضع.

## Versions (verified)
- **Laravel**: `laravel/framework v12.44.0`
- **Livewire**: `livewire/livewire v4.0.1`

---
## ملخص سريع (أهم ما ستجده في التقرير)
- **[CRITICAL]** Bug مؤثر على المخزون: الـ API يقوم بضبط `products.stock_quantity` بقيمة **مخزون مخزن واحد** بينما الـ Repository يعتبرها **إجمالي المخزون عبر كل المخازن** (يكسر تقارير/تنبيهات/ERP coherence).
- **[CRITICAL]** Webhooks: يتم ضبط `BranchContext` داخل الـ Controller بدون أي `finally`/clearing، كما أن Routes الخاصة بالـ webhooks لا تستخدم `api-core` (خطر تسريب سياق الفرع في السيرفرات طويلة التشغيل + عدم توحيد سلوك الـ API).
- **[HIGH]** تقارير مالية تعتمد على `created_at` + تستخدم `float` في الجمع + لا تُصفّي الحالات بشكل كافٍ ⇒ أرقام مالية/فترات تقارير قد تكون خاطئة خصوصًا مع backdated sales.
- **[HIGH]** إغلاق يوم POS: يتم تسجيل `closed_by = auth()->id()` بينما الإغلاق يتم غالبًا عبر Command بدون مستخدم ⇒ `null`/كسر قيود DB + يوجد تعارض تاريخ (`sale_date` في Service مقابل `created_at` في Job).
- **[MEDIUM]** Scheduled reports (Sales/Customers): تعتمد `created_at` ولا تُصفّي حالات البيع/الحذف بشكل كافٍ ⇒ إرسال تقارير غير دقيقة.
- **[MEDIUM]** Orders API (store token): `sale_date` تُفرض كـ "اليوم" و `created_by` تعتمد على auth (غالبًا null) + حسابات باستخدام float ⇒ عدم تطابق مع StoreSyncService ودقة مالية أقل.
- **[MEDIUM]** SlowMovingStockService: SQL MySQL-specific (`DATEDIFF/NOW`) + لا يُصفّي status للـ sales ⇒ نتائج خاطئة/عدم توافق عند تغيير DB.
- **[MEDIUM]** ما زال يوجد مسارين لتحويل المخزون (Transfer vs StockTransfer) ⇒ مخاطرة تباين في السلوك والتقارير.
- **[LOW]** Endpoints الأداء/الأخطاء في ReportsController ما زالت Placeholder (قيم 0/فارغة) ⇒ Dashboard مضلل.

---
## ملخص (من تقرير الـ Delta)
- Old bugs still not solved: **103**
- New bugs in v31: **1**

### Old bugs still not solved — by severity
- High: 63
- Medium: 40

### New bugs — by severity
- Medium: 1

## Bugs جديدة / متبقية (تفصيل)
### V31-CRIT-01 — تضارب معنى `products.stock_quantity` مع multi-warehouse
**Locations**
- `app/Http/Controllers/Api/V1/InventoryController.php` (تقريبًا: السطور 124–151)
- `app/Repositories/StockMovementRepository.php` (تقريبًا: السطور 186–213)

**المشكلة**
- الـ `StockMovementRepository::updateProductStockCache()` يوضح صراحة أن `stock_quantity` هو **إجمالي المخزون عبر كل المخازن** (`sum(quantity)` لكل المنتج).
- بينما `InventoryController::updateStock/bulkUpdateStock` يقوم في نهاية الـ transaction بعمل:
  - حساب مخزون **warehouse_id محدد** ثم
  - تحديث `products.stock_quantity` بهذه القيمة.

**الأثر (ERP/Inventory/Finance)**
- لو المنتج له أكثر من Warehouse داخل نفس الفرع: `stock_quantity` سيتم تحويله لقيمة مخزن واحد ⇒
  - تقارير المخزون/slow moving/expiry/low stock alerts غير صحيحة
  - inconsistencies بين الـ API وباقي الشاشات التي تعتمد على cache

**اقتراح إصلاح**
- لا تكتب `stock_quantity` بقيمة warehouse واحدة.
- إمّا:
  1) الاعتماد فقط على `updateProductStockCache()` (إجمالي كل المخازن)، وارجع القيمة الخاصة بالـ warehouse ضمن response بدون تخزينها في `products.stock_quantity`.
  2) أو إذا كنت تحتاج cache لكل warehouse: اعمل جدول/حقل منفصل للـ per-warehouse stock cache.

---

### V31-CRIT-02 — Webhooks لا تُطبّق `api-core` + BranchContext لا يتم تنظيفه
**Locations**
- `routes/api.php` (السطور 85–89: مجموعة `webhooks` عليها throttle فقط)
- `app/Http/Controllers/Api/V1/WebhooksController.php` (مثال: السطور 27–40 و 82–86: `BranchContextManager::setBranchContext(...)` بدون `clear`)

**المشكلة**
- الـ Routes الخاصة بالـ webhooks لا تستخدم `api-core` (وبالتالي قد تفقد `ClearBranchContext` وغيره).
- داخل `WebhooksController` يتم set للـ BranchContext لكن لا يوجد `try/finally` ينفذ `BranchContextManager::clearBranchContext()`.

**الأثر**
- على بيئات طويلة التشغيل (Octane/RoadRunner/Queue Workers التي تستقبل أكثر من request بنفس process): ممكن يحصل **تسريب branch context** لطلبات لاحقة.
- عدم توحيد سلوك الـ API (JSON validation/headers) مقارنة بباقي الـ API.

**اقتراح إصلاح**
- أضف `api-core` لمجموعة webhooks:
  - `Route::prefix('webhooks')->middleware(['api-core','throttle:30,1'])->group(...)`
- وأضف `try/finally` في كل handler:
  - `setBranchContext` ثم في `finally` => `clearBranchContext`.

---

### V31-HIGH-03 — تقارير مالية تعتمد على `created_at` + float sums (مخاطر مالية)
**Locations**
- `app/Http/Controllers/Admin/ReportsController.php`
  - `financeSales()` (تقريبًا: 109–135)
  - `financePurchases()` (تقريبًا: 140–166)
  - `financePnl()` (تقريبًا: 171–211)
  - `financeAging()` (تقريبًا: 252–307)

**المشكلة**
- استخدام `created_at` لتحديد فترة التقرير بدل `sale_date` / `purchase_date` (في ERP غالبًا “تاريخ العملية” ≠ “تاريخ الإدخال”).
- عدم تصفية statuses بشكل كافٍ (في بعض المواضع فقط يستبعد `cancelled` بينما منطق الإيراد عادة يستبعد `draft/void/refunded/...`).
- استخدام `(float)` مع `sum()` والحسابات (`grossProfit/netProfit/aging`) ⇒ احتمالية أخطاء rounding خصوصًا لو عندك 3+ decimal places.

**الأثر**
- أرقام P&L / Aging / Sales/Purchases summaries قد تكون **غير صحيحة** خصوصًا مع backdated/imported operations.
- عدم تطابق مع شاشات أخرى بالفعل تعتمد `sale_date` (مثل POSService).

**اقتراح إصلاح**
- توحيد الـ date columns:
  - sales: `sale_date` (أو تاريخ الفاتورة المعتمد)
  - purchases: `purchase_date`
  - aging: الأفضل الاعتماد على due_date (إن وجد) أو sale_date بدل created_at
- توحيد فلترة statuses.
- في الحسابات: إما bcmath بسلاسل، أو إرجاع sums كسلاسل/decimals بدون cast لـ float.

---

### V31-HIGH-04 — POS Closing: `closed_by` قد يصبح null + تعارض منطق التاريخ بين Service/Job
**Locations**
- `app/Services/POSService.php` (تقريبًا: السطر 485: `closed_by => auth()->id()`)
- `app/Console/Commands/ClosePosDay.php` (يستدعي Service من CLI)
- `app/Jobs/ClosePosDayJob.php` (تقريبًا: السطر 55 يستخدم `whereDate('created_at', $date)`)

**المشكلة**
- عند تشغيل الإغلاق عبر Command (بدون مستخدم web): `auth()->id()` غالبًا **null**.
- يوجد منطقين للإغلاق:
  - POSService يعتمد `sale_date`
  - ClosePosDayJob يعتمد `created_at`

**الأثر**
- ممكن crash إذا `closed_by` not-null / foreign key.
- أو Audit ناقص.
- أو نتائج إغلاق يوم مختلفة حسب المسار الذي شغّل الإغلاق.

**اقتراح إصلاح**
- وحّد التنفيذ (Service واحد) واستعمل `sale_date`.
- مرّر `closed_by` كـ parameter (user/system user) أو اجعلها nullable بوضوح.
- إذا ستستخدم Job: اجعله يستدعي POSService بدل إعادة الحساب.

---

### V31-MED-05 — Scheduled reports (Sales/Customers) غير دقيقة
**Locations**
- `app/Services/ScheduledReportService.php`
  - `fetchSalesReportData()` (تقريبًا: 70–103)
  - `fetchCustomerReportData()` (تقريبًا: 139–160 وما بعدها)

**المشكلة**
- Sales scheduled report يعتمد `created_at` في grouping/filters.
- Customer report يعمل `leftJoin` على sales بدون تصفية status أو `deleted_at` في sales.

**الأثر**
- تقارير “مجدولة” تُرسل بالإيميل قد تعرض أرقام غير متوافقة مع بقية النظام.

**اقتراح إصلاح**
- استخدم `sale_date` بدل `created_at`.
- أضف فلترة status + `whereNull(sales.deleted_at)` في joins.

---

### V31-MED-06 — Orders API: تاريخ العملية و audit غير مضبوطين + float arithmetic
**Locations**
- `app/Http/Controllers/Api/V1/OrdersController.php` (تقريبًا: 203–247)

**المشكلة**
- `sale_date` يتم فرضها كـ `now()->toDateString()` بدل أخذها من payload (order date) أو على الأقل من store-created timestamp.
- `created_by` يستخدم `auth()->id()` بينما هذا endpoint محمي بـ `store.token` وليس `auth:sanctum` ⇒ غالبًا null.
- حسابات line totals/discounts/tax تتم بـ float.

**الأثر**
- mismatch مع StoreSyncService الذي يربط العمليات بـ integration user ويستخدم تواريخ العملية القادمة من المنصة.
- دقة أقل في الحسابات المالية.

**اقتراح إصلاح**
- اجعل `sale_date` يأتي من input (مثلاً `order_date`) مع validation.
- استخدم integration user id (مثل منطق StoreSyncService) بدل auth.
- استخدم bcmath/rounding موحد.

---

### V31-MED-07 — SlowMovingStockService: SQL غير متوافق + لا يفلتر statuses
**Locations**
- `app/Services/Reports/SlowMovingStockService.php` (تقريبًا: 17–31)

**المشكلة**
- استخدام `DATEDIFF(NOW(), ...)` (MySQL-specific) بينما بقية المشروع بدأ يدعم DB compatibility.
- join على `sales` يكتفي بـ `whereNull(deleted_at)` بدون استبعاد حالات مثل `cancelled/void/refunded`.

**الأثر**
- نتائج “slow moving / obsolete” قد تكون خاطئة.
- صعوبة مستقبلية إذا تغيّر DB driver.

**اقتراح إصلاح**
- استخدم DatabaseCompatibilityService أو query builder متوافق.
- أضف فلترة statuses.

---

### V31-MED-08 — ما زال يوجد نظامان للتحويلات (Transfer vs StockTransfer)
**Locations**
- `app/Models/Transfer.php` (يوضح وجود نظامين)

**المشكلة/الأثر**
- وجود مسارين لنفس الـ business process (تحويل مخزون) يؤدي بسهولة إلى:
  - اختلاف في قواعد الخصم/الإضافة
  - اختلاف في التقارير/الأثر المحاسبي
  - ارتباك للمستخدمين

**اقتراح إصلاح**
- توحيد المسار أو تحديد واضح جدًا للفروقات + تعطيل المسار غير المستخدم.

---

### V31-LOW-09 — Performance/Errors endpoints Placeholder
**Locations**
- `app/Http/Controllers/Admin/ReportsController.php` (تقريبًا: 64–104)

**المشكلة**
- `performance()` و `errors()` تُرجع قيم 0/فارغة (placeholder) لكنها تُعرض كـ “تقارير”.

**الأثر**
- Dashboard/Monitoring مضلل.

**اقتراح إصلاح**
- إما تنفيذها فعليًا (logs/metrics) أو إخفاء endpoints حتى يتم تنفيذها.


---

## ملحق A — بنود قديمة ما زالت موجودة (من تقرير الـ Delta) **بعد إزالة التكرار**

> تم استبعاد **10** بند/تحذير لأن نفس الملفات/المواضع تمت تغطيتها بالفعل في القسم التفصيلي أعلاه.
> المتبقي في هذا الملحق: **93** بند قديم + **1** بند جديد.


### A — High

- **A.1** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `resources/views/livewire/admin/dashboard.blade.php` | Line: `42`
  - Evidence: `? $saleModel::selectRaw('DATE(created_at) as day, SUM(total_amount) as total')`
- **A.2** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `resources/views/livewire/admin/dashboard.blade.php` | Line: `55`
  - Evidence: `? $contractModel::selectRaw('status, COUNT(*) as total')`
- **A.3** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Console/Commands/CheckDatabaseIntegrity.php` | Line: `232`
  - Evidence: `->select($column, DB::raw('COUNT(*) as count'))`
- **A.4** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Console/Commands/CheckDatabaseIntegrity.php` | Line: `237`
  - Evidence: `$query->whereRaw($where);`
- **A.5** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Console/Commands/CheckDatabaseIntegrity.php` | Line: `348`
  - Evidence: `DB::statement($fix);`
- **A.8** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Http/Controllers/Api/StoreIntegrationController.php` | Line: `75`
  - Evidence: `->selectRaw($stockExpr.' as current_stock');`
- **A.13** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Http/Controllers/Branch/ReportsController.php` | Line: `50`
  - Evidence: `->selectRaw("{$dateExpr} as first_move")`
- **A.15** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Admin/Branch/Reports.php` | Line: `86`
  - Evidence: `'due_amount' => (clone $query)->selectRaw('SUM(total_amount - paid_amount) as due')->value('due') ?? 0,`
- **A.16** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Admin/Branch/Reports.php` | Line: `99`
  - Evidence: `'total_value' => (clone $query)->sum(DB::raw('COALESCE(default_price, 0) * COALESCE(stock_quantity, 0)')),`
- **A.17** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Concerns/LoadsDashboardData.php` | Line: `147`
  - Evidence: `->whereRaw("{$stockExpr} <= min_stock")`
- **A.18** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Concerns/LoadsDashboardData.php` | Line: `285`
  - Evidence: `->selectRaw("{$stockExpr} as current_quantity")`
- **A.19** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Concerns/LoadsDashboardData.php` | Line: `287`
  - Evidence: `->whereRaw("{$stockExpr} <= products.min_stock")`
- **A.20** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Concerns/LoadsDashboardData.php` | Line: `290`
  - Evidence: `->orderByRaw($stockExpr)`
- **A.21** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Dashboard/CustomizableDashboard.php` | Line: `249`
  - Evidence: `$totalValue = (clone $productsQuery)->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(default_price, 0) * COALESCE(stock_quantity, 0)'));`
- **A.22** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Helpdesk/Dashboard.php` | Line: `76`
  - Evidence: `$ticketsByPriority = Ticket::select('priority_id', DB::raw('count(*) as count'))`
- **A.23** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Inventory/StockAlerts.php` | Line: `61`
  - Evidence: `$query->whereRaw('COALESCE(stock_calc.total_stock, 0) <= products.min_stock AND COALESCE(stock_calc.total_stock, 0) > 0');`
- **A.24** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Inventory/StockAlerts.php` | Line: `63`
  - Evidence: `$query->whereRaw('COALESCE(stock_calc.total_stock, 0) <= 0');`
- **A.25** — **High** — Logic/Multi-branch — Fallback to Branch::first()?->id can mis-assign records
  - File: `app/Livewire/Manufacturing/BillsOfMaterials/Form.php` | Line: `82`
  - Evidence: `$branchId = $user->branch_id ?? Branch::first()?->id;`
- **A.26** — **High** — Logic/Multi-branch — Fallback to Branch::first()?->id can mis-assign records
  - File: `app/Livewire/Manufacturing/ProductionOrders/Form.php` | Line: `87`
  - Evidence: `$branchId = $user->branch_id ?? Branch::first()?->id;`
- **A.27** — **High** — Logic/Multi-branch — Fallback to Branch::first()?->id can mis-assign records
  - File: `app/Livewire/Manufacturing/WorkCenters/Form.php` | Line: `103`
  - Evidence: `return Branch::first()?->id;`
- **A.28** — **High** — Logic/Multi-branch — Fallback to Branch::first()?->id can mis-assign records
  - File: `app/Livewire/Manufacturing/WorkCenters/Form.php` | Line: `131`
  - Evidence: `$branchId = $user->branch_id ?? Branch::first()?->id;`
- **A.33** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Reports/SalesAnalytics.php` | Line: `204`
  - Evidence: `->selectRaw("{$dateFormat} as period")`
- **A.34** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Reports/SalesAnalytics.php` | Line: `328`
  - Evidence: `->selectRaw("{$hourExpr} as hour")`
- **A.40** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Warehouse/Index.php` | Line: `99`
  - Evidence: `$totalValue = (clone $stockMovementQuery)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->value('value') ?? 0;`
- **A.41** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Livewire/Warehouse/Movements/Index.php` | Line: `90`
  - Evidence: `'total_value' => (clone $baseQuery)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->value('value') ?? 0,`
- **A.42** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Models/Product.php` | Line: `283`
  - Evidence: `->whereRaw("({$stockSubquery}) <= stock_alert_threshold");`
- **A.43** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Models/Product.php` | Line: `299`
  - Evidence: `return $query->whereRaw("({$stockSubquery}) <= 0");`
- **A.44** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Models/Product.php` | Line: `315`
  - Evidence: `return $query->whereRaw("({$stockSubquery}) > 0");`
- **A.45** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Models/Project.php` | Line: `170`
  - Evidence: `return $query->whereRaw('actual_cost > budget');`
- **A.46** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Models/SearchIndex.php` | Line: `76`
  - Evidence: `$builder->whereRaw(`
- **A.47** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Models/SearchIndex.php` | Line: `85`
  - Evidence: `$q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])`
- **A.51** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/AutomatedAlertService.php` | Line: `220`
  - Evidence: `->whereRaw("({$stockSubquery}) > 0")`
- **A.62** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/PurchaseReturnService.php` | Line: `459`
  - Evidence: `return $query->select('condition', DB::raw('COUNT(*) as count'), DB::raw('SUM(qty_returned) as total_qty'))`
- **A.64** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/RentalService.php` | Line: `375`
  - Evidence: `$stats = $query->selectRaw('`
- **A.72** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/SmartNotificationsService.php` | Line: `42`
  - Evidence: `->selectRaw("{$stockExpr} as current_quantity")`
- **A.73** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/SmartNotificationsService.php` | Line: `43`
  - Evidence: `->whereRaw("{$stockExpr} <= products.min_stock")`
- **A.74** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/StockReorderService.php` | Line: `40`
  - Evidence: `->whereRaw("({$stockSubquery}) <= reorder_point")`
- **A.75** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/StockReorderService.php` | Line: `65`
  - Evidence: `->whereRaw("({$stockSubquery}) <= stock_alert_threshold")`
- **A.76** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/StockReorderService.php` | Line: `66`
  - Evidence: `->whereRaw("({$stockSubquery}) > COALESCE(reorder_point, 0)")`
- **A.77** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/StockService.php` | Line: `31`
  - Evidence: `return (float) $query->selectRaw('COALESCE(SUM(quantity), 0) as stock')`
- **A.78** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/StockService.php` | Line: `101`
  - Evidence: `return (float) ($query->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')`
- **A.86** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/WorkflowAutomationService.php` | Line: `27`
  - Evidence: `->whereRaw("({$stockSubquery}) <= COALESCE(reorder_point, min_stock, 0)")`
- **A.87** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/WorkflowAutomationService.php` | Line: `169`
  - Evidence: `->selectRaw("*, ({$stockSubquery}) as calculated_stock")`
- **A.88** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/WorkflowAutomationService.php` | Line: `170`
  - Evidence: `->orderByRaw("(COALESCE(reorder_point, min_stock, 0) - ({$stockSubquery})) DESC")`
- **A.89** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Analytics/InventoryTurnoverService.php` | Line: `38`
  - Evidence: `$cogs = $cogsQuery->sum(DB::raw('sale_items.quantity * COALESCE(products.cost, 0)'));`
- **A.90** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Analytics/InventoryTurnoverService.php` | Line: `48`
  - Evidence: `$avgInventoryValue = $inventoryQuery->sum(DB::raw('COALESCE(stock_quantity, 0) * COALESCE(cost, 0)'));`
- **A.91** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Analytics/ProfitMarginAnalysisService.php` | Line: `145`
  - Evidence: `DB::raw("DATE_FORMAT(sales.created_at, '{$dateFormat}') as period"),`
- **A.92** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Analytics/ProfitMarginAnalysisService.php` | Line: `152`
  - Evidence: `->groupBy(DB::raw("DATE_FORMAT(sales.created_at, '{$dateFormat}')"))`
- **A.93** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Analytics/SalesForecastingService.php` | Line: `66`
  - Evidence: `DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),`
- **A.94** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Analytics/SalesForecastingService.php` | Line: `73`
  - Evidence: `->groupBy(DB::raw("DATE_FORMAT(created_at, '{$dateFormat}')"))`
- **A.95** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Performance/QueryOptimizationService.php` | Line: `179`
  - Evidence: `DB::statement($optimizeStatement);`
- **A.96** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Reports/CustomerSegmentationService.php` | Line: `27`
  - Evidence: `->selectRaw("{$datediffExpr} as recency_days")`
- **A.98** — **High** — Security/SQL — Raw SQL with variable interpolation
  - File: `app/Services/Reports/CustomerSegmentationService.php` | Line: `158`
  - Evidence: `->selectRaw("{$datediffExpr} as days_since_purchase")`

### A — Medium

- **A.6** — **Medium** — Perf/Security — Loads entire file into memory (Storage::get)
  - File: `app/Http/Controllers/Admin/MediaDownloadController.php` | Line: `53`
  - Evidence: `$content = $disk->get($path);`
- **A.12** — **Medium** — Logic/Files — Local disk URL generation may fail
  - File: `app/Http/Controllers/Branch/ProductController.php` | Line: `156`
  - Evidence: `'url' => Storage::disk('local')->url($path),`
- **A.14** — **Medium** — Perf/Security — Loads entire file into memory (Storage::get)
  - File: `app/Http/Controllers/Files/UploadController.php` | Line: `41`
  - Evidence: `$content = $storage->get($path);`
- **A.29** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Rental/Reports/Dashboard.php` | Line: `69`
  - Evidence: `$occupancyRate = $total > 0 ? (float) bcdiv(bcmul((string) $occupied, '100', 4), (string) $total, 1) : 0;`
- **A.30** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Reports/SalesAnalytics.php` | Line: `152`
  - Evidence: `$avgOrderValue = $totalOrders > 0 ? (float) bcdiv((string) $totalSales, (string) $totalOrders, 2) : 0;`
- **A.31** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Reports/SalesAnalytics.php` | Line: `171`
  - Evidence: `$salesGrowth = (float) bcdiv(bcmul($diff, '100', 6), (string) $prevTotalSales, 1);`
- **A.32** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Reports/SalesAnalytics.php` | Line: `176`
  - Evidence: `$completionRate = $totalOrders > 0 ? (float) bcdiv(bcmul((string) $completedOrders, '100', 4), (string) $totalOrders, 1) : 0;`
- **A.35** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Sales/Form.php` | Line: `300`
  - Evidence: `return (float) bcdiv($total, '1', BCMATH_STORAGE_SCALE);`
- **A.36** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Sales/Form.php` | Line: `340`
  - Evidence: `return (float) bcdiv($result, '1', BCMATH_STORAGE_SCALE);`
- **A.37** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Sales/Form.php` | Line: `476`
  - Evidence: `'discount_amount' => (float) bcdiv($discountAmount, '1', BCMATH_STORAGE_SCALE),`
- **A.38** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Sales/Form.php` | Line: `478`
  - Evidence: `'tax_amount' => (float) bcdiv($taxAmount, '1', BCMATH_STORAGE_SCALE),`
- **A.39** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Livewire/Sales/Form.php` | Line: `479`
  - Evidence: `'line_total' => (float) bcdiv($lineTotal, '1', BCMATH_STORAGE_SCALE),`
- **A.48** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Models/StockTransferItem.php` | Line: `74`
  - Evidence: `return (float) bcsub((string)$this->qty_shipped, (string)$this->qty_received, 3);`
- **A.49** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/AutomatedAlertService.php` | Line: `173`
  - Evidence: `$utilization = (float) bcmul(`
- **A.50** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/AutomatedAlertService.php` | Line: `183`
  - Evidence: `$availableCredit = (float) bcsub((string) $customer->credit_limit, (string) $customer->balance, 2);`
- **A.52** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/AutomatedAlertService.php` | Line: `234`
  - Evidence: `$estimatedLoss = (float) bcmul((string) $currentStock, (string) $unitCost, 2);`
- **A.53** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/CurrencyExchangeService.php` | Line: `55`
  - Evidence: `return (float) bcmul((string) $amount, (string) $rate, 4);`
- **A.54** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/CurrencyService.php` | Line: `128`
  - Evidence: `return (float) bcmul((string) $amount, (string) $rate, 2);`
- **A.55** — **Medium** — Perf/Security — Loads entire file into memory (Storage::get)
  - File: `app/Services/DiagnosticsService.php` | Line: `178`
  - Evidence: `$retrieved = Storage::disk($disk)->get($filename);`
- **A.56** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/HelpdeskService.php` | Line: `293`
  - Evidence: `return (float) bcdiv((string) $totalMinutes, (string) $tickets->count(), 2);`
- **A.57** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/HRMService.php` | Line: `235`
  - Evidence: `return (float) bcmul((string) $dailyRate, (string) $absenceDays, 2);`
- **A.58** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/LoyaltyService.php` | Line: `208`
  - Evidence: `return (float) bcmul((string) $points, (string) $settings->redemption_rate, 2);`
- **A.59** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/PricingService.php` | Line: `30`
  - Evidence: `return (float) bcdiv((string) $override, '1', 4);`
- **A.60** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/PricingService.php` | Line: `38`
  - Evidence: `return (float) bcdiv((string) $p, '1', 4);`
- **A.61** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/PricingService.php` | Line: `45`
  - Evidence: `return (float) bcdiv((string) $base, '1', 4);`
- **A.63** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/PurchaseService.php` | Line: `105`
  - Evidence: `$lineTax = (float) bcmul($taxableAmount, bcdiv((string) $taxPercent, '100', 6), 2);`
- **A.65** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/RentalService.php` | Line: `388`
  - Evidence: `? (float) bcmul(bcdiv((string) $occupiedUnits, (string) $totalUnits, 4), '100', 2)`
- **A.66** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/RentalService.php` | Line: `519`
  - Evidence: `? (float) bcmul(bcdiv((string) $collectedAmount, (string) $totalAmount, 4), '100', 2)`
- **A.79** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/TaxService.php` | Line: `63`
  - Evidence: `return (float) bcdiv($taxPortion, '1', 4);`
- **A.80** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/TaxService.php` | Line: `69`
  - Evidence: `return (float) bcdiv($taxAmount, '1', 4);`
- **A.81** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/TaxService.php` | Line: `82`
  - Evidence: `return (float) bcdiv((string) $base, '1', 4);`
- **A.82** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/TaxService.php` | Line: `98`
  - Evidence: `return (float) bcdiv($total, '1', 4);`
- **A.83** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/TaxService.php` | Line: `102`
  - Evidence: `defaultValue: (float) bcdiv((string) $base, '1', 4)`
- **A.84** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/TaxService.php` | Line: `142`
  - Evidence: `'total_with_tax' => (float) bcadd((string) $subtotal, (string) $taxAmount, 4),`
- **A.85** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/UIHelperService.php` | Line: `190`
  - Evidence: `$value = (float) bcdiv((string) $value, '1024', $precision + 2);`
- **A.97** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/Reports/CustomerSegmentationService.php` | Line: `136`
  - Evidence: `? (float) bcdiv($totalRevenue, (string) count($customers), 2)`
- **A.100** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/UX/SmartSuggestionsService.php` | Line: `123`
  - Evidence: `'profit_per_unit' => (float) bcsub($price, (string) $cost, 2),`
- **A.101** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/UX/SmartSuggestionsService.php` | Line: `144`
  - Evidence: `'profit_per_unit' => (float) bcsub($suggestedPrice, (string) $cost, 2),`
- **A.102** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/UX/SmartSuggestionsService.php` | Line: `274`
  - Evidence: `return (float) bcdiv((string) ($totalSold ?? 0), (string) $days, 2);`
- **A.103** — **Medium** — Finance/Precision — BCMath result cast to float
  - File: `app/Services/UX/SmartSuggestionsService.php` | Line: `412`
  - Evidence: `? (float) bcmul(bcdiv(bcsub((string) $product->default_price, (string) $product->standard_cost, 2), (string) $product->default_price, 4), '100', 2)`

### B — Bugs جديدة (من تقرير الـ Delta)

- **B.1** — **Medium** — Security/Auth — Token accepted via query/body (leak risk)
  - File: `app/Http/Middleware/AuthenticateStoreToken.php` | Line: `109`
  - Evidence: `return $request->query('api_token') ?? $request->input('api_token');`
