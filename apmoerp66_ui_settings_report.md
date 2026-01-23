# تقرير فحص النصوص غير المترجمة في الـ UI (Blade/Livewire Views)

> النسخة: **apmoerp68** (Laravel 12 / Livewire 4.0.1)

> نطاق الفحص: جميع ملفات `*.blade.php` تحت `resources/views`.

> معيار "غير مترجم": أي نص/Label/Placeholder/Value **بالإنجليزية** ظاهر في الـ UI ولم يكن داخل `__()` أو `@lang()`.


---

## ملخص

- عدد ملفات Blade المفحوصة: **299**
- عدد المواضع التي تحتوي نصوص إنجليزية غير مترجمة (مرشّحة): **670**

### توزيع حسب النوع

| Kind | Count |
|---|---:|
| attr:value | 471 |
| attr:label | 86 |
| text | 79 |
| attr:placeholder | 30 |
| attr:aria-label | 3 |
| attr:alt | 1 |

### أعلى الملفات من حيث عدد النصوص غير المترجمة

| File | Count |
|---|---:|
| `resources/views/components/sidebar/module.blade.php` | 54 |
| `resources/views/livewire/admin/settings/advanced-settings.blade.php` | 49 |
| `resources/views/livewire/admin/settings/unified-settings.blade.php` | 42 |
| `resources/views/components/sidebar/main.blade.php` | 33 |
| `resources/views/livewire/admin/users/form.blade.php` | 32 |
| `resources/views/livewire/inventory/product-compatibility.blade.php` | 22 |
| `resources/views/livewire/admin/modules/form.blade.php` | 13 |
| `resources/views/livewire/admin/setup-wizard.blade.php` | 11 |
| `resources/views/livewire/manufacturing/production-orders/form.blade.php` | 10 |
| `resources/views/livewire/admin/reports/index.blade.php` | 10 |
| `resources/views/livewire/manufacturing/production-orders/index.blade.php` | 10 |
| `resources/views/livewire/manufacturing/work-centers/form.blade.php` | 8 |
| `resources/views/livewire/admin/installments/index.blade.php` | 8 |
| `resources/views/livewire/sales/form.blade.php` | 8 |
| `resources/views/livewire/expenses/form.blade.php` | 8 |
| `resources/views/livewire/fixed-assets/form.blade.php` | 8 |
| `resources/views/livewire/accounting/index.blade.php` | 7 |
| `resources/views/livewire/components/media-picker.blade.php` | 7 |
| `resources/views/livewire/customers/form.blade.php` | 7 |
| `resources/views/livewire/projects/gantt-chart.blade.php` | 7 |

---

## التفاصيل (مسار + سطر + النص)

> ملاحظة: بعض `attr:value` قد تكون قيم داخلية (مثل web/excel/pdf) لكنها تظهر في الـ UI أحيانًا (راديو/سلكت)، لذلك تم إدراجها للمراجعة.


### `resources/views/admin/reports/inventory-export-web.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 17 | attr:value | web |
| 21 | attr:value | excel |
| 25 | attr:value | pdf |

### `resources/views/admin/reports/pos-export-web.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 17 | attr:value | web |
| 21 | attr:value | excel |
| 25 | attr:value | pdf |

### `resources/views/components/erp/breadcrumb.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 1 | attr:aria-label | Breadcrumb |

### `resources/views/components/export-modal.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 95 | attr:value | Y-m-d |
| 96 | attr:value | d/m/Y |
| 97 | attr:value | m/d/Y |
| 127 | attr:value | all |

### `resources/views/components/modal.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 7 | text | Modal Title |

### `resources/views/components/sidebar/enhanced.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 155 | text | Ctrl+K |
| 156 | text | Ctrl+B |

### `resources/views/components/sidebar/main.blade.php` — (33)

| Line | Kind | Text |
|---:|---|---|
| 8 | text | HugouERP |
| 13 | attr:label | Dashboard |
| 17 | attr:label | POS Terminal |
| 27 | attr:label | Sales |
| 32 | attr:label | Purchases |
| 37 | attr:label | Inventory |
| 42 | attr:label | Warehouse |
| 47 | attr:label | Accounting |
| 52 | attr:label | Expenses |
| 57 | attr:label | Income |
| 67 | attr:label | Customers |
| 72 | attr:label | Suppliers |
| 82 | attr:label | Human Resources |
| 87 | attr:label | Rental |
| 92 | attr:label | Manufacturing |
| 97 | attr:label | Banking |
| 102 | attr:label | Fixed Assets |
| 107 | attr:label | Projects |
| 112 | attr:label | Documents |
| 117 | attr:label | Helpdesk |
| 132 | attr:label | My Attendance |
| 136 | attr:label | My Leaves |
| 140 | attr:label | My Payslips |
| 151 | attr:label | Settings |
| 156 | attr:label | Branch Settings |
| 161 | attr:label | Reports |
| 167 | attr:label | Branch Reports |
| 173 | attr:label | Users |
| 179 | attr:label | Branch Employees |
| 185 | attr:label | Roles |
| 190 | attr:label | Branches |
| 195 | attr:label | Modules |
| 200 | attr:label | Audit Logs |

### `resources/views/components/sidebar/module.blade.php` — (54)

| Line | Kind | Text |
|---:|---|---|
| 23 | attr:label | All Sales |
| 25 | attr:label | New Sale |
| 28 | attr:label | Returns |
| 31 | attr:label | Analytics |
| 35 | attr:label | All Purchases |
| 37 | attr:label | New Purchase |
| 40 | attr:label | Returns |
| 43 | attr:label | Requisitions |
| 45 | attr:label | Quotations |
| 46 | attr:label | Goods Received |
| 49 | attr:label | Products |
| 51 | attr:label | Categories |
| 54 | attr:label | Units |
| 56 | attr:label | Stock Alerts |
| 57 | attr:label | Batches |
| 58 | attr:label | Serial Numbers |
| 59 | attr:label | Barcodes |
| 62 | attr:label | Dashboard |
| 63 | attr:label | Locations |
| 64 | attr:label | Movements |
| 65 | attr:label | Transfers |
| 66 | attr:label | Adjustments |
| 69 | attr:label | Units |
| 70 | attr:label | Properties |
| 71 | attr:label | Tenants |
| 72 | attr:label | Contracts |
| 73 | attr:label | Reports |
| 76 | attr:label | Bills of Materials |
| 77 | attr:label | Production Orders |
| 78 | attr:label | Work Centers |
| 81 | attr:label | Employees |
| 82 | attr:label | Attendance |
| 83 | attr:label | Payroll |
| 84 | attr:label | Shifts |
| 85 | attr:label | Reports |
| 88 | attr:label | Accounts |
| 89 | attr:label | Transactions |
| 90 | attr:label | Reconciliation |
| 93 | attr:label | All Assets |
| 95 | attr:label | Add Asset |
| 97 | attr:label | Depreciation |
| 100 | attr:label | All Projects |
| 102 | attr:label | New Project |
| 106 | attr:label | All Documents |
| 108 | attr:label | Upload Document |
| 112 | attr:label | Tickets |
| 114 | attr:label | New Ticket |
| 115 | attr:label | Categories |
| 119 | attr:label | All Expenses |
| 121 | attr:label | New Expense |
| 122 | attr:label | Categories |
| 126 | attr:label | All Income |
| 128 | attr:label | New Income |
| 129 | attr:label | Categories |

### `resources/views/components/ui/keyboard-shortcuts.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 137 | text | Ctrl |

### `resources/views/components/ui/page-header.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 27 | attr:aria-label | Breadcrumb |

### `resources/views/layouts/app.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 17 | text | @yield('title', config('app.name', 'Ghanem ERP')) |

### `resources/views/layouts/guest.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 12 | text | @yield('title', config('app.name', 'Ghanem ERP')) |

### `resources/views/livewire/accounting/accounts/form.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 31 | attr:value | asset |
| 32 | attr:value | liability |
| 33 | attr:value | equity |
| 34 | attr:value | revenue |
| 35 | attr:value | expense |

### `resources/views/livewire/accounting/index.blade.php` — (7)

| Line | Kind | Text |
|---:|---|---|
| 92 | attr:aria-label | Tabs |
| 136 | attr:value | asset |
| 137 | attr:value | liability |
| 138 | attr:value | equity |
| 139 | attr:value | revenue |
| 140 | attr:value | expense |
| 184 | text | balance |

### `resources/views/livewire/accounting/journal-entries/form.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 41 | attr:value | draft |
| 42 | attr:value | posted |

### `resources/views/livewire/admin/api-documentation.blade.php` — (6)

| Line | Kind | Text |
|---:|---|---|
| 42 | text | Shopify |
| 43 | text | WooCommerce |
| 44 | text | Laravel API |
| 45 | text | Custom API |
| 51 | text | X-RateLimit-Limit |
| 52 | text | X-RateLimit-Remaining |

### `resources/views/livewire/admin/branch/employees.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 44 | attr:value | active |
| 45 | attr:value | inactive |

### `resources/views/livewire/admin/branch/reports.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 15 | attr:value | day |
| 16 | attr:value | week |
| 17 | attr:value | month |
| 18 | attr:value | year |

### `resources/views/livewire/admin/branches/index.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 103 | attr:value | active |
| 104 | attr:value | inactive |

### `resources/views/livewire/admin/bulk-import.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 157 | attr:placeholder | https://docs.google.com/spreadsheets/d/... |

### `resources/views/livewire/admin/dashboard.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 233 | text | whereDate('end_date',' |

### `resources/views/livewire/admin/export/customize-export.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 69 | attr:value | xlsx |
| 70 | attr:value | csv |
| 71 | attr:value | pdf |

### `resources/views/livewire/admin/installments/index.blade.php` — (8)

| Line | Kind | Text |
|---:|---|---|
| 52 | attr:value | active |
| 53 | attr:value | completed |
| 54 | attr:value | defaulted |
| 55 | attr:value | all |
| 151 | attr:value | cash |
| 152 | attr:value | card |
| 153 | attr:value | transfer |
| 154 | attr:value | cheque |

### `resources/views/livewire/admin/login-activity/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 34 | attr:value | login |
| 35 | attr:value | logout |
| 36 | attr:value | failed |
| 37 | attr:value | lockout |

### `resources/views/livewire/admin/loyalty/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 35 | attr:value | new |
| 36 | attr:value | regular |
| 37 | attr:value | vip |
| 38 | attr:value | premium |

### `resources/views/livewire/admin/media-library.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 77 | attr:value | all |
| 78 | attr:value | mine |

### `resources/views/livewire/admin/modules/fields/form.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 100 | attr:placeholder | e.g. max:255\|min:3 |

### `resources/views/livewire/admin/modules/form.blade.php` — (13)

| Line | Kind | Text |
|---:|---|---|
| 271 | attr:placeholder | e.g., hrm, inventory |
| 321 | attr:value | text |
| 322 | attr:value | textarea |
| 323 | attr:value | number |
| 324 | attr:value | email |
| 325 | attr:value | phone |
| 326 | attr:value | date |
| 327 | attr:value | datetime |
| 328 | attr:value | select |
| 329 | attr:value | checkbox |
| 330 | attr:value | file |
| 331 | attr:value | image |
| 347 | attr:placeholder | e.g., department_id |

### `resources/views/livewire/admin/modules/module-manager.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 68 | attr:value | active |
| 69 | attr:value | inactive |
| 230 | attr:placeholder | e.g., my_module |

### `resources/views/livewire/admin/modules/rental-periods/form.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 27 | attr:placeholder | e.g. monthly_1 |

### `resources/views/livewire/admin/reports/index.blade.php` — (10)

| Line | Kind | Text |
|---:|---|---|
| 56 | attr:value | sales |
| 57 | attr:value | purchases |
| 58 | attr:value | expenses |
| 59 | attr:value | income |
| 60 | attr:value | customers |
| 61 | attr:value | suppliers |
| 62 | attr:value | inventory |
| 172 | attr:value | xlsx |
| 173 | attr:value | csv |
| 174 | attr:value | pdf |

### `resources/views/livewire/admin/reports/scheduled-manager.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 118 | attr:value | daily |
| 119 | attr:value | weekly |
| 120 | attr:value | monthly |
| 121 | attr:value | quarterly |
| 237 | attr:placeholder | {"branch_id": 1} |

### `resources/views/livewire/admin/reports/templates-manager.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 157 | attr:value | html |
| 158 | attr:value | xlsx |
| 159 | attr:value | pdf |
| 223 | attr:placeholder | {"from": "2025-01-01", "to": "2025-01-31"} |
| 238 | attr:placeholder | id, name, status, total |

### `resources/views/livewire/admin/settings/advanced-settings.blade.php` — (49)

| Line | Kind | Text |
|---:|---|---|
| 120 | attr:value | EGP |
| 121 | attr:value | USD |
| 122 | attr:value | EUR |
| 123 | attr:value | SAR |
| 129 | attr:value | ar |
| 130 | attr:value | en |
| 136 | attr:value | Africa/Cairo |
| 136 | text | Africa/Cairo (Egypt) |
| 137 | attr:value | Asia/Riyadh |
| 137 | text | Asia/Riyadh (Saudi Arabia) |
| 138 | attr:value | Asia/Dubai |
| 138 | text | Asia/Dubai (UAE) |
| 139 | attr:value | Europe/London |
| 139 | text | Europe/London (UK) |
| 168 | text | 3shm (WhatsApp) |
| 212 | text | SMSMISR |
| 305 | text | google.com/recaptcha/admin |
| 429 | text | console.firebase.google.com |
| 432 | text | Web ( |
| 506 | attr:value | daily |
| 507 | attr:value | weekly |
| 508 | attr:value | monthly |
| 629 | attr:value | auto |
| 630 | attr:value | expanded |
| 631 | attr:value | collapsed |
| 657 | attr:value | top-right |
| 658 | attr:value | top-left |
| 659 | attr:value | bottom-right |
| 660 | attr:value | bottom-left |
| 661 | attr:value | top-center |
| 662 | attr:value | bottom-center |
| 711 | attr:value | xlsx |
| 711 | text | Excel (.xlsx) |
| 712 | attr:value | csv |
| 712 | text | CSV (.csv) |
| 713 | attr:value | pdf |
| 713 | text | PDF (.pdf) |
| 714 | attr:value | json |
| 714 | text | JSON (.json) |
| 747 | attr:value | portrait |
| 748 | attr:value | landscape |
| 754 | attr:value | a4 |
| 754 | text | A4 |
| 755 | attr:value | letter |
| 755 | text | Letter |
| 756 | attr:value | legal |
| 756 | text | Legal |
| 757 | attr:value | a3 |
| 757 | text | A3 |

### `resources/views/livewire/admin/settings/system-settings.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 95 | attr:placeholder | app.locale |
| 104 | attr:placeholder | value |
| 110 | attr:placeholder | general |

### `resources/views/livewire/admin/settings/unified-settings.blade.php` — (42)

| Line | Kind | Text |
|---:|---|---|
| 137 | attr:value | UTC |
| 137 | text | UTC |
| 138 | attr:value | Africa/Cairo |
| 138 | text | Africa/Cairo |
| 139 | attr:value | Asia/Dubai |
| 139 | text | Asia/Dubai |
| 140 | attr:value | Asia/Riyadh |
| 140 | text | Asia/Riyadh |
| 141 | attr:value | Europe/London |
| 141 | text | Europe/London |
| 142 | attr:value | America/New_York |
| 142 | text | America/New_York |
| 152 | attr:value | Y-m-d |
| 152 | text | YYYY-MM-DD |
| 153 | attr:value | d/m/Y |
| 153 | text | DD/MM/YYYY |
| 154 | attr:value | m/d/Y |
| 154 | text | MM/DD/YYYY |
| 201 | attr:placeholder | #10b981 |
| 212 | attr:placeholder | #3b82f6 |
| 222 | attr:value | $branding_logo_id |
| 234 | attr:value | $branding_favicon_id |
| 247 | attr:alt | Logo |
| 435 | attr:value | daily |
| 436 | attr:value | weekly |
| 437 | attr:value | monthly |
| 457 | attr:value | local |
| 458 | attr:value | s3 |
| 459 | attr:value | ftp |
| 482 | attr:value | FIFO |
| 483 | attr:value | LIFO |
| 484 | attr:value | AVG |
| 549 | attr:value | none |
| 577 | attr:value | standard |
| 578 | attr:value | retail |
| 579 | attr:value | service |
| 697 | attr:value | percentage |
| 698 | attr:value | fixed |
| 717 | attr:value | percentage |
| 718 | attr:value | fixed |
| 776 | attr:value | percentage |
| 777 | attr:value | fixed |

### `resources/views/livewire/admin/settings/user-preferences.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 20 | text | F1 |
| 47 | attr:value | light |
| 56 | attr:value | dark |
| 65 | attr:value | system |
| 245 | text | Ctrl+S |

### `resources/views/livewire/admin/settings/warehouse-settings.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 60 | attr:value | FIFO |
| 61 | attr:value | LIFO |
| 62 | attr:value | FEFO |

### `resources/views/livewire/admin/setup-wizard.blade.php` — (11)

| Line | Kind | Text |
|---:|---|---|
| 72 | attr:placeholder | ABC Company |
| 83 | attr:placeholder | info@company.com |
| 93 | attr:placeholder | 123 Main St, Cairo, Egypt |
| 115 | attr:value | ar |
| 116 | attr:value | en |
| 116 | text | English |
| 139 | attr:placeholder | John Doe |
| 144 | attr:placeholder | admin@company.com |
| 178 | attr:placeholder | Main Branch |
| 183 | attr:placeholder | HQ001 |
| 193 | attr:placeholder | 123 Main St, Cairo, Egypt |

### `resources/views/livewire/admin/stock/low-stock-alerts.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 34 | attr:value | active |
| 35 | attr:value | acknowledged |
| 36 | attr:value | resolved |
| 37 | attr:value | all |
| 69 | text | current_qty |

### `resources/views/livewire/admin/store/form.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 53 | attr:placeholder | https://your-store.myshopify.com |
| 95 | text | Shopify: |
| 96 | text | WooCommerce: |
| 97 | text | Salla: |

### `resources/views/livewire/admin/store/orders-dashboard.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 221 | attr:value | excel |
| 222 | attr:value | pdf |
| 223 | attr:value | web |

### `resources/views/livewire/admin/store/stores.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 42 | attr:value | active |
| 43 | attr:value | inactive |

### `resources/views/livewire/admin/translation-manager.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 175 | attr:placeholder | app, auth, validation, etc. |
| 183 | attr:placeholder | e.g., messages.welcome |

### `resources/views/livewire/admin/translations/form.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 106 | text | EN: |
| 110 | text | AR: |

### `resources/views/livewire/admin/users/form.blade.php` — (32)

| Line | Kind | Text |
|---:|---|---|
| 138 | attr:value | ar |
| 139 | attr:value | en |
| 151 | attr:value | Africa/Cairo |
| 151 | text | Africa/Cairo (EET, UTC+2) |
| 152 | attr:value | Asia/Riyadh |
| 152 | text | Asia/Riyadh (AST, UTC+3) |
| 153 | attr:value | Asia/Dubai |
| 153 | text | Asia/Dubai (GST, UTC+4) |
| 154 | attr:value | Asia/Kuwait |
| 154 | text | Asia/Kuwait (AST, UTC+3) |
| 155 | attr:value | Asia/Bahrain |
| 155 | text | Asia/Bahrain (AST, UTC+3) |
| 156 | attr:value | Asia/Qatar |
| 156 | text | Asia/Qatar (AST, UTC+3) |
| 157 | attr:value | Asia/Amman |
| 157 | text | Asia/Amman (EET, UTC+2) |
| 158 | attr:value | Asia/Beirut |
| 158 | text | Asia/Beirut (EET, UTC+2) |
| 159 | attr:value | Asia/Damascus |
| 159 | text | Asia/Damascus (EET, UTC+2) |
| 160 | attr:value | Asia/Jerusalem |
| 160 | text | Asia/Jerusalem (IST, UTC+2) |
| 161 | attr:value | Europe/London |
| 161 | text | Europe/London (GMT, UTC+0) |
| 162 | attr:value | Europe/Paris |
| 162 | text | Europe/Paris (CET, UTC+1) |
| 163 | attr:value | America/New_York |
| 163 | text | America/New_York (EST, UTC-5) |
| 164 | attr:value | America/Los_Angeles |
| 164 | text | America/Los_Angeles (PST, UTC-8) |
| 165 | attr:value | UTC |
| 165 | text | UTC (UTC+0) |

### `resources/views/livewire/auth/two-factor-challenge.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 25 | attr:placeholder | XXXXX-XXXXX |

### `resources/views/livewire/banking/accounts/form.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 73 | attr:value | checking |
| 74 | attr:value | savings |
| 75 | attr:value | credit |

### `resources/views/livewire/banking/accounts/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 82 | attr:value | active |
| 83 | attr:value | inactive |
| 84 | attr:value | closed |

### `resources/views/livewire/banking/reconciliation.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 34 | text | $currentStep |
| 36 | text | $currentStep |

### `resources/views/livewire/command-palette.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 104 | text | ESC |

### `resources/views/livewire/components/media-picker.blade.php` — (7)

| Line | Kind | Text |
|---:|---|---|
| 246 | attr:value | all |
| 247 | attr:value | images |
| 248 | attr:value | documents |
| 271 | attr:value | newest |
| 272 | attr:value | oldest |
| 273 | attr:value | name_asc |
| 274 | attr:value | name_desc |

### `resources/views/livewire/components/notes-attachments.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 117 | text | &bull; |
| 158 | attr:value | general |
| 159 | attr:value | important |
| 160 | attr:value | followup |
| 161 | attr:value | internal |

### `resources/views/livewire/customers/form.blade.php` — (7)

| Line | Kind | Text |
|---:|---|---|
| 61 | attr:value | immediate |
| 62 | attr:value | net15 |
| 63 | attr:value | net30 |
| 64 | attr:value | net60 |
| 65 | attr:value | net90 |
| 82 | attr:value | active |
| 83 | attr:value | inactive |

### `resources/views/livewire/customers/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 48 | attr:value | individual |
| 49 | attr:value | company |
| 88 | text | balance |

### `resources/views/livewire/documents/form.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 89 | attr:placeholder | e.g., Contracts, Invoices |
| 95 | attr:placeholder | e.g., Legal, Financial |

### `resources/views/livewire/documents/show.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 144 | attr:value | view |
| 145 | attr:value | download |
| 146 | attr:value | edit |
| 147 | attr:value | manage |

### `resources/views/livewire/documents/tags/form.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 33 | attr:placeholder | #3B82F6 |

### `resources/views/livewire/expenses/form.blade.php` — (8)

| Line | Kind | Text |
|---:|---|---|
| 42 | attr:value | cash |
| 43 | attr:value | bank_transfer |
| 44 | attr:value | card |
| 45 | attr:value | cheque |
| 85 | attr:value | daily |
| 86 | attr:value | weekly |
| 87 | attr:value | monthly |
| 88 | attr:value | yearly |

### `resources/views/livewire/fixed-assets/form.blade.php` — (8)

| Line | Kind | Text |
|---:|---|---|
| 41 | attr:value | Computer Equipment |
| 42 | attr:value | Office Furniture |
| 43 | attr:value | Machinery |
| 44 | attr:value | Vehicles |
| 45 | attr:value | Buildings |
| 149 | attr:value | straight_line |
| 150 | attr:value | declining_balance |
| 151 | attr:value | units_of_production |

### `resources/views/livewire/fixed-assets/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 89 | attr:value | active |
| 90 | attr:value | disposed |
| 91 | attr:value | sold |
| 92 | attr:value | retired |

### `resources/views/livewire/helpdesk/index.blade.php` — (7)

| Line | Kind | Text |
|---:|---|---|
| 88 | attr:value | new |
| 89 | attr:value | open |
| 90 | attr:value | pending |
| 91 | attr:value | resolved |
| 92 | attr:value | closed |
| 117 | attr:value | me |
| 118 | attr:value | unassigned |

### `resources/views/livewire/helpdesk/ticket-form.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 86 | attr:value | new |
| 87 | attr:value | open |
| 88 | attr:value | pending |
| 89 | attr:value | resolved |
| 90 | attr:value | closed |

### `resources/views/livewire/helpdesk/tickets/form.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 82 | attr:value | new |
| 83 | attr:value | open |
| 84 | attr:value | pending |
| 85 | attr:value | resolved |
| 86 | attr:value | closed |

### `resources/views/livewire/helpdesk/tickets/index.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 36 | attr:value | new |
| 37 | attr:value | open |
| 38 | attr:value | pending |
| 39 | attr:value | resolved |
| 40 | attr:value | closed |

### `resources/views/livewire/hrm/attendance/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 24 | attr:value | present |
| 25 | attr:value | absent |
| 26 | attr:value | leave |

### `resources/views/livewire/hrm/employees/index.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 103 | attr:value | active |
| 104 | attr:value | inactive |

### `resources/views/livewire/hrm/payroll/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 30 | attr:value | draft |
| 31 | attr:value | approved |
| 32 | attr:value | paid |

### `resources/views/livewire/hrm/self-service/my-attendance.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 48 | attr:value | present |
| 49 | attr:value | absent |
| 50 | attr:value | late |

### `resources/views/livewire/hrm/self-service/my-leaves.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 64 | attr:value | pending |
| 65 | attr:value | approved |
| 66 | attr:value | rejected |
| 67 | attr:value | cancelled |

### `resources/views/livewire/hrm/shifts/index.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 22 | attr:value | active |
| 23 | attr:value | inactive |

### `resources/views/livewire/income/form.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 42 | attr:value | cash |
| 43 | attr:value | bank_transfer |
| 44 | attr:value | card |
| 45 | attr:value | cheque |

### `resources/views/livewire/inventory/barcode-print.blade.php` — (6)

| Line | Kind | Text |
|---:|---|---|
| 126 | attr:value | barcode |
| 127 | attr:value | qrcode |
| 134 | attr:value | small |
| 135 | attr:value | medium |
| 136 | attr:value | large |
| 207 | text | [QR] |

### `resources/views/livewire/inventory/batches/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 80 | attr:value | active |
| 81 | attr:value | expired |
| 82 | attr:value | depleted |

### `resources/views/livewire/inventory/product-compatibility.blade.php` — (22)

| Line | Kind | Text |
|---:|---|---|
| 221 | attr:value | sedan |
| 222 | attr:value | suv |
| 223 | attr:value | truck |
| 224 | attr:value | van |
| 225 | attr:value | motorcycle |
| 226 | attr:value | bus |
| 233 | attr:value | gasoline |
| 234 | attr:value | diesel |
| 235 | attr:value | electric |
| 236 | attr:value | hybrid |
| 290 | attr:value | front |
| 291 | attr:value | rear |
| 292 | attr:value | left |
| 293 | attr:value | right |
| 294 | attr:value | front_left |
| 295 | attr:value | front_right |
| 296 | attr:value | rear_left |
| 297 | attr:value | rear_right |
| 298 | attr:value | engine |
| 299 | attr:value | transmission |
| 300 | attr:value | interior |
| 301 | attr:value | exterior |

### `resources/views/livewire/inventory/product-store-mappings/form.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 37 | attr:placeholder | e.g. 1234567890 |

### `resources/views/livewire/inventory/products/form.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 219 | attr:value | stock |
| 220 | attr:value | service |
| 232 | attr:value | active |
| 233 | attr:value | inactive |
| 257 | attr:value | $thumbnail_media_id |

### `resources/views/livewire/inventory/products/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 41 | attr:value | active |
| 42 | attr:value | inactive |
| 54 | attr:value | stock |
| 55 | attr:value | service |

### `resources/views/livewire/inventory/serials/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 80 | attr:value | in_stock |
| 81 | attr:value | sold |
| 82 | attr:value | returned |
| 83 | attr:value | defective |

### `resources/views/livewire/inventory/services/form.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 89 | attr:value | minutes |
| 90 | attr:value | hours |
| 91 | attr:value | days |
| 102 | attr:value | active |
| 103 | attr:value | inactive |

### `resources/views/livewire/inventory/stock-alerts.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 20 | attr:value | all |
| 21 | attr:value | low |
| 22 | attr:value | out |

### `resources/views/livewire/manufacturing/bills-of-materials/form.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 68 | attr:value | draft |
| 69 | attr:value | active |
| 70 | attr:value | archived |

### `resources/views/livewire/manufacturing/bills-of-materials/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 89 | attr:value | active |
| 90 | attr:value | draft |
| 91 | attr:value | archived |

### `resources/views/livewire/manufacturing/production-orders/form.blade.php` — (10)

| Line | Kind | Text |
|---:|---|---|
| 77 | attr:value | low |
| 78 | attr:value | normal |
| 79 | attr:value | high |
| 80 | attr:value | urgent |
| 91 | attr:value | draft |
| 92 | attr:value | planned |
| 93 | attr:value | released |
| 94 | attr:value | in_progress |
| 95 | attr:value | completed |
| 96 | attr:value | cancelled |

### `resources/views/livewire/manufacturing/production-orders/index.blade.php` — (10)

| Line | Kind | Text |
|---:|---|---|
| 100 | attr:value | draft |
| 101 | attr:value | confirmed |
| 102 | attr:value | released |
| 103 | attr:value | in_progress |
| 104 | attr:value | completed |
| 105 | attr:value | cancelled |
| 111 | attr:value | low |
| 112 | attr:value | normal |
| 113 | attr:value | high |
| 114 | attr:value | urgent |

### `resources/views/livewire/manufacturing/timeline.blade.php` — (6)

| Line | Kind | Text |
|---:|---|---|
| 13 | attr:value | week |
| 14 | attr:value | month |
| 37 | attr:value | draft |
| 38 | attr:value | planned |
| 39 | attr:value | in_progress |
| 40 | attr:value | completed |

### `resources/views/livewire/manufacturing/work-centers/form.blade.php` — (8)

| Line | Kind | Text |
|---:|---|---|
| 68 | attr:value | manual |
| 69 | attr:value | machine |
| 70 | attr:value | assembly |
| 71 | attr:value | quality_control |
| 72 | attr:value | packaging |
| 103 | attr:value | active |
| 104 | attr:value | maintenance |
| 105 | attr:value | inactive |

### `resources/views/livewire/manufacturing/work-centers/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 89 | attr:value | active |
| 90 | attr:value | maintenance |
| 91 | attr:value | inactive |

### `resources/views/livewire/pos/terminal.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 267 | attr:value | cash |
| 268 | attr:value | card |
| 269 | attr:value | transfer |
| 270 | attr:value | cheque |

### `resources/views/livewire/projects/expenses.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 87 | attr:value | materials |
| 88 | attr:value | labor |
| 89 | attr:value | equipment |
| 90 | attr:value | other |

### `resources/views/livewire/projects/form.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 64 | attr:value | planning |
| 65 | attr:value | active |
| 66 | attr:value | on_hold |
| 67 | attr:value | completed |
| 68 | attr:value | cancelled |

### `resources/views/livewire/projects/gantt-chart.blade.php` — (7)

| Line | Kind | Text |
|---:|---|---|
| 13 | attr:value | week |
| 14 | attr:value | month |
| 15 | attr:value | quarter |
| 48 | attr:value | planning |
| 49 | attr:value | active |
| 50 | attr:value | on_hold |
| 51 | attr:value | completed |

### `resources/views/livewire/projects/index.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 80 | attr:value | planning |
| 81 | attr:value | active |
| 82 | attr:value | on_hold |
| 83 | attr:value | completed |
| 84 | attr:value | cancelled |

### `resources/views/livewire/purchases/form.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 55 | attr:value | draft |
| 56 | attr:value | pending |
| 57 | attr:value | posted |
| 58 | attr:value | received |

### `resources/views/livewire/purchases/index.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 94 | attr:value | draft |
| 95 | attr:value | pending |
| 96 | attr:value | posted |
| 97 | attr:value | received |
| 98 | attr:value | cancelled |

### `resources/views/livewire/purchases/requisitions/form.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 22 | attr:value | low |
| 23 | attr:value | normal |
| 24 | attr:value | high |

### `resources/views/livewire/purchases/requisitions/index.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 59 | attr:value | draft |
| 60 | attr:value | pending_approval |
| 61 | attr:value | approved |
| 62 | attr:value | rejected |
| 63 | attr:value | converted |

### `resources/views/livewire/rental/contracts/form.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 61 | attr:value | draft |
| 62 | attr:value | active |
| 63 | attr:value | ended |
| 64 | attr:value | cancelled |

### `resources/views/livewire/rental/contracts/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 24 | attr:value | draft |
| 25 | attr:value | active |
| 26 | attr:value | ended |
| 27 | attr:value | cancelled |

### `resources/views/livewire/rental/reports/dashboard.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 142 | text | whereDate('end_date', ' |

### `resources/views/livewire/rental/tenants/index.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 69 | attr:value | active |
| 70 | attr:value | inactive |

### `resources/views/livewire/rental/units/form.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 61 | attr:value | available |
| 62 | attr:value | occupied |
| 63 | attr:value | maintenance |

### `resources/views/livewire/rental/units/index.blade.php` — (3)

| Line | Kind | Text |
|---:|---|---|
| 31 | attr:value | available |
| 32 | attr:value | occupied |
| 33 | attr:value | maintenance |

### `resources/views/livewire/reports/sales-analytics.blade.php` — (6)

| Line | Kind | Text |
|---:|---|---|
| 9 | attr:value | today |
| 10 | attr:value | week |
| 11 | attr:value | month |
| 12 | attr:value | quarter |
| 13 | attr:value | year |
| 14 | attr:value | custom |

### `resources/views/livewire/reports/scheduled-reports/form.blade.php` — (7)

| Line | Kind | Text |
|---:|---|---|
| 67 | attr:value | daily |
| 68 | attr:value | weekly |
| 69 | attr:value | monthly |
| 70 | attr:value | quarterly |
| 125 | attr:value | pdf |
| 126 | attr:value | excel |
| 127 | attr:value | csv |

### `resources/views/livewire/sales/form.blade.php` — (8)

| Line | Kind | Text |
|---:|---|---|
| 55 | attr:value | draft |
| 56 | attr:value | pending |
| 57 | attr:value | completed |
| 58 | attr:value | cancelled |
| 170 | attr:value | cash |
| 171 | attr:value | card |
| 172 | attr:value | bank_transfer |
| 173 | attr:value | cheque |

### `resources/views/livewire/sales/index.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 95 | attr:value | draft |
| 96 | attr:value | pending |
| 97 | attr:value | posted |
| 98 | attr:value | completed |
| 99 | attr:value | cancelled |

### `resources/views/livewire/suppliers/index.blade.php` — (1)

| Line | Kind | Text |
|---:|---|---|
| 53 | text | balance |

### `resources/views/livewire/warehouse/locations/form.blade.php` — (6)

| Line | Kind | Text |
|---:|---|---|
| 30 | text | @endif |
| 40 | attr:value | main |
| 41 | attr:value | secondary |
| 42 | attr:value | virtual |
| 49 | attr:value | active |
| 50 | attr:value | inactive |

### `resources/views/livewire/warehouse/locations/index.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 60 | attr:value | active |
| 61 | attr:value | inactive |

### `resources/views/livewire/warehouse/movements/index.blade.php` — (2)

| Line | Kind | Text |
|---:|---|---|
| 53 | attr:value | in |
| 54 | attr:value | out |

### `resources/views/livewire/warehouse/transfers/form.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 46 | attr:value | pending |
| 47 | attr:value | in_transit |
| 48 | attr:value | completed |
| 49 | attr:value | cancelled |

### `resources/views/livewire/warehouse/transfers/index.blade.php` — (4)

| Line | Kind | Text |
|---:|---|---|
| 59 | attr:value | pending |
| 60 | attr:value | in_transit |
| 61 | attr:value | completed |
| 62 | attr:value | cancelled |

### `resources/views/livewire/warehouse/warehouses/form.blade.php` — (6)

| Line | Kind | Text |
|---:|---|---|
| 54 | attr:value | main |
| 55 | attr:value | transit |
| 56 | attr:value | returns |
| 57 | attr:value | damaged |
| 64 | attr:value | active |
| 65 | attr:value | inactive |

### `resources/views/welcome.blade.php` — (5)

| Line | Kind | Text |
|---:|---|---|
| 55 | text | Let's get started |
| 56 | text | Laravel has an incredibly rich ecosystem. |
| 56 | text | We suggest starting with the following. |
| 67 | text | Documentation |
| 94 | text | Laracasts |