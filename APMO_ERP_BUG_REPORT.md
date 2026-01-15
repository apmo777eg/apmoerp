# تقرير فحص Bugs لمشروع APMO ERP (Laravel 12 + Livewire 4)

> **ملاحظة:** تم فحص الكود داخل المشروع (مع تجاهل `database/` و `seeders` قدر الإمكان).
> المطلوب: حصر كل أنواع الـ Bugs (منطقية/مالية/حرجة/تكامل ERP/أمنية/تشغيلية) مع **مسار الملف** وشرح مختصر + اقتراح إصلاح.

---

## 0) ملخص تنفيذي (أخطر المشاكل)

### A) Breakers / تعطل التشغيل
1) **Branch API Context مكسور**: تعارض Route Binding مع Middleware `SetBranchContext` يجعل `{branch}` يُعامل كـ ID بينما البايندنج يرجعه Model → فشل في `whereKey()` + فشل فحص branch_id بالمقارنة الرقمية.
2) **Schedule + Queue Jobs غالبا لا تعمل** مع أغلب موديلات ERP بسبب `BranchScope` (Fail-Closed) في الـ Console بدون Explicit Branch Context.
3) **HRM Payroll مكسور**: عدم تطابق HRMService/Controllers مع Model `Payroll` (يستخدم period/basic/... غير موجودة).
4) **Commands مكسورة**: أوامر Artisan تنادي Methods غير موجودة (POS closeDay / Rental generateRecurringInvoices).
5) **Inventory ممكن ينحرف**: Events queued داخل Transactions مع `queue.after_commit = false` → Listener قد يعمل قبل commit.

---

## 1) Bugs حرجة (Critical)

### CRIT-01: Branch Route Binding يتعارض مع SetBranchContext (يكسر كل Branch API)
- **الملفات:**
  - `bootstrap/app.php` (سطر 24-26): Route::bind('branch' ...) يرجّع Branch Model
  - `app/Http/Middleware/SetBranchContext.php` (سطر 33-72): يتعامل مع route('branch') كأنه ID
- **المشكلة:**
  - `route('branch')` يصبح Branch object.
  - `SetBranchContext` يحاول `is_numeric($contextBranchId)` (سطر 52-56) → يفشل لأن القيمة object.
  - ثم `Branch::whereKey($branchId)` (سطر 71) → `whereKey` يتوقع scalar/array، ويميل إلى crash أو يرجّع null.
- **الأثر:** أغلب endpoints تحت `/api/v1/branches/{branch}/...` تتكسر (422/409/500) + branch mismatch false positives.
- **إصلاح سريع:**
  - في `SetBranchContext`: لو `$routeBranchId` instance of Branch خُد `$routeBranchId->getKey()`.
  - أو احذف Route::bind بالكامل واعتمد على implicit model binding + تعامل middleware مع Model.

---

### CRIT-02: BranchScope Fail-Closed يكسر Jobs/Schedule/Queue (نتائج فاضية)
- **الملف:** `app/Models/Scopes/BranchScope.php` (سطر 91-114)
- **المشكلة:** في console بدون user وبدون explicit branch id، يضيف شرط مستحيل:
  - `whereNull(id) AND whereNotNull(id)` ⇒ 0 rows.
- **أمثلة متأثرة مباشرة:**
  - `app/Jobs/ClosePosDayJob.php` (يستعلم Sale في queue → يرجّع فاضي)
  - `app/Jobs/GenerateRecurringInvoicesJob.php` (يستعلم RentalContract في queue → يرجّع فاضي)
  - `app/Services/HRMService.php` (يستعلم HREmployee في console → يرجّع فاضي)
  - أي Command/Job يقرأ موديلات ERP في console.
- **الأثر:** التقارير الدورية، إغلاق يوم POS، فواتير إيجار دورية، payroll… كلها ممكن “تشتغل” لكن بدون أي بيانات فعلية.
- **إصلاح سريع:**
  - إلزام كل Job/Scheduled task يحدد branch context:
    - قبل أي query: `BranchContextManager::setBranchContext($branchId)`
  - أو توسعة SAFE_CONSOLE_COMMANDS / أو إضافة Middleware لـ api-core/queue لعمل clear/set context بشكل صحيح.

---

### CRIT-03: Events Queued داخل Transactions + after_commit=false ⇒ انحراف مخزون
- **الملفات:**
  - `config/queue.php` (عدة drivers: after_commit => false)
  - `app/Livewire/Sales/Form.php` (سطر 437-439): dispatch SaleCompleted داخل DB::transaction
  - `app/Livewire/Purchases/Form.php` (سطر 359-363): dispatch PurchaseReceived داخل DB::transaction
  - `app/Services/POSService.php` (داخل transaction: event SaleCompleted)
  - Listeners: `app/Listeners/UpdateStockOnSale.php`, `app/Listeners/UpdateStockOnPurchase.php` (ShouldQueue)
- **المشكلة:** Listener قد يتنفذ قبل commit، وساعتها:
  - sale أو purchase أو items ممكن ما تكونش اتحفظت بالكامل → `sale->items` فاضية/ناقصة.
  - StockMovement يتسجل غلط أو لا يتسجل.
- **الأثر:** مخزون غير صحيح (خصم ناقص/مكرر) + صعوبة تحقيق مالي.
- **إصلاح سريع:**
  - فعل `after_commit => true` للـ queue driver المستخدم.
  - أو استخدم `event(...)->afterCommit()` أو Dispatch job/listener بعد commit.

---

### CRIT-04: Sale Void لا يعكس المخزون فعليا (Reference mismatch)
- **الملفات:**
  - `app/Services/SaleService.php` (سطر 180-207): يبحث عن StockMovements بـ reference_type = 'sale'
  - `app/Listeners/UpdateStockOnSale.php` (سطر 76-107): يسجل StockMovements بـ reference_type = 'sale_item' و reference_id = sale_item_id
- **المشكلة:** voidSale لن يجد أي حركات مخزون أصلية (لأنها على مستوى sale_item) ⇒ لا يضيف reversal.
- **الأثر:** void sale لا يرجع المخزون، وبيظهر كأن المخزون اتخصم للأبد.
- **إصلاح سريع:**
  - في voidSale: اجلب sale items ثم اعكس كل movement reference_type='sale_item' لكل item، أو خزّن mapping ثابت.
  - أو وحّد reference_type في كل النظام (إما sale أو sale_item).

---

### CRIT-05: HRM Payroll غير متوافق مع Model Payroll (بيسبب فشل/بيانات ناقصة)
- **الملفات:**
  - `app/Services/HRMService.php` (سطر 70-128): يستخدم `period`, `basic`, `allowances`, `deductions`, `net`
  - `app/Models/Payroll.php`: يعتمد year/month/net_salary/gross_salary… ولا يوجد period/basic/allowances…
  - `app/Http/Controllers/Branch/HRM/PayrollController.php` (سطر 13-48): يعمل filter على `period` ويكتب `paid_at`
- **المشكلة:** mismatch شامل بين service/controller وبين model/schema.
- **الأثر:** payroll run لن يسجل بيانات صحيحة، أو قد يسجل row ناقص (mass-assignment يتجاهل الأعمدة غير fillable) أو يقع في أخطاء DB.
- **إصلاح سريع:**
  - توحيد Contract: إمّا تعديل Payroll model ليدعم period/basic… أو تعديل HRMService ليملأ year/month وباقي أعمدة Payroll الفعلية.

---

## 2) Bugs عالية (High)

### HIGH-01: Commands تنادي Methods غير موجودة (تشغيل مكسور)
- **الملفات:**
  - `app/Console/Commands/GenerateRecurringInvoices.php` سطر 65: ينادي `RentalService->generateRecurringInvoices(...)`
  - `app/Services/RentalService.php` سطر 283: الموجود `generateRecurringInvoicesForMonth(...)` فقط
  - `app/Console/Commands/ClosePosDay.php` ينادي `POSService->closeDay(...)` (لا يوجد)
  - `app/Services/POSService.php` لا يحتوي closeDay
- **الأثر:** أوامر schedule (`routes/console.php`) ستفشل runtime.
- **إصلاح:** تعديل اسماء النداء لتطابق Methods الفعلية أو إضافة methods الناقصة.

---

### HIGH-02: RunPayroll Command يمرر باراميترات غلط
- **الملفات:**
  - `app/Console/Commands/RunPayroll.php` سطر 78: `runPayroll($branch, $periodStart)`
  - `app/Services/HRMService.php` signature: `runPayroll(string $period)`
- **الأثر:** TypeError أو نتائج غير منطقية.
- **إصلاح:** اجعل command يمرر `Y-m` فقط، ومعالجة branch بتحديد context أو فلترة employees.

---

### HIGH-03: Payment reminders system غير متسق (Array vs Object)
- **الملفات:**
  - `app/Services/AutomatedAlertService.php`: يرجع alerts كـ arrays
  - `app/Console/Commands/SendPaymentRemindersCommand.php` سطر 82-90: يتعامل مع alert كـ object (`$alert->customer`)
  - `app/Notifications/PaymentReminderNotification.php`: يتوقع object عند البناء (`$alert->reference`, `$alert->customer`…)
- **الأثر:** Crash عند إرسال التنبيهات (أو لن تُرسل).
- **إصلاح:** توحيد شكل الـ alert (DTO class) أو تعديل الكود ليستخدم array keys بشكل ثابت.

---

### HIGH-04: تعديل فاتورة بيع عبر Livewire يمسح items/payments بدون عكس المخزون
- **الملف:** `app/Livewire/Sales/Form.php` سطر 355-366
- **المشكلة:** عند edit:
  - `sale->items()->delete(); sale->payments()->delete();`
  - لا يتم حذف/عكس StockMovements السابقة ولا Journal Entries ولا Refunds.
- **الأثر المالي:** فقدان أثر محاسبي + مخزون مضروب (خصم قديم يظل قائم + خصم جديد يتضاف).
- **إصلاح:** بدل delete: استخدم versioning أو soft delete مع reversal لحركات المخزون/القيود.

---

### HIGH-05: تعديل Purchase عبر Livewire يمسح items وقد يسبب مخزون مضروب
- **الملف:** `app/Livewire/Purchases/Form.php` سطر 320-363
- **المشكلة:** على edit: delete للـ items بدون عكس مخزون (لو كانت purchase received سابقا).
- **الأثر:** تكرار إضافة المخزون أو عدم عكسه عند التعديل.
- **إصلاح:** لو status received: اعكس movements القديمة قبل إعادة التسجيل.

---

### HIGH-06: SmartNotificationsService يستخدم status غير متوافق مع باقي النظام
- **الملف:** `app/Services/SmartNotificationsService.php`
- **المشكلة:** overdue/reminders تعتمد `Sale.status = pending` بينما POS/Livewire غالبا يستخدم `completed`.
- **الأثر:** تنبيهات overdue/reminders لن تظهر أو تظهر غلط.
- **إصلاح:** توحيد حالات sales (state machine) أو تعديل queries لتستهدف الحالات الصحيحة + payment_status.

---

### HIGH-07: اختيار مستخدمي الإشعارات حسب user.branch_id فقط (يتجاهل multi-branch)
- **الملف:** `app/Services/SmartNotificationsService.php` (getUsersForNotification)
- **المشكلة:** `when($branchId) -> where('branch_id', $branchId)` بينما صلاحيات الفروع غالبا من pivot `branch_user`.
- **الأثر:** مستخدم لديه صلاحية على الفرع عبر pivot لكن primary branch مختلف → لن يستلم إشعارات.
- **إصلاح:** join على branch_user أو استخدام BranchContextManager للوصول.

---

### HIGH-08: Jobs تعتمد على موديلات BranchScoped بدون ضبط BranchContextManager
- **الملفات:**
  - `app/Jobs/ClosePosDayJob.php`
  - `app/Jobs/GenerateRecurringInvoicesJob.php`
- **المشكلة:** تعمل في queue (console) بدون user → BranchScope يرجع 0 rows.
- **الإصلاح:** أضف branchId للـ job payload واستدع BranchContextManager::setBranchContext قبل أي query.

---

## 3) Bugs متوسطة (Medium)

### MED-01: Middleware EnsureBranchAccess في ترتيب غير منطقي داخل api-auth
- **الملف:** `bootstrap/app.php` group `api-auth`
- **المشكلة:** EnsureBranchAccess يأتي قبل Authenticate و قبل إعداد branch في بعض المسارات.
- **الأثر:** سلوك غير متوقع (401 مبكر/عدم تنفيذ تحقق branch كما هو مقصود).
- **إصلاح:** ضع EnsureBranchAccess بعد SetBranchContext أو دمجه داخل SetBranchContext.

---

### MED-02: HasRequestContext يقرأ المستخدم من guard ثابت (api) وليس guard الحالي
- **الملف:** `app/Traits/HasRequestContext.php`
- **المشكلة:** `Auth::guard('api')->user() ?? Auth::user()` بينما الـ API يستخدم sanctum.
- **الأثر:** currentUser قد يكون null أو مستخدم غلط في سياقات معينة.
- **إصلاح:** الاعتماد على Auth::user فقط بعد AssignGuard، أو قراءة guard الحالي من Auth::getDefaultDriver.

---

### MED-03: علاقة Customer->payments غير صحيحة
- **الملفات:**
  - `app/Models/Customer.php` سطر 113-116: hasMany SalePayment
  - `app/Models/SalePayment.php`: لا يحتوي customer_id
- **الأثر:** customer->payments دائماً فاضية، مما يكسر أي منطق يعتمد عليها.
- **إصلاح:** استخدم hasManyThrough عبر Sale، أو أضف customer_id لـ sale_payments (إن كان مقصود).

---

### MED-04: scopeWithinCreditLimit يستبعد العملاء الذين credit_limit = NULL
- **الملف:** `app/Models/Customer.php` سطر 128-130
- **المشكلة:** `whereRaw('balance <= credit_limit')` مع NULL يؤدي لعدم تطابق.
- **الأثر:** فلترة غلط للعملاء “بدون حد ائتمان”.
- **إصلاح:** `whereNull(credit_limit)->orWhereRaw(balance <= credit_limit)` أو استخدام COALESCE.

---

### MED-05: GenerateRecurringInvoicesJob منطق مختلف عن RentalService (تضارب ERP)
- **الملف:** `app/Jobs/GenerateRecurringInvoicesJob.php`
- **مشاكل:** status (unpaid vs pending) + due_date (endOfMonth vs +7 days) + code uniqid + بدون lock/transaction.
- **الأثر:** دبل فواتير/تضارب حالة/صعوبة متابعة.
- **إصلاح:** اجعل job يستدعي RentalService الرسمي فقط، أو احذف أحد المسارين.

---

### MED-06: ExpireRentalContracts يستخدم مقارنة تاريخ لا تطابق الوصف
- **الملف:** `app/Console/Commands/ExpireRentalContracts.php`
- **المشكلة:** الوصف “before or on” لكن query يستخدم `<` وليس `<=`.
- **الأثر:** عقد ينتهي “اليوم” قد لا يتم إنهاؤه تلقائياً.
- **إصلاح:** استخدم `<=` أو عدّل الوصف.

---

### MED-07: ScheduledReportService حساب مخزون بدون branch scoping في بعض التقارير
- **الملف:** `app/Services/ScheduledReportService.php` (fetchProductsReportData)
- **المشكلة:** stock subquery يجمع stock_movements لكل الفروع لنفس product_id (لو كان product مشترك).
- **الأثر:** تقارير مخزون غير صحيحة في multi-branch.
- **إصلاح:** استخدم branch stock expression أو فلترة warehouse.branch_id.

---

### MED-08: SetBranchContext لا يضبط Explicit Branch Context للـ BranchScope
- **الملف:** `app/Http/Middleware/SetBranchContext.php`
- **المشكلة:** يضع request attributes فقط ولا يستدعي BranchContextManager::setBranchContext.
- **الأثر:** BranchScope قد يسمح “كل الفروع المتاحة للمستخدم” وليس الفرع الحالي، مما يسبب عدم اتساق context.
- **إصلاح:** استدع BranchContextManager::setBranchContext((int)$branch->getKey()) في middleware.

---

## 4) Bugs منخفضة (Low) لكن مهمة للـ ERP Quality

### LOW-01: closeDay في Branch PosController مجرد stub
- **الملف:** `app/Http/Controllers/Branch/PosController.php` (closeDay)
- **الأثر:** endpoint يعطي “Closed” بدون أي إغلاق فعلي أو ترحيل أو قيود.

### LOW-02: Performance: بعض الموديلات تستخدم eager-load دائم (protected with)
- **أمثلة:** `app/Models/Sale.php`, `app/Models/Purchase.php`
- **الأثر:** حمل زائد عند paginations الكبيرة.

---

## 5) توصيات إصلاح مرتبة بالأولوية

1) **إصلاح CRIT-01**: خَلّي SetBranchContext يتعامل مع Branch Model أو احذف Route::bind.
2) **ضبط سياق الفروع للـ Jobs**: كل Job يشتغل على branch لازم يأخذ branchId ويستدعي BranchContextManager::setBranchContext.
3) **Inventory consistency**: فعّل after_commit أو dispatch بعد commit.
4) **توحيد reference_type للـ stock_movements** (sale_item vs sale) ثم إصلاح voidSale.
5) **توحيد HRM Payroll schema** (Service/Controller/Model) وإزالة period إذا غير موجود أو إضافته بشكل صحيح.

---

## 6) قائمة الملفات الأكثر احتياجا لمراجعة عاجلة
- bootstrap/app.php
- app/Http/Middleware/SetBranchContext.php
- app/Models/Scopes/BranchScope.php
- config/queue.php
- app/Services/SaleService.php
- app/Livewire/Sales/Form.php
- app/Livewire/Purchases/Form.php
- app/Services/HRMService.php
- app/Http/Controllers/Branch/HRM/PayrollController.php
- app/Console/Commands/GenerateRecurringInvoices.php
- app/Console/Commands/ClosePosDay.php
- app/Console/Commands/SendPaymentRemindersCommand.php
- app/Services/SmartNotificationsService.php
- app/Services/AutomatedAlertService.php

