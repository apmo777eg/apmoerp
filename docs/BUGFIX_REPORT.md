# Bugfix Report (2026-02-19)

This build includes a comprehensive UI/UX + Livewire audit pass focused on:
- Fixing the export download break and the `missing ) after argument list` frontend SyntaxError
- Making settings persistence real (DB-backed) with clear UX feedback
- Correct module filtering for product forms (supports_items)
- Correct unit filtering for product forms (supports_products)
- Fixing z-index / stacking-context issues that caused modals & dropdowns to be clipped
- Standardizing Livewire feedback (toasts) and adding a global loading indicator

## 1) Export not downloading + frontend SyntaxError

### Symptoms
- Console errors like:
  - `Uncaught SyntaxError: missing ) after argument list`
  - `Failed to execute 'replaceWith' on 'Element': missing ) after argument list`
- Export toast appears (`Export prepared. Download starting...`) but no download starts.

### Root causes
- Some Blade translations were injected into JavaScript/Alpine/Livewire expressions inside single quotes. Any translation containing an apostrophe would break parsing and trigger: `missing ) after argument list`.
- Layout applied `transform` / `contain` on `.erp-card`, creating stacking contexts that broke `position: fixed` (modals) and caused dropdowns/popovers to render *under* later sections.

### Fix
- Replaced translation injection inside JS expressions with `@js(...)` / `@json(...)` (safe JS encoding).
- Removed `transform` / `contain` "performance" rules from the layout to prevent stacking-context clipping.
- Ensured export uses a Livewire event + hidden iframe download trigger (no window.open blockers).

Files:
- `resources/views/layouts/app.blade.php`
- `resources/views/components/ui/keyboard-shortcuts.blade.php`
- `resources/views/livewire/admin/modules/form.blade.php`
- `resources/views/livewire/dashboard/index.blade.php`
- `resources/views/livewire/dashboard/customizable-dashboard.blade.php`
- `resources/views/livewire/pos/terminal.blade.php`
- `resources/views/livewire/reports/sales-analytics.blade.php`
- `resources/views/livewire/purchases/requisitions/index.blade.php`
- `resources/views/livewire/purchases/quotations/index.blade.php`

## 2) Admin Settings not saving (/admin/settings)

### Root causes
- `default_currency` dropdown was saving the wrong value (`reference_number`) while validation expects a 3-letter ISO code.
- Validation exceptions were caught and swallowed, so field errors were not displayed.
- No clear “success/fail” feedback for Livewire actions.

### Fix
- Currency dropdown now saves ISO currency `code` (EGP, USD, …).
- Standardized settings save methods:
  - Validation errors now show field errors + toast error.
  - Success saves show toast success.
  - Unexpected errors are reported + toast error.
- Added loading states on all settings save buttons.

Files:
- `app/Livewire/Admin/Settings/UnifiedSettings.php`
- `resources/views/livewire/admin/settings/unified-settings.blade.php`
- `resources/views/layouts/app.blade.php` (also shows `session('error')` for full page requests)

## 3) Product module dropdown shows wrong modules (rental/manufacturing/etc)

### Root cause
- `supports_items` default was effectively `true` on seeded modules, so the “product module” filter could not work.

### Fix
- Default `supports_items` is now `false` (system module) and only explicit product modules are flagged `supports_items = true`.
- Seeder now enforces `supports_items=false` for all modules unless explicitly set.

Files:
- `database/migrations/2026_01_01_000004_create_modules_table.php`
- `app/Models/Module.php`
- `database/seeders/ModulesSeeder.php`

## 4) Product unit dropdown shows all units (should show product units only)

### Root cause
- Units table had no way to distinguish product units vs other module units (e.g. time units).

### Fix
- Added `supports_products` boolean to `units_of_measure` (DB is fresh, so change is inside the existing migration).
- Seeder marks time-based units (`type = time`) as `supports_products = false` by default.
- Product form now loads only `UnitOfMeasure::active()->forProducts()`.

Files:
- `database/migrations/2026_01_02_000001_create_currencies_units_tables.php`
- `app/Models/UnitOfMeasure.php`
- `database/seeders/UnitsOfMeasureSeeder.php`
- `app/Livewire/Inventory/Products/Form.php`

## 5) HRM My Leaves modal shows grey overlay / bad z-index / scroll issues

### Fix
- Rebuilt the leave request modal using the unified “popup card” style (similar to the product image gallery popup).
- Uses `z-modal` / `z-modal-backdrop` so it appears above the navbar.
- Ensures the popup is scroll-safe on small screens.
- Added submit loading state.
- Switched user feedback to toast notifications.

Files:
- `resources/views/livewire/hrm/self-service/my-leaves.blade.php`
- `app/Livewire/Hrm/SelfService/MyLeaves.php`

## 6) Admin Modules icon dropdown (z-index/overlap)

### Fix
- Increased stacking priority for the “Appearance” card so the icon picker dropdown overlays the next sections correctly.

Files:
- `resources/views/livewire/admin/modules/form.blade.php`

## 7) Livewire UX standardization (toasts + loading)

### Fix
- Introduced a shared Livewire base component to centralize UX behaviors:
  - Convert `session()->flash()` messages into toast notifications for Livewire (AJAX) actions
  - Prevent stale flash messages on subsequent page loads
- Standardized export feedback to always use toast notifications.
- Added a global top loading bar for *any* Livewire request (not only navigation) with a small delay to avoid flicker.
- Added "JS enabled" flag (`html.js`) and hide inline flash blocks in the main layout to avoid duplicate feedback.

Files:
- `app/Livewire/BaseComponent.php`
- `app/Traits/HasExport.php`
- `resources/views/layouts/app.blade.php`

---

## Quick regression checklist

1. **Export**
   - Customers → Export CSV → Download starts.
   - Repeat for 2–3 other pages that use export.

2. **Settings**
   - `/admin/settings` → General tab:
     - Change default currency, save, refresh page → value persists.
     - Trigger a validation error (clear required field), save → field errors + toast error.

3. **Products module dropdown**
   - Product create/edit form → module dropdown:
     - Should show only product modules (not rental/manufacturing/hrm…)

4. **Products units dropdown**
   - Product create/edit form → unit dropdown:
     - Should exclude time units by default (hour/day/week…)

5. **My Leaves popup**
   - `/app/hrm/my-leaves` → Request Leave:
     - Popup appears above navbar.
     - Scroll works if screen height is small.
     - Submit shows loading + toast.
