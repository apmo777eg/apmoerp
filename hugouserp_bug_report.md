# تقرير فحص المشروع (Laravel 12 + Livewire 4 beta.5) — 2026-01-13

> ملاحظة مهمة: ملفات الـ migrations غير موجودة داخل الـ ZIP، لذلك في جزء من المشاكل المتعلقة بأسماء الأعمدة أنا حكمت عليها من **الـ Models (fillable/casts/accessors)** ومن الاستدعاءات داخل الكود.  
> لو الـ DB عندك فيها أعمدة قديمة (grand_total / paid_total / qty …) جنب الأعمدة الجديدة (total_amount / paid_amount / quantity …) فده برضه يعتبر Bug/Technical Debt لأن المشروع بيستخدم الاسمين بشكل غير موحد.

---

## 0) ملخص سريع (أخطر النقاط)

- **خلط شديد بين أسماء أعمدة قديمة/جديدة**: (grand_total / discount_total / tax_total / paid_total / due_total / posted_at / payment_due_date / qty …) مستخدمين كأعمدة SQL في أماكن كثيرة، بينما الـ Models بتتعامل معاهم كـ **Accessors** مبنية على أعمدة تانية (مثل total_amount / paid_amount / due_date / quantity).  
  النتيجة: تقارير/Exports/Jobs ممكن **تفشل SQL** أو تطلع أرقام غلط.

- **مشاكل Multi‑Branch**:  
  - Global Branch Scope بيتعطل في الـ console (Jobs/Queues) → تشغيل مهام في الخلفية بدون عزل فروع.  
  - طريقة كشف وجود branch_id داخل الجدول غير سليمة (مرتبطة بوجود method اسمها branch()) وفي نفس الوقت تعيين branch_id تلقائياً في creating مش شغال إلا لو الـ Model عنده currentBranchId().

- **مشاكل محاسبة/دفاتر**:  
  - AccountingService فيها `Log::error()` بدون import → Runtime error وقت عدم توازن القيد.  
  - قيود المبيعات مش بتتعامل صح مع **الدفعات الجزئية** (Receivable ناقص) وقد تخرج قيود غير متزنة.

- **POS**:  
  - Limit الخصم اليومي ممكن يتكسر بسبب `sum('discount_total')` على Query.  
  - إقفال جلسة POS بيستخدم `sum('grand_total')` على Query وقد يفشل أو يرجع صفر.

---

## 1) Bugs حرجة (Critical)

### C1) BranchScope متعطل في console → Jobs/Queues بدون عزل فروع
- **File:** `app/Models/Scopes/BranchScope.php`  
- **Lines:** 31–34  
- **Problem:** لو التطبيق شغال في console (queue worker / schedule / commands) الـ scope بيرجع بدون ما يضيف filter.  
- **Impact:** أي Job/Listener يعمل Query على Models ممكن يلم/يعدل بيانات من كل الفروع بدون قصد.  
- **Fix:** لا توقف الـ scope لمجرد إنك في console. اعمل disable فقط في حالات محددة (migrations/seeding) أو مرّر branch context للـ jobs.

---

### C2) تضارب خطير بين منطق "وجود branch_id" في BranchScope و HasBranch
- **Files:**
  - `app/Models/Scopes/BranchScope.php` (hasBranchIdColumn)
  - `app/Traits/HasBranch.php` (hasBranchIdColumn + auto assign في creating)
- **Lines:**
  - BranchScope.php: 86–96  
  - HasBranch.php: 45–53 و 112–115  
- **Problem:**  
  - BranchScope يعتبر أي Model عنده method اسمها `branch()` كأنه عنده عمود `branch_id` ويطبق عليه شرط `where table.branch_id …`.  
  - لكن HasBranch في creating **لن يضبط branch_id** إلا لو `branch_id` موجودة في `$fillable` **وكمان** الـ Model عنده method اسمها `currentBranchId()` (والـ BaseModel لا يوفرها).
- **Impact:**  
  - لو في Models لا تحتوي فعلاً على عمود `branch_id` → SQL errors.  
  - لو العمود موجود لكن مش بيتملّى → Records branch_id = NULL → تختفي بسبب الـ scope → علاقات/شاشات فاضية.
- **Fix (اقتراح عملي):**
  1) في BranchScope: بدل `method_exists($model,'branch')` استخدم كشف حقيقي للعمود (Schema::hasColumn مع cache).  
  2) في HasBranch: استخدم `current_branch_id()` (helper) مباشرة بدل الاشتراط على `currentBranchId()` أو اجعل BaseModel يستخدم `HasRequestContext`.

---

### C3) Sale::scopeOverdue يستخدم payment_due_date كـ column
- **File:** `app/Models/Sale.php`  
- **Lines:** 191–195  
- **Problem:** `whereNotNull('payment_due_date')` و `whereDate('payment_due_date', …)` بينما نفس الـ model عنده `due_date` و Accessor `getPaymentDueDateAttribute()` مبني على `due_date`.  
- **Impact:** SQL error أو نتائج غلط (فواتير متأخرة لا تظهر).  
- **Fix:** استبدل `payment_due_date` بـ `due_date` في الـ scope (أو وحّد الأعمدة).

---

### C4) Purchase::payments علاقة غلط (Receipt بدل PurchasePayment)
- **File:** `app/Models/Purchase.php`  
- **Lines:** 109–112  
- **Problem:** `payments()` راجعة `hasMany(Receipt::class)` بينما Receipt مرتبط بـ Sale وليس Purchase.  
- **Impact:** عرض/تقارير/Status للدفعات في المشتريات هتكون غلط أو فاضية.  
- **Fix:** إنشاء Model مخصص لدفعات المشتريات أو ربطها بـ Transaction/Payment الصحيح.

---

### C5) POSService يستخدم أعمدة افتراضية في SQL aggregates (discount_total / grand_total)
- **File:** `app/Services/POSService.php`
- **Lines:** 87–90 و 329–333  
- **Problems:**
  - Limit الخصم اليومي: `sum('discount_total')` على Query.  
  - إقفال الجلسة: `sum('grand_total')` على Query.
- **Impact:**  
  - لو الأعمدة دي مش موجودة في DB (وغالباً هي accessors) → SQL error.  
  - أو ترجع 0 → كسر Limits/تقارير.
- **Fix:** استخدم الأعمدة الحقيقية (مثلاً `discount_amount`, `total_amount`) أو `selectRaw('SUM(total_amount) as grand_total')`.

---

### C6) تقارير Branch (ReportsController) تستخدم paid_total/due_total + stock_movements.qty في DB::table
- **File:** `app/Http/Controllers/Branch/ReportsController.php`
- **Lines:** 89–110 و 167–198  
- **Problems:**
  - cashflow: `sum('paid_total')` و `sum('due_total')`  
  - stockAging: `SUM(m.qty)` (بينما الـ schema/Model يستخدم quantity)
- **Impact:** SQL errors أو أرقام غلط (Cashflow/Stock aging).  
- **Fix:** توحيد الأعمدة واستخدام quantity / paid_amount / (total_amount - paid_amount) … إلخ.

---

### C7) Sales/Purchases ExportImportController قديم وغير متوافق مع Models الحالية → فساد بيانات
- **Files:**
  - `app/Http/Controllers/Branch/Sales/ExportImportController.php`  
  - `app/Http/Controllers/Branch/Purchases/ExportImportController.php`
- **Examples (Sales file):** Lines 48–110 و 155–189  
- **Problem:** بيصدر/يستورد حقول (reference, posted_at, grand_total, tax_total, discount_total, amount_paid, amount_due …) لا تتطابق مع fillable في Models (reference_number, sale_date, total_amount, tax_amount, discount_amount, paid_amount …).  
- **Impact:** الاستيراد ممكن ينشئ Records ناقصة أو بأرقام 0 → كارثة مالية.  
- **Fix:** تحديث mapping بالكامل حسب الـ Models الحالية + Validation حقيقي.

---

### C8) POS Reports Export Controller يعتمد على posted_at + grand_total كـ columns و Scope Posted لا يشمل completed
- **File:** `app/Http/Controllers/Admin/Reports/PosReportsExportController.php`
- **Lines:** 40–66  
- **Problems:**
  - `whereDate('posted_at', …)` + `orderBy('posted_at')`  
  - `where('grand_total', …)`  
  - `Sale::posted()` يعتمد على status = posted فقط (وسيرفس الـ POS يسجل status = completed)
- **Impact:** التقرير ممكن يفشل أو يستبعد معظم مبيعات POS.  
- **Fix:** فلترة على `created_at` أو `sale_date` + توحيد statuses.

---

### C9) ClosePosDayJob يختار أعمدة grand_total/paid_total مباشرة
- **File:** `app/Jobs/ClosePosDayJob.php`  
- **Lines:** 30–34  
- **Problem:** `get(['grand_total','paid_total'])` لو مش أعمدة حقيقية هيفشل.  
- **Fix:** استخدم الأعمدة الفعلية أو selectRaw/alias.

---

### C10) AccountingService: Log::error بدون import → Runtime error
- **File:** `app/Services/AccountingService.php`
- **Lines:** 262  
- **Problem:** `Log::error(...)` داخل namespace App\Services بدون `use Illuminate\Support\Facades\Log;` أو `\Log::error`.  
- **Impact:** أول مرة قيد يطلع مش متزن → Exception جديد ويكسر العملية.  
- **Fix:** أضف import أو استخدم `\Log`.

---

### C11) AccountingService: قيود المبيعات لا تغطي الدفعات الجزئية (Receivable ناقص) + shipping غير مسجل
- **File:** `app/Services/AccountingService.php`
- **Area:** `generateSaleJournalEntry()` (تقريباً Lines 48–132)
- **Problem:** في حالة Partial Payments: بيسجل Cash/Bank للمدفوع فقط، لكن لا يسجل Accounts Receivable للباقي → القيد قد يصبح غير متزن أو غير صحيح محاسبياً.  
- **Fix:** لو مجموع المدفوعات < grand_total → أضف سطر Debit على Accounts Receivable بالباقي + عالج shipping (Revenue/Expense) حسب التصميم.

---

### C12) FinancialTransactionObserver يعتمد على grand_total كـ attribute (isDirty/getOriginal) وهو غالباً Accessor
- **File:** `app/Observers/FinancialTransactionObserver.php`
- **Lines:** 32–51  
- **Problem:** `isDirty('grand_total')` و `getOriginal('grand_total')` لن يعملوا لو grand_total مش عمود فعلي.  
- **Impact:** Balance updates خاطئة/لا تحدث عند تعديل إجمالي البيع/الشراء.  
- **Fix:** اعتمد على `total_amount` و/أو احسب من الأعمدة الفعلية.

---

### C13) EnforceDiscountLimit: property_exists على Eloquent attributes → Limits لا تُطبق
- **File:** `app/Http/Middleware/EnforceDiscountLimit.php`
- **Lines:** 33–39  
- **Problem:** `property_exists($user,'max_line_discount')` غالباً false لأن ده Attribute مش Property.  
- **Impact:** أي limits خاصة بالمستخدم لن تعمل → مخاطرة مالية.  
- **Fix:** استخدم `!is_null($user->max_line_discount)` أو `data_get($user,'max_line_discount')`.

---

## 2) Bugs عالية (High)

### H1) SaleUpdateRequest لا يتطابق مع Sale model (حقول لن تُحفظ)
- **File:** `app/Http/Requests/SaleUpdateRequest.php`  
- **Lines:** 41–85  
- **Problem:** discount_value / payment_due_date / customer_notes / expected_delivery_date … ليست ضمن fillable في `Sale`.  
- **Impact:** API update يتقبل الحقول لكن `fill()` سيتجاهلها → UI يبين نجاح لكن البيانات لا تتغير.  
- **Fix:** توحيد أسماء الحقول بين Request و Model أو عمل mapping قبل fill.

---

### H2) PurchaseService::cancel يتحقق من status == 'paid' بدل payment_status
- **File:** `app/Services/PurchaseService.php`
- **Lines:** 213–219  
- **Impact:** ممكن تلغي Purchase مدفوعة فعلياً.  
- **Fix:** تحقق من `$p->payment_status === 'paid'` أو من paid_amount.

---

### H3) PurchaseService::approve status = approved بينما Purchase::approve status = confirmed
- **Files:**  
  - `app/Services/PurchaseService.php` (Lines 67–70)  
  - `app/Models/Purchase.php` (Lines 165–169)  
- **Impact:** حالات الـ Purchase غير متوقعة → تقارير/صلاحيات/شاشات تتلخبط.  
- **Fix:** توحيد القيم واستخدام Enum فعلي (PurchaseStatus) بدل strings.

---

### H4) Sale statuses/Enums غير مستخدمين وتوجد قيم متناقضة عبر النظام
- **Files:** `app/Enums/SaleStatus.php` و `app/Models/Sale.php` و `app/Services/POSService.php` …  
- **Problem:** enum بيعرف (paid/partially_paid/confirmed) لكن الـ Model/Services تستخدم (draft/pending/posted/completed).  
- **Impact:** تقارير (posted) لا ترى مبيعات (completed)، وExport/Import يتعامل مع status = paid… إلخ.  
- **Fix:** إلغاء الـ enum أو تطبيقه فعلياً (casts) وتوحيد كل الأكواد.

---

### H5) StockService و DB::table يتجاهل branch scoping (خلط فروع)
- **File:** `app/Services/StockService.php`  
- **Lines:** 19–55 (+ expressions 84–115)  
- **Problem:** لا يوجد branch filter إطلاقاً، وده DB::table (لا يستفيد من BranchScope).  
- **Impact:** أرصدة مخزون ممكن تتجمع عبر كل الفروع إذا warehouse_id مش محدد.  
- **Fix:** إلزام warehouse_id أو إضافة branch filter صريح (join warehouses).

---

### H6) مصدر الحقيقة للمخزون غير موحد (stock_quantity vs stock_movements)
- **Files/Examples:**  
  - `app/Models/Product.php` (stock_quantity)  
  - `app/Listeners/UpdateStockOnSale.php` (يكتب stock_movements فقط)  
  - `app/Services/AutomatedAlertService.php` (low stock يعتمد على stock_quantity)  
- **Impact:** Alerts/Widgets تعتمد على رقم لا يتم تحديثه من عمليات البيع/الشراء → بيانات مضللة.  
- **Fix:** إما تحديث stock_quantity في كل حركة (بيع/شراء/تعديل) أو إلغاء stock_quantity والاكتفاء بـ stock_movements + caches محسوبة.

---

### H7) POSController لا يلزم warehouse_id
- **File:** `app/Http/Controllers/Api/V1/POSController.php`
- **Lines:** 27–43  
- **Impact:** sale ممكن تتسجل بـ warehouse_id = NULL → stock checks/stock movements قد تفشل أو تحسب غلط.  
- **Fix:** اجعل warehouse_id required أو حدده من branch default warehouse.

---

### H8) AuditsChanges: منطق تعطيل التدقيق في console أول 5 دقائق + property_exists للفرع
- **File:** `app/Traits/AuditsChanges.php`
- **Lines:** 33–46 و 70–82  
- **Impact:** Logs/Audits قد لا تُسجل بعد restart للـ worker، والـ branch_id قد لا يتسجل أبداً.  
- **Fix:** تحقق من seeding عبر command name أو env، واستبدل property_exists بـ attribute access.

---

### H9) توليد أكواد/مراجع بـ count()+1 → Race conditions + تكرار
- **Files (أمثلة):**
  - `app/Services/SaleService.php` (ReturnNote reference_number) Lines 87–90  
  - `app/Models/GoodsReceivedNote.php` Lines 36–49  
  - `app/Models/PurchaseRequisition.php` Lines 28–39  
  - `app/Models/Project.php` … إلخ
- **Impact:** في إنشاء متوازي (multi users) ممكن يحصل duplicate code/reference.  
- **Fix:** استخدم sequences/UUID/Unique index + retry، أو `max()+1` مع قفل (lock) داخل transaction.

---

### H10) PurchaseService::create يحسب total_amount = subtotal فقط (يتجاهل tax/discount/shipping)
- **File:** `app/Services/PurchaseService.php`
- **Lines:** 160–176  
- **Impact:** إجماليات مشتريات غير صحيحة → تقارير/دفاتر غلط.  
- **Fix:** طبق معادلة واضحة: subtotal - discount + tax + shipping.

---

### H11) POSService Idempotency غير مقيد بالفرع
- **File:** `app/Services/POSService.php`
- **Lines:** 44–46  
- **Impact:** لو client_uuid تكرر بين فرعين → يرجع Sale من فرع آخر.  
- **Fix:** `where('branch_id',$branchId)->where('client_uuid',$clientUuid)`.

---

### H12) Widgets/Analytics تعتمد على sum('grand_total') في Query
- **Files (أمثلة):**
  - `app/Livewire/Components/DashboardWidgets.php` Lines 64–66  
  - `app/Livewire/Reports/SalesAnalytics.php` Lines 152–155  
  - `app/Services/Analytics/KPIDashboardService.php` Lines 201–205
- **Impact:** SQL error أو أرقام 0 لو العمود افتراضي.  
- **Fix:** sum على الأعمدة الحقيقية.

---

### H13) استخدام \Str::uuid() داخل Controllers بدون استيراد Str (قد يعتمد على alias)
- **File:** `app/Http/Controllers/Branch/Sales/ExportImportController.php` (Line 157)  
- **File:** `app/Http/Controllers/Branch/Purchases/ExportImportController.php` (مشابه)  
- **Impact:** لو alias Str غير متاح → Fatal error.  
- **Fix:** `use Illuminate\Support\Str;` ثم `Str::uuid()` أو `\Illuminate\Support\Str::uuid()`.

---

### H14) CheckDatabaseIntegrity command يعتمد على أعمدة قديمة (sale_items.qty/price… stock_movements.movement_date…)
- **File:** `app/Console/Commands/CheckDatabaseIntegrity.php`
- **Lines:** 41–66 و 117–138 و 166–170  
- **Impact:** Command يعطي false positives أو يفشل.  
- **Fix:** تحديثه حسب schema الحالي أو حذفه.

---

## 3) Bugs متوسطة (Medium / Performance / Tech‑Debt)

- **Money helper**: `app/Helpers/helpers.php` (Lines 13–41) يستخدم float في `number_format((float)$amount)` بعد BCMath → عرض/تقريب قد يختلف.  
- **N+1**: `app/Listeners/UpdateStockOnSale.php` (Lines 32–47) يعمل `$item->load('unit')` داخل loop.  
- **تكرار setting() داخل loops**: POSService/Stock listeners.  
- **تشتت تعريفات الحالة**: Strings مبعثرة بدون single source of truth.

---

## 4) توصيات إصلاح مرتبة بالأولوية

1) **توحيد أسماء الأعمدة**: قرار واحد (الجديد أو القديم) ثم تعديل كل queries/exports/jobs.  
2) **إصلاح Multi‑Branch**:  
   - BranchScope لا يتعطل في console  
   - طريقة كشف branch_id + تعيينه تلقائياً  
   - استخدام current_branch_id عند الحاجة
3) **إصلاح المحاسبة**:  
   - استكمال قيود الدفعات الجزئية + shipping  
   - منع float قدر الإمكان (اعتماد decimal strings/BCMath أو integer cents)
4) **POS**: إلزام warehouse_id + إصلاح daily discount limit + session close totals.
5) **إصلاح Import/Export**: mapping صحيح و validation قوية (و transaction).

---

## Appendix: أماكن مرصودة تلقائياً لاستدعاءات Sum/Where على أعمدة افتراضية

> هذه قائمة مختصرة لأشهر الأماكن التي تحتاج مراجعة:  
- `DashboardWidgets.php` (sum('grand_total'))  
- `SalesAnalytics.php` (sum('grand_total'))  
- `KPIDashboardService.php` (sum('grand_total'))  
- `POSService.php` (sum('discount_total'), sum('grand_total'))  
- `ReportsController.php` (sum('paid_total'), sum('due_total'))  
- `PosReportsExportController.php` (where('grand_total'), whereDate('posted_at'), orderBy('posted_at'))  
- `ClosePosDayJob.php` (get(['grand_total','paid_total']))

