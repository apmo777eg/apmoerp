# تقرير فحص الـ UI + إعادة تصميم الـ Settings (APMO ERP v66)

- تاريخ الفحص: **2026-01-22**

- نطاق التقرير: **مشاكل واجهة المستخدم (UI/UX) + إعدادات النظام (Settings) + Templates** مع التركيز على إزالة أي إدخالات تقنية (JSON/Cron/Validation rules/Keys) من واجهة المدير وتحويلها لاختيارات (Checkbox/Radio/Select/Dropdown).

---

## 1) ملخص تنفيذي (المشاكل الأعلى أولوية)

### A) الـ Settings الحالية فيها مشاكل جوهرية (Fragmentation + Options زينة)

- فيه **أكثر من نظام إعدادات** داخل المشروع: 
  - `SettingsService` + `config/settings.php` (تعريفات Typed) 
  - Livewire صفحات Settings متعددة بتكتب مباشرة في جدول `system_settings` بكاشات مختلفة 
  - صفحة/Controller بتسمح بتعديل إعدادات Key/Value مباشرة (مناسبة للمطور فقط)
- النتيجة: **إعدادات كتير بتتسجل في الداتا بيز لكن مش بتتقرأ/مش بتأثر في أي منطق** (Options زينة).

### B) واجهات بتطلب إدخال تقني من Admin (مرفوض حسب طلبك)

الأماكن اللي بتطلب JSON/Cron/Validation Rules/Keys بشكل مباشر:
- System Settings (Key/Value editor)
- Branch Settings (Key/Value per branch)
- Report Templates (Default Filters JSON + Export Columns كـ نص)
- Scheduled Reports (Cron Expression + Filters JSON)
- Custom Fields (field_key + validation_rules بصيغة Laravel)

### C) Bugs تسبب إن الـ Settings **مش شغالة فعلياً** حتى لو الـ UI موجودة

- **Mismatch في أسماء مفاتيح الإعدادات** بين UI / config / business logic (مثال: negative stock + default warehouse + payment terms).
- بعض الموديلات بتولد أرقام فواتير/أوامر شراء بطريقة عشوائية، فـ إعدادات (starting number) تظل بلا تأثير.


---

## 2) سيناريو المشروع بشكل عام (من منظور الـ UI + الـ Backend)

- التطبيق Laravel + Livewire، بفكرة ERP متعدد الوحدات (Modules) + فروع (Branches) + صلاحيات.
- الـ Admin يدير:
  - إعدادات النظام (System/Unified/Advanced settings)
  - تمكين/تعطيل الـ Modules على مستوى النظام أو الفرع
  - إدارة Custom Fields للوحدات
  - Reports: تشغيل/تصدير (Web/Excel/PDF) + Templates + Scheduled Reports
- الـ Backend يعتمد على جدول `system_settings` كـ KV store مع كاش.
- الـ Templates المقصودة في الكود تشمل بشكل واضح: **Report Templates** و**Scheduled Reports**، إضافة إلى وجود مفاتيح Settings في `config/settings.php` تخص طباعة/Receipts لكنها غير مربوطة بمنطق الطباعة الحالي.


---

## 3) UI Bugs / UX Debts (مركّز على Settings + Templates)

### 3.1 System Settings: Key/Value Editor (غير مناسب للإدارة)

**دليل سريع (File/Line):**
- `resources/views/livewire/admin/settings/system-settings.blade.php` سطر **73**: `wire:model="rows.{{ $index }}.key"`
- نفس الملف سطر **82**: `wire:model="rows.{{ $index }}.value"`
- نفس الملف سطر **88**: `wire:model="rows.{{ $index }}.group"`
- نفس الملف سطر **94**: `wire:model="rows.{{ $index }}.is_public"`

- **المشكلة:** الصفحة تسمح للمدير بكتابة `key` و`value` مباشرة (قد تكون JSON) (ملف: `resources/views/livewire/admin/settings/system-settings.blade.php`).
- **الأثر:**
  - Admin لازم يعرف أسماء المفاتيح بالضبط (تقني جداً)
  - خطر إدخال قيم غلط بدون Typed validation حقيقي
  - صعب التأكد إن الإعدادات دي بتأثر فعلاً في النظام
- **دليل:** وجود input للـ key/value مباشر.
  - سطر مثال: key/value inputs داخل الجدول.

**اقتراح جذري:** إلغاء الـ Key/Value editor من واجهة الـ Admin واستبداله بـ Settings UI مبنية على تعريفات `config/settings.php` (Typed UI).


### 3.2 Branch Settings: Key/Value per branch (نفس المشكلة + أخطر)

**دليل سريع (File/Line):**
- `resources/views/livewire/admin/settings/branch-settings.blade.php` سطر **59**: `<input type="text" wire:model="rows.{{ $index }}.key"`
- نفس الملف سطر **66**: `<input type="text" wire:model="rows.{{ $index }}.value"`

- **المشكلة:** نفس فكرة key/value لكن على مستوى الفرع، وتخزين JSON داخل `branches.settings`.
- **الأثر:** أي خطأ هنا يعمل break لجزء من الفرع + صعب عمل support.
- **اقتراح:** نفس Settings UI ولكن مع ميزة `Override per branch` (Toggle) بدل كتابة keys.


### 3.3 Purchases Settings: مفاتيح غلط => Options زينة

**دليل سريع (File/Line):**
- `app/Livewire/Admin/Settings/PurchasesSettings.php` سطر **53**: `$this->purchase_invoice_prefix = $settings['purchases.invoice_prefix'] ?? 'PO-';`
- `app/Models/Purchase.php` سطر **91**: `$prefix = setting('purchases.purchase_order_prefix', 'PO-');`

- صفحة `PurchasesSettings` بتقرأ/تكتب مفاتيح مثل `purchases.invoice_prefix` و `purchases.payment_terms_days`.
- بينما نموذج الشراء `Purchase` يستخدم `setting('purchases.purchase_order_prefix', 'PO-')` لتوليد رقم الأمر.
- **النتيجة:** تغيير prefix من UI غالباً **لن يغيّر** سلوك توليد الرقم (لأن المفتاح مختلف).
- **مطلوب:** توحيد المفاتيح إلى ما هو معرف في `config/settings.php` + ما يستخدمه الـ model.


### 3.4 Warehouse Settings: مجموعة مفاتيح `warehouse.*` غير مستخدمة عملياً

- `WarehouseSettings` تكتب مفاتيح مثل `warehouse.default_warehouse_location` و`warehouse.enable_negative_stock`.
- منطق المخزون الأساسي لا يقرأ هذه المفاتيح؛ فيه مفاتيح أخرى مستخدمة في الخدمات/الـ controllers.
- **النتيجة:** إعدادات Warehouse الحالية غالباً **زينة** أو غير مؤثرة.


### 3.5 Bug خطير: Allow Negative Stock غير موحّد

**دليل سريع (File/Line):**
- `app/Services/POSService.php` سطر **147**: `$allowNegativeStock = (bool) setting('pos.allow_negative_stock', false);`
- `app/Services/StockService.php` سطر **359**: `$allowNegativeStock = (bool) setting('inventory.allow_negative_stock', false);`

- POS check بيقرأ `pos.allow_negative_stock` (وهذا المفتاح موجود) لكنه يطبق فقط في خطوة التحقق في `POSService`.
- لكن **تطبيق قاعدة عدم السالب** فعلياً عند إنشاء حركة المخزون داخل `StockService` يقرأ مفتاح مختلف: `inventory.allow_negative_stock` (غير معرف في `config/settings.php`).
- **النتيجة:** حتى لو فعلت allow negative stock من الـ UI، ممكن تظل حركات المخزون ترفض السالب في طبقة أخرى.
- **الحل:** توحيد المفتاح (مثلاً `inventory.allow_negative_stock`) وإضافته لتعريفات settings + جعل UI تعدله، أو عمل alias واضح.


### 3.6 Bug: Default Warehouse ID key غير صحيح + لا يوجد UI لضبطه

**دليل سريع (File/Line):**
- `app/Http/Controllers/Api/V1/InventoryController.php` سطر **384**: `$defaultWarehouseId = setting('default_warehouse_id');`

- بعض Controllers في API تستخدم `setting('default_warehouse_id')`.
- تعريفات settings الموجودة تتضمن `inventory.default_warehouse_id`.
- **النتيجة:** `default_warehouse_id` غير معرف => SettingsService يرجّع default => fallback logic يصبح شبه عشوائي.
- **الحل:** تحديث الكود لاستخدام `inventory.default_warehouse_id` + إضافة UI اختيار Warehouse (Select searchable) لهذه القيمة.


### 3.7 Bug: Costing Method key غير صحيح

- الكود يستخدم `setting('inventory.costing_method', 'fifo')`.
- تعريف settings يحتوي `inventory.default_costing_method`.
- **الحل:** توحيد المفتاح (اختيار واحد فقط) وتحديث UI + الخدمات.


### 3.8 Report Templates (TEMPLETES): إدخال JSON + أعمدة كنص

**دليل سريع (File/Line):**
- `resources/views/livewire/admin/reports/templates-manager.blade.php` سطر **210**: `{{ __('Default Filters (JSON)') }}`
- نفس الملف سطر **225**: `{{ __('Export Columns') }}`

- UI الحالية تسمح بإدخال:
  - `Default Filters (JSON)`
  - `Export Columns` كنص مفصول بفواصل
- ده بيجبر الـ Admin يكتب JSON ويفهم أسماء الأعمدة (تقني جداً).
- **الحل UX:**
  1) **Filter Builder UI**: إضافة/حذف Filters عبر Rows (Key + Operator + Value) مع Types (date/number/select)
  2) **Column Picker UI**: قائمة Checkboxes/Multi-select للأعمدة المتاحة (بـ Search)
  3) إخفاء/قفل الـ Key override للمطور فقط.


### 3.9 Scheduled Reports: Cron + Filters JSON

**دليل سريع (File/Line):**
- `resources/views/livewire/admin/reports/scheduled-manager.blade.php` سطر **198**: `{{ __('Cron Expression') }}`
- نفس الملف سطر **225**: `{{ __('Custom Filters (JSON)') }}`

- رغم وجود واجهة سهلة للتكرار (يومي/أسبوعي/شهري)، ما زال في Advanced mode يسمح بتعديل Cron مباشرة + JSON filters.
- **الحل:**
  - اجعل Cron read-only (للـ Admin) أو أخفه تماماً.
  - Filters بنفس Filter Builder.
  - ربط Scheduled report دائمًا بـ Template، والتعديل يتم من template وليس JSON هنا.


### 3.10 Custom Fields: field_key + validation_rules (تقني)

**دليل سريع (File/Line):**
- `resources/views/livewire/admin/modules/fields/form.blade.php` سطر **28**: `<input type="text" wire:model="field_key" class="erp-input w-full font-mono" pattern="[a-z_]+" required {{ $fieldId ? 'readonly' : '' }}>`
- نفس الملف سطر **81**: `<input type="text" wire:model="validation_rules" class="erp-input w-full font-mono" placeholder="e.g. required|max:255">`

- UI تطلب من الـ Admin يكتب:
  - `field_key` (اسم داخلي لازم يكون snake_case)
  - `validation_rules` بصيغة Laravel rules string
- **الحل UX:**
  - auto-generate للـ key من label (مع إمكانية تعديل في Advanced فقط)
  - Validation Rules Builder: Required checkbox + Min/Max inputs + Numeric/Email/Date toggles + Unique toggle … ثم توليد rules string داخلياً.


---

## 4) Settings Options زينة (تُحفظ لكن لا تُستخدم خارج صفحات Settings)

- أثناء الفحص، تم رصد **54** مفتاح Settings يتم ضبطه من صفحات Settings ولكنه **غير مُشار إليه** في أي كود تشغيل (PHP/Blade/JS) خارج مجلد `app/Livewire/Admin/Settings`.

- هذا يعني عملياً: المستخدم يغيرها من UI لكن النظام لن يتأثر (إلا إذا كان المقصود تنفيذها لاحقاً).


### 4.1 قائمة المفاتيح (Appendix A)

> **ملاحظة:** بعضها مفيد وموجود تعريفه في `config/settings.php` لكن لم يتم ربطه بالمنطق بعد.


#### Appendix A — مفاتيح غير مستخدمة خارج الـ Settings UI (مرتبة حسب صفحة الإعدادات)


- **app/Livewire/Admin/Settings/AdvancedSettings.php** (7 keys)

  - `advanced.max_payload_size`
  - `advanced.pagination_default`
  - `advanced.progress_bar_color`
  - `advanced.show_progress_bar`
  - `advanced.spa_navigation_enabled`
  - `app.currency`
  - `app.logo`

- **app/Livewire/Admin/Settings/PurchasesSettings.php** (9 keys)

  - `purchases.approval_threshold`
  - `purchases.auto_receive_on_purchase`
  - `purchases.enable_3way_matching`
  - `purchases.enable_grn`
  - `purchases.enable_purchase_requisitions`
  - `purchases.grn_validity_days`
  - `purchases.invoice_prefix`
  - `purchases.invoice_starting_number`
  - `purchases.require_purchase_approval`

- **app/Livewire/Admin/Settings/UnifiedSettings.php** (28 keys)

  - `accounting.coa_template`
  - `advanced.enable_webhooks`
  - `app.date_format`
  - `backup.auto_backup`
  - `backup.storage`
  - `branding.favicon`
  - `branding.favicon_id`
  - `branding.logo`
  - `branding.logo_id`
  - `branding.primary_color`
  - `branding.secondary_color`
  - `branding.tagline`
  - `hrm.late_arrival_threshold`
  - `hrm.working_days_per_week`
  - `hrm.working_hours_per_day`
  - `inventory.stock_alert_threshold`
  - `inventory.use_per_product_threshold`
  - `notifications.new_order`
  - `notifications.payment_due`
  - `pos.auto_print_receipt`
  - `pos.rounding_rule`
  - `rental.grace_period_days`
  - `rental.penalty_type`
  - `rental.penalty_value`
  - `sales.invoice_starting_number`
  - `security.enable_audit_log`
  - `system.multi_branch`
  - `system.require_branch_selection`

- **app/Livewire/Admin/Settings/WarehouseSettings.php** (10 keys)

  - `warehouse.auto_allocate_stock`
  - `warehouse.default_warehouse_location`
  - `warehouse.enable_barcode_scanning`
  - `warehouse.enable_batch_tracking`
  - `warehouse.enable_multi_location`
  - `warehouse.enable_negative_stock`
  - `warehouse.enable_serial_tracking`
  - `warehouse.require_approval_for_adjustments`
  - `warehouse.stock_allocation_method`
  - `warehouse.stock_count_frequency_days`

---

## 5) مفاتيح Settings مستخدمة في الكود لكنها **غير مُعرّفة** في `config/settings.php` (Bugs)

هذه المفاتيح يتم استدعاؤها عبر `setting('...')` لكن لا يوجد لها تعريف (type/default/options) في `config/settings.php`، مما يجعلها ترجع default دائماً أو تسبب سلوك غير متوقع:

- `default_warehouse_id` (مستخدمة في: app/Http/Controllers/Api/V1/InventoryController.php, app/Http/Controllers/Api/V1/OrdersController.php)
- `inventory.allow_negative_stock` (مستخدمة في: app/Listeners/UpdateStockOnSale.php, app/Repositories/StockMovementRepository.php, app/Services/StockService.php)
- `inventory.costing_method` (مستخدمة في: app/Services/CostingService.php)
- `purchases.payment_terms_days` (مستخدمة في: app/Services/FinancialReportService.php)
- `rental.buffer_hours` (مستخدمة في: app/Services/RentalService.php)
- `sales.payment_terms_days` (مستخدمة في: app/Services/FinancialReportService.php)

---

## 6) إعادة تصميم جذرية للـ Settings (المطلوب لتنفيذ طلبك)

### 6.1 الهدف
- أي إعداد يجب أن يظهر للمدير كاختيارات واضحة (Checkbox/Radio/Select/Dropdown) أو Inputs بسيطة مع Validation، بدون أي JSON/Cron/Rules/Keys.
- منع وجود Settings زينة: كل setting يظهر في UI يجب أن يكون مستهلك (Used) في مكان واضح.

### 6.2 التصميم المقترح (Settings Registry + UI Generator)

1) **Source of truth**: ملف `config/settings.php` يصبح Registry نهائي:
   - `type`: boolean/string/int/float/select/multi_select/date/time/color/file/textarea
   - `options`: للقوائم
   - `validation`: rules (internal)
   - `scope`: global / branch / user
   - `env_key`: (اختياري) لو القيمة يجب أن تأتي من ENV
   - `sensitive`: (اختياري) لتشفير القيمة في DB وإخفائها في UI
2) **Helper** واحد للقراءة: `setting('group.key')` + يدعم override:
   - ENV override (للأسرار)
   - Branch override (لو scope يسمح)
   - DB value
   - Default
3) **Settings UI واحدة** تولّد Tabs تلقائياً من Registry بدل تعدد صفحات.
4) **منع أي إدخال raw key/value** في الـ Admin UI.


### 6.3 أنواع Controls خاصة لازمة (بدون JSON)

- **Filter Builder**: بديل `filtersJson/defaultFiltersJson`.
- **Column Picker**: بديل `exportColumnsText`.
- **Validation Builder**: بديل `validation_rules`.
- **Template Selector**: اختيار template من presets (للإيصالات/الفواتير/التقارير).


### 6.4 إزالة التضارب الحالي

- توحيد الكاش: حالياً هناك مفاتيح كاش مختلفة (`system_settings`, `system_settings_all`) => يجب توحيدها لضمان أن أي تعديل يظهر فوراً.
- توحيد الكتابة: كل صفحات Settings يجب أن تستخدم `SettingsService` فقط.
- Deprecate: `SystemSettingController` (Update arbitrary key/value) أو قصره على super-admin + feature-flag.


---

## 7) UI Consistency (مشاكل شكلية قابلة للتحسين سريعاً)

- تم رصد مؤشرات عامة (مش بالضرورة bugs functional) عبر فحص ملفات Blade:
  - Inline styles موجودة في **42** ملف.
  - Hardcoded hex colors موجودة في **22** ملف.
  - `bg-white` بدون `dark:bg-*` (احتمال مشكلة Dark Mode) في **103** ملف.


### أمثلة (ليس حصر كامل)

- **inline_styles**:
  - `resources/views/admin/store/orders-export-pdf.blade.php`
  - `resources/views/admin/store/orders-export-web.blade.php`
  - `resources/views/components/loading-indicator.blade.php`
  - `resources/views/components/modal.blade.php`
  - `resources/views/components/skeleton-loader.blade.php`
  - `resources/views/components/toast-container.blade.php`
  - `resources/views/components/ui/keyboard-shortcuts.blade.php`
  - `resources/views/components/ui/skeleton.blade.php`
  - `resources/views/emails/scheduled-report.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/navbar.blade.php`
  - `resources/views/livewire/admin/media-library.blade.php`
  - `resources/views/livewire/admin/modules/module-manager.blade.php`
  - `resources/views/livewire/admin/settings/unified-settings.blade.php`
  - `resources/views/livewire/admin/setup-wizard.blade.php`
- **hardcoded_hex_colors**:
  - `resources/views/admin/reports/inventory-export-pdf.blade.php`
  - `resources/views/admin/reports/pos-export-pdf.blade.php`
  - `resources/views/admin/store/orders-export-pdf.blade.php`
  - `resources/views/admin/store/orders-export-web.blade.php`
  - `resources/views/emails/password-reset.blade.php`
  - `resources/views/emails/scheduled-report.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/guest.blade.php`
  - `resources/views/livewire/admin/backup-restore.blade.php`
  - `resources/views/livewire/admin/settings/unified-settings.blade.php`
  - `resources/views/livewire/admin/setup-wizard.blade.php`
  - `resources/views/livewire/components/export-column-selector.blade.php`
  - `resources/views/livewire/dashboard/customizable-dashboard.blade.php`
  - `resources/views/livewire/dashboard/index.blade.php`
  - `resources/views/livewire/documents/tags/form.blade.php`
- **bg_white_missing_dark_bg**:
  - `resources/views/admin/reports/inventory-export-web.blade.php`
  - `resources/views/admin/reports/pos-export-web.blade.php`
  - `resources/views/components/export-modal.blade.php`
  - `resources/views/components/form/checkbox.blade.php`
  - `resources/views/components/load-more.blade.php`
  - `resources/views/components/sidebar/link.blade.php`
  - `resources/views/components/sidebar/section.blade.php`
  - `resources/views/components/toast-container.blade.php`
  - `resources/views/errors/403.blade.php`
  - `resources/views/errors/404.blade.php`
  - `resources/views/errors/500.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/navbar.blade.php`
  - `resources/views/livewire/accounting/index.blade.php`
  - `resources/views/livewire/admin/branches/modules.blade.php`
- **raw_form_elements**:
  - `resources/views/components/attachments/uploader.blade.php`
  - `resources/views/components/export-modal.blade.php`
  - `resources/views/components/form/checkbox.blade.php`
  - `resources/views/components/form/input.blade.php`
  - `resources/views/components/form/select.blade.php`
  - `resources/views/components/form/textarea.blade.php`
  - `resources/views/components/import-wizard.blade.php`
  - `resources/views/components/sidebar/enhanced.blade.php`
  - `resources/views/components/ui/data-table.blade.php`
  - `resources/views/components/ui/form/input.blade.php`
  - `resources/views/components/ui/form/select.blade.php`
  - `resources/views/components/ui/form/textarea.blade.php`
  - `resources/views/layouts/sidebar-new.blade.php`
  - `resources/views/livewire/accounting/accounts/form.blade.php`
  - `resources/views/livewire/accounting/index.blade.php`

### Icon-only buttons بدون aria-label (Accessibility)

- تم رصد **76** ملفات تحتوي على أزرار أيقونات بدون `aria-label` (قد يؤثر على accessibility).

  - `resources/views/components/attachments/uploader.blade.php`
  - `resources/views/components/error-alert.blade.php`
  - `resources/views/components/module-context-selector.blade.php`
  - `resources/views/components/reports/saved-views.blade.php`
  - `resources/views/components/sidebar/enhanced.blade.php`
  - `resources/views/components/success-alert.blade.php`
  - `resources/views/components/toast-container.blade.php`
  - `resources/views/components/ui/keyboard-shortcuts.blade.php`
  - `resources/views/components/ui/undo-notification.blade.php`
  - `resources/views/components/validation-errors.blade.php`
  - `resources/views/errors/403.blade.php`
  - `resources/views/errors/404.blade.php`
  - `resources/views/errors/500.blade.php`
  - `resources/views/layouts/navbar.blade.php`
  - `resources/views/livewire/accounting/index.blade.php`

---

## 8) قائمة إصلاحات مقترحة (Prioritized)

### P0 — لازم تتعمل قبل أي UI جديدة (علشان الـ Settings تبقى شغالة)
1) توحيد مفاتيح Settings بين UI/Config/Logic:
   - `inventory.allow_negative_stock` vs `pos.allow_negative_stock`
   - `inventory.default_warehouse_id` بدل `default_warehouse_id`
   - `inventory.default_costing_method` بدل `inventory.costing_method`
   - Purchases: استخدام `purchases.purchase_order_prefix` بدل `purchases.invoice_prefix`
2) إلغاء/إخفاء صفحات key/value (SystemSettings + BranchSettings) للـ Admin.


### P1 — تبسيط Templates
- Report Templates + Scheduled Reports:
  - إضافة Filter Builder + Column Picker.
  - إلغاء إدخال JSON/Cron.


### P2 — إعادة بناء Settings UI (Generator)
- صفحة واحدة مبنية على `config/settings.php` (Tabs + Fields + validation + scope override).
- دعم env override + encryption.


### P3 — تحسينات شكلية
- تقليل inline styles/hex colors وربطها بمتغيرات Branding.
- تغطية dark mode بشكل consistent.
- إضافة loading states ومعايير accessibility للأيقونات.


---

## 9) ملاحق إضافية

### Appendix B — جدول مختصر (Key → عدد المراجع خارج Settings)

| Key | External Refs | Set In |
|---|---:|---|

| `accounting.coa_template` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `advanced.enable_webhooks` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `advanced.max_payload_size` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `advanced.pagination_default` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `advanced.progress_bar_color` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `advanced.show_progress_bar` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `advanced.spa_navigation_enabled` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `app.currency` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `app.date_format` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `app.logo` | 0 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `backup.auto_backup` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `backup.storage` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.favicon` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.favicon_id` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.logo` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.logo_id` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.primary_color` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.secondary_color` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `branding.tagline` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.late_arrival_threshold` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.working_days_per_week` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.working_hours_per_day` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `inventory.stock_alert_threshold` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `inventory.use_per_product_threshold` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `notifications.new_order` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `notifications.payment_due` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `pos.auto_print_receipt` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `pos.rounding_rule` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `purchases.approval_threshold` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.auto_receive_on_purchase` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.enable_3way_matching` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.enable_grn` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.enable_purchase_requisitions` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.grn_validity_days` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.invoice_prefix` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.invoice_starting_number` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `purchases.require_purchase_approval` | 0 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `rental.grace_period_days` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `rental.penalty_type` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `rental.penalty_value` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `sales.invoice_starting_number` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `security.enable_audit_log` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `system.multi_branch` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `system.require_branch_selection` | 0 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `warehouse.auto_allocate_stock` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.default_warehouse_location` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.enable_barcode_scanning` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.enable_batch_tracking` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.enable_multi_location` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.enable_negative_stock` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.enable_serial_tracking` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.require_approval_for_adjustments` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.stock_allocation_method` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `warehouse.stock_count_frequency_days` | 0 | `app/Livewire/Admin/Settings/WarehouseSettings.php` |
| `advanced.lazy_load_components` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `company.email` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `company.phone` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `export.chunk_size` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `export.default_format` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `export.include_headers` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `export.max_export_rows` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `export.pdf_orientation` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `export.pdf_paper_size` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.api_key` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.app_id` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.auth_domain` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.enabled` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.messaging_sender_id` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.project_id` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.storage_bucket` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `firebase.vapid_key` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `hrm.health_insurance_deduction` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.housing_allowance_type` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.housing_allowance_value` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.meal_allowance` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.transport_allowance_type` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `hrm.transport_allowance_value` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `inventory.costing_method` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `notifications.late_payment_enabled` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `notifications.late_penalty_percent` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `notifications.low_stock_enabled` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `notifications.low_stock_threshold` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `notifications.rental_reminder_days` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `pos.allow_negative_stock` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `purchases.payment_terms_days` | 1 | `app/Livewire/Admin/Settings/PurchasesSettings.php` |
| `sales.payment_terms_days` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `security.session_timeout` | 1 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `sms.3shm.appkey` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.3shm.authkey` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.3shm.enabled` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.3shm.sandbox` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.smsmisr.enabled` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.smsmisr.password` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.smsmisr.sandbox` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.smsmisr.sender_id` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.smsmisr.username` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.auto_save_forms` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.auto_save_interval` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.compact_tables` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.enable_keyboard_shortcuts` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.show_breadcrumbs` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.sidebar_collapsed` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.toast_duration` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `ui.toast_position` | 1 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `advanced.cache_ttl` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php, app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `advanced.enable_api` | 2 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `advanced.enable_query_logging` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `advanced.slow_query_threshold` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `app.locale` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `backup.enabled` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `backup.frequency` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php, app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `backup.include_uploads` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `backup.retention_days` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php, app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `backup.time` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `company.name` | 2 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `notifications.low_stock` | 2 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `sales.invoice_prefix` | 2 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `security.password_expiry_days` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `security.recaptcha_site_key` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `security.session_lifetime` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `sms.provider` | 2 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `pos.max_discount_percent` | 3 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `security.2fa_enabled` | 3 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `security.2fa_required` | 3 | `app/Livewire/Admin/Settings/AdvancedSettings.php, app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `security.max_sessions` | 3 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `security.recaptcha_enabled` | 3 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `security.recaptcha_secret_key` | 3 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |
| `app.timezone` | 4 | `app/Livewire/Admin/Settings/AdvancedSettings.php, app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `general.default_currency` | 23 | `app/Livewire/Admin/Settings/UnifiedSettings.php` |
| `app.name` | 28 | `app/Livewire/Admin/Settings/AdvancedSettings.php` |

# ApmoERP67 — تحسينات الـ Backend و Livewire Forms (تركيز UI/Settings/Templates)

> النسخة محل الفحص: `apmoerp67.zip` (Laravel 12 + Livewire 4.0.1)

الهدف هنا: **تبسيط الـ UI** بحيث أي شيء “متقدم” (JSON / Cron / Keys / أكواد) يتحول لاختيارات واضحة (Select / Radio / Checkbox / Multi-select)، وبنفس الوقت **نضمن أن كل اختيار فعلاً مؤثر في النظام** وليس “زينة”.

---

## 1) مشاكل حرجة في Settings (بتأثر مباشرة على الـ UI)

### 1.1 قراءة/كتابة الإعدادات في UnifiedSettings “غير متوافقة” مع تصميم جدول system_settings
**الملفات:**
- `app/Livewire/Admin/Settings/UnifiedSettings.php`
- `app/Models/SystemSetting.php`
- `app/Services/SettingsService.php`
- `database/migrations/2026_01_07_000003_create_user_activity_tables.php` (فيه جدول `system_settings`)

**المشكلة:**
- `UnifiedSettings` بيعمل:
  - `SystemSetting::pluck('value','key')` -> بيرجع **قيمة خام من DB** (بتتجاهل casts)  
  - وبعدها بيعمل `(bool) $value` على string.  
  - في PHP: أي string غير فاضية تعتبر **true**. يعني `"0"` أو `"false"` هيتحولوا true → **Toggle في الـ UI يطلع شغال وهو مش شغال، أو العكس.**
- كمان `setSetting()` بيخزن `value` كسكيلار (string/bool/int) بدون `type`/`is_encrypted`/`options`… بينما `SettingsService` بيخزن القيمة غالباً كـ array `[value]` وبيعتمد على `type`.

**الأثر في الـ UI:**
- Checkboxes/توجل ممكن تبقى بتعرض حالة غلط.
- تغييرات الإعدادات ممكن متبقاش consistent مع باقي النظام اللي بيقرأ عبر `SettingsService` / helper `setting()`.

**الإصلاح المقترح (ضروري):**
1) **إلغاء pluck نهائياً داخل UnifiedSettings** واستبداله بـ `SettingsService` أو `setting()` helper.
2) تعديل `setSetting()` في `UnifiedSettings` يستخدم `SettingsService->set()` (ويحط type/group صح).
3) توحيد مفاتيح الإعدادات Key Naming (شوف بند 1.2).

> النتيجة: كل toggle/select في الـ UI هيرجع “حقيقي” وبيغيّر سلوك النظام فعلاً.

---

### 1.2 تضارب في مفاتيح Settings بين (UnifiedSettings) و (config/settings.php) و (الكود الفعلي)
**ملف الـ Registry الحالي:**
- `config/settings.php` (منظم ومناسب جداً لتوليد UI تلقائي)

**المشكلة:**
- `config/settings.php` مبني على قاعدة: **المفتاح = group.setting**  
  مثال: `inventory.default_costing_method`
- بينما `UnifiedSettings` بيكتب مفاتيح مختلفة تماماً في أجزاء مهمة:
  - `company.name` بدل `general.company_name`
  - `inventory.costing_method` بدل `inventory.default_costing_method`
  - `notifications.low_stock` بدل الشكل الموجود في AdvancedSettings (`notifications.low_stock_enabled`…)
- ده بيخلق سيناريو “Option زينة”: المستخدم يختار حاجة في Settings UI لكنها مش متصلة بسلوك التطبيق اللي بيقرأ مفتاح مختلف.

**الإصلاح المقترح:**
- اعتماد **قاعدة واحدة** للمفاتيح: `group.setting` كما في `config/settings.php`.
- تعديل `UnifiedSettings` عشان يحفظ ويقرأ مفاتيح مطابقة للـ registry.
- إضافة Migration/Command صغير يعمل **key migration** من المفاتيح القديمة للجديدة (مرة واحدة) لو فيه بيانات سابقة.

**أمثلة Mapping أساسية لازم تتصلح:**
- `company.name` → `general.company_name`
- `company.email` → `general.company_email`
- `company.phone` → `general.company_phone`
- `app.date_format` → `branding.date_format` (أو تثبيت المفتاح في registry حسب قراركم)
- `inventory.costing_method` → `inventory.default_costing_method`
- `inventory.stock_alert_threshold` → `inventory.low_stock_threshold` (لو ده المقصود)
- `security.session_timeout` → `security.session_lifetime` (لو ده المقصود، أو العكس)

---

### 1.3 SystemSettings (Key/Value raw editor) لازم يتقفل أو يتحول SuperAdmin فقط
**الملف:**
- `app/Livewire/Admin/Settings/SystemSettings.php`

**المشكلة:**
- ده بيسمح لأي Admin يدخل Keys وقيم يدوي. ده “برمجة” جوه UI، وبيكسر شرطك:  
  > مفيش مدير/أدمن يدخل أكواد/تعقيد.

**الإصلاح المقترح:**
- يا إما:
  1) إغلاق الصفحة تماماً (إزالة route / إخفاء من القائمة)، أو
  2) قصرها على Permission خاص جداً: `settings.super_admin` مثلاً.
- واستبدالها بواجهة settings مبنية بالكامل على `config/settings.php` + controls واضحة.

---

## 2) تغييرات جذرية مطلوبة في Settings (علشان تتحكم من UI + ENV + DB)

### 2.1 خلي `config/settings.php` هو “مصدر الحقيقة”
بدلاً من وجود 2–3 صفحات Settings منفصلة (Unified/Advanced/System)، نعمل التالي:

**تصميم مقترح:**
- `SettingsRegistry` (Class بسيطة) تقرأ `config/settings.php` وتطلع:
  - Tabs/Groups
  - type/options/default/min/max/validation
  - هل setting مربوط بـ ENV؟
- `UnifiedSettings` يتحول لصفحة “Dynamic”:
  - بتبني الفورم تلقائياً بناءً على registry
  - وتخزن بالقواعد (type/options) بدون كتابة يدوي لمفاتيح scattered.

**مكسب ضخم:**
- أي Setting جديد تضيفه في `config/settings.php` يظهر في UI تلقائياً.
- مفيش Settings “زينة” لأن UI نفسها مبنية على نفس المفاتيح اللي الكود يستخدمها.

---

### 2.2 دعم ENV Override من غير ما نطلب من الأدمن يكتب ENV
المطلوب منك: “أقدر أتحكم في أي إدخالات وقيم من خلال settings في الـ ENV أو الـ DB”.

**أفضل ممارسة:**
- بعض الإعدادات لازم تتقفل من ENV (زي مفاتيح API / SMTP / SMS …)  
- وبعضها يتظبط من DB (UI toggles / قواعد business)

**اقتراح عملي:**
- في `config/settings.php` لكل setting ممكن تضيف:
  - `env_key` => 'APP_TIMEZONE' مثلاً
  - `env_overrides` => true/false
- داخل UI:
  - لو env موجود: اعرض القيمة “Locked by ENV” (Read-only) + اسم ENV key
  - لو env مش موجود: خلي الإدخال editable ويُحفظ في DB

> كده الأدمن مش بيكتب ENV، بس بيشوف مين اللي متحكم (ENV ولا DB) بدون خلط.

---

### 2.3 تحسين SettingsService (أداء/Consistency)
**الملف:**
- `app/Services/SettingsService.php`

**ملاحظات:**
- `set()` و `setMany()` بينادوا `Artisan::call('config:clear')` بعد كل تعديل (أو بعد batch).
- ده ممكن يبقى مكلف جداً في Production ومش لازم لو التطبيق بيقرأ الإعدادات من `SettingsService` مباشرة.

**اقتراح:**
- إلغاء `config:clear` (أو خليها Feature Flag setting مثل `advanced.clear_config_cache_on_settings_update`).
- اعتمد على cache الداخلية (`system_settings`) مع clear لها فقط.

---

## 3) Templates + Scheduled Reports: مشاكل UI + Backend (زينة حالياً) والحل

### 3.1 Report Templates حالياً فيها “حقول برمجية” (JSON / columns text / custom key)
**الملفات:**
- `app/Livewire/Admin/Reports/ReportTemplatesManager.php`
- `resources/views/livewire/admin/reports/templates-manager.blade.php`
- `database/migrations/2026_01_07_000001_create_reporting_tables.php` (report_templates)

**مشاكل UI:**
- `Default Filters (JSON)` textarea = إدخال برمجي مباشر.
- `Export Columns` نص comma-separated = إدخال متقدم غير مناسب للإدمن.
- `Custom key` = إدخال تقني.

**مشاكل Backend مرتبطة:**
- `output_type` في DB default = `html`  
  بينما الـ UI بيستخدم `web`/`excel`/`pdf` → mismatch.
- Templates لا تبدو موصولة فعلياً بآلية توليد التقرير (شوف 3.2).

**الحل المطلوب (UI-first):**
1) **اختيار التقرير**: بدل `route_name` فقط، اربط Template بـ `ReportDefinition`.
2) **Filters**: توليد فورم ديناميكي من `ReportDefinition.available_filters`
   - Date range = date inputs
   - branch/store = select
   - status = select/radio
   - categories = multi-select
3) **Columns**: Checkbox group / multi-select من `ReportDefinition.available_columns`
4) `output_type` يتوحد إلى: `html | xlsx | pdf` (أو `web|xlsx|pdf` لكن لازم DB+Services يوافقوا)
5) اخفاء `custom key` افتراضياً، ولو محتاجينه يبقى SuperAdmin فقط.

---

### 3.2 Templates/Scheduling “مش متوصّل” فعلياً بالتشغيل (Options زينة)
**الملفات:**
- `app/Services/ScheduledReportService.php`
- `app/Console/Commands/RunScheduledReports.php`
- `database/migrations/2026_01_07_000001_create_reporting_tables.php` (scheduled_reports)
- `database/migrations/2026_01_09_000001_create_missing_tables.php` (report_schedules)

**المشكلة:**
- `ScheduledReportService` بيستخدم `$template->config` و `$template->type`…  
  لكن جدول `report_templates` **مفيهوش أعمدة config/type** → دايماً null.
- Command `RunScheduledReports` بيقرأ من جدول `report_schedules`  
  بينما Livewire UI بتكتب في `scheduled_reports` (Model `ScheduledReport`) → جدول مختلف → جدولة مش شغالة.

**الإصلاح المقترح (لازم قرار واحد):**
- اختاروا نظام واحد فقط:
  1) **اعتماد `report_schedules`** (لأنه مستخدم في الـ Command)  
     - عدّل Livewire `ScheduledReportsManager` يقرأ/يكتب في `report_schedules`
     - خلي الـ UI تعتمد Frequency/Day/Time (زي اللي في الواجهة بالفعل) بدون Cron وبدون JSON
  أو
  2) اعتماد `scheduled_reports` وتعديل الـ Command يقرأ منها  
     - لكن ده أصعب لأن عندك بالفعل Migration لـ report_schedules.

> أنا برشح (1) عشان أقل تغييرات وتوافق مع الموجود.

---

## 4) تعديلات مباشرة مطلوبة في Livewire Forms (للتبسيط)

### 4.1 قاعدة عامة: أي Textarea “تقني” يتحول UI Controls
**Targets في النسخة دي:**
- `Default Filters (JSON)` في Templates
- `Custom Filters (JSON)` في Scheduled Reports
- `Cron Expression` (خليه read-only أو hidden)

**Implementation Pattern:**
- داخل Livewire component:
  - خزّن filters كـ array: `$this->filters = []`
  - اعرض UI fields حسب schema
  - اعمل validation لكل field
  - في الحفظ: خزّن `$filters` كـ JSON في DB

---

### 4.2 توحيد Format Enums
- Templates: `output_type` لازم يطابق DB + export layer.
- Scheduler: لا تعتمد “web” لو DB بيحط “html”.

---

## 5) “باكدج/كود” مقترح لتقليل التكرار

### 5.1 Components جاهزة لإعادة الاستخدام
اعمل Blade components بسيطة (أو Livewire subcomponents):
- `x-form.toggle` (checkbox styled)
- `x-form.select` (select + label + help + error)
- `x-form.checkbox-group` (multi-select columns)
- `x-form.radio-group`

وده يخلي كل Settings/Templates/Scheduler consistent ومريح.

---

## 6) Checklist تحقق قبل اعتماد التغييرات (علشان مفيش Option زينة)

### Settings
- [ ] كل Setting موجود في UI ليه Key واحد canonical (group.setting)  
- [ ] كل Key مستخدم في الكود via `setting()` أو `SettingsService->get()`  
- [ ] مفيش قراءة مباشرة بـ `pluck('value','key')` لأي setting boolean  
- [ ] لو setting مربوط بـ ENV → UI يظهره read-only مع اسم ENV key

### Templates
- [ ] اختيار ReportDefinition mandatory
- [ ] Columns = Multi-select من schema (مش text)
- [ ] Filters = UI fields (مش JSON)
- [ ] output_type enum موحد ومختبر

### Scheduler
- [ ] UI يكتب في نفس الجدول اللي الـ Command بيقرأ منه
- [ ] مفيش Cron إدخال يدوي (إلا لو SuperAdmin)
- [ ] filters مش JSON input للمستخدم النهائي

---

## 7) تغييرات مقترحة “بالملفات” (خريطة تنفيذ)

### A) Settings
1) Refactor:
- `app/Livewire/Admin/Settings/UnifiedSettings.php`  
  - استبدال getSetting/setSetting بـ SettingsService
  - توحيد المفاتيح مع `config/settings.php`

2) Hardening:
- `app/Livewire/Admin/Settings/SystemSettings.php`  
  - قفل/تقليل صلاحيات

3) (اختياري) Migration لتحويل المفاتيح القديمة:
- Migration أو Artisan Command: ينقل قيم `company.name` → `general.company_name` … إلخ

### B) Templates
1) UI + Logic:
- `app/Livewire/Admin/Reports/ReportTemplatesManager.php`
- `resources/views/livewire/admin/reports/templates-manager.blade.php`

2) ربط بـ ReportDefinition:
- إضافة `report_definition_id` في `report_templates` (Migration جديدة)
- تحديث الـ model `ReportTemplate` بعلاقة `reportDefinition()`

### C) Scheduler
1) توحيد الجدول:
- عدّل `ScheduledReportsManager` يشتغل على `report_schedules`
- أو عدّل Command يقرأ من `scheduled_reports` (اختاروا واحد)

---

## ملاحظة مهمة (لتنفيذ سريع)
لو عايز أسرع طريق بأقل refactor:
- **Step 1**: إصلاح UnifiedSettings (قراءة/كتابة settings) + key alignment  
- **Step 2**: إصلاح Template/Schedule wiring (جدول واحد + output_type enums)  
- **Step 3**: إزالة JSON inputs واستبدالها بـ schema-driven UI

ده هيحل 80% من “تعقيد الأدمن” ويخلي كل حاجة فعّالة مش زينة.



---

## 8) Patches سريعة (أمثلة عملية جاهزة للتطبيق)

> دي أمثلة “core” لإصلاح أهم نقط. مش كل المشروع، لكن بتقفل أكبر ثغرات UI/Backend بسرعة.

### 8.1 Patch: اجعل UnifiedSettings يستخدم SettingsService (بدون pluck)
**ملف:** `app/Livewire/Admin/Settings/UnifiedSettings.php`

**فكرة الباتش:**
- احذف `getSetting()` اللي بيعمل pluck
- استبدل `setSetting()` بـ `$this->settings->set(...)`
- في `mount()`/constructor inject SettingsService

```php
use App\Services\SettingsService;

class UnifiedSettings extends Component
{
    public function __construct()
    {
        $this->settings = app(SettingsService::class);
    }

    protected SettingsService $settings;

    protected function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings->get($key, $default);
    }

    protected function setSetting(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $this->settings->set($key, $value, [
            'group' => $group,
            'type' => $type,
            'is_public' => false,
        ]);
    }
}
```

**مهم جداً:**
- بعد الباتش ده، لازم تغيّر مفاتيح `setSetting()` في `saveGeneral()` وغيرها لتبقى `group.setting` (كما في `config/settings.php`).

---

### 8.2 Patch: Fix Key Names في saveGeneral (مثال)
بدّل:

```php
$this->setSetting('company.name', $this->company_name, 'general');
```

إلى:

```php
$this->setSetting('general.company_name', $this->company_name, 'general', 'string');
```

وبالمثل:
- `general.company_email`
- `general.company_phone`
- `branding.date_format` … إلخ

---

### 8.3 Migration (مرة واحدة) لترحيل المفاتيح القديمة للجديدة
**فكرة سريعة (Pseudo):**
- لو لقيت `company.name` موجود → انسخ قيمته لـ `general.company_name` ثم احذف القديم.

```php
DB::transaction(function () {
    $map = [
        'company.name'  => 'general.company_name',
        'company.email' => 'general.company_email',
        'company.phone' => 'general.company_phone',
        // ...
        'inventory.costing_method' => 'inventory.default_costing_method',
    ];

    foreach ($map as $old => $new) {
        $row = \App\Models\SystemSetting::where('key', $old)->first();
        if ($row && ! \App\Models\SystemSetting::where('key', $new)->exists()) {
            \App\Models\SystemSetting::create([
                'key' => $new,
                'value' => $row->value,
                'type' => $row->type,
                'group' => $row->group,
                'is_public' => $row->is_public,
            ]);
        }
        if ($row) $row->delete();
    }
});
```

---

### 8.4 Patch: توحيد output_type في Templates
**ملفات:**
- `app/Livewire/Admin/Reports/ReportTemplatesManager.php`
- `resources/views/livewire/admin/reports/templates-manager.blade.php`

بدّل القيم في select:
- `web` → `html`
- `excel` → `xlsx`
- `pdf` → `pdf`

وبعدها عدّل validation rule و أي كود بيقارن output_type.

---

### 8.5 Patch: ربط Templates بـ ReportDefinition (بدل route_name)
**تعديل DB:**
- Add column: `report_definition_id` to `report_templates`

**Model:**
```php
public function reportDefinition(): BelongsTo
{
    return $this->belongsTo(ReportDefinition::class);
}
```

**UI:**
- بدل dropdown “route_name” → dropdown تقرير من `ReportDefinition::active()->ordered()->get()`

---

### 8.6 Patch: Scheduler يكتب في نفس الجدول اللي الـ Command بيقرأ منه
**حالة حالية:**
- UI: `ScheduledReportsManager` (جدول scheduled_reports)
- CLI: `RunScheduledReports` (جدول report_schedules)

**حل سريع:**
- غيّر Livewire `ScheduledReportsManager` يقرأ/يكتب في `report_schedules` (وتلغي cronExpression من الإدخال، وتخليه computed).

---

## 9) ناتج متوقع بعد تطبيق الباتشات
- Settings toggles هتكون دقيقة 100% (مش bool casting غلط).
- مفيش Admin يكتب JSON أو Cron.
- Templates + Scheduling هيتحولوا من “زينة” لـ Feature شغال فعلياً (من نفس الـ DB schema وبنفس enums).
- أي إعداد تضيفه في `config/settings.php` تقدر تطلعه UI بسهولة.

