# DB‑First Clean Status — Stage 18 (2026‑02‑18)

Base reference: **apmoerp81_stage17_code_reference_fix_20260218.zip**

This stage focused on **Phase C (static column scan)** + **Phase D (multi‑branch logic checks)**, targeting the same class of failures you hit before:
- Incorrect / missing column names in Backend queries or Livewire/Views
- Seeder inserting columns that do not exist
- Multi‑branch scoping (BranchScope) hiding data due to missing `branch_id`

---

## What was checked

### 1) Migrations sanity
- Checked for **duplicate column additions on fresh DB** ("Duplicate column name" style failures) by scanning all `Schema::table()` additions against earlier `Schema::create()` definitions.
- Result: **No duplicate candidates found** in the current migration set.

### 2) Model vs Schema (DB‑First)
- Compared **fillable fields** for models against extracted schema columns.
- Result: **No missing fillable columns** (after ignoring explicit legacy alias mutators where applicable).

### 3) Livewire ↔ Views binding
- Static scan for `wire:model` properties and `wire:click/submit` methods used in views.
- Filtered out Livewire built‑ins like `$set` and `$toggle`.
- Result: **No real missing property/method bindings detected** in the mapped views.

### 4) Sorting columns in Livewire Index tables
- Matched blade `sortBy('...')` usage against component `allowedSortColumns()`.
- Fixed mismatches where views still used legacy `code` for models that are DB‑first `reference_number`.

### 5) Multi‑branch logic (BranchScope)
- Verified models extending `BaseModel` have:
  - `deleted_at` (SoftDeletes)
  - `branch_id` when required
- Result: Tables without `branch_id` are either:
  - explicitly excluded (e.g. `branches`), or
  - do not have `branch_id` in fillable, so BranchScope safely skips scoping.

---

## Fixes applied in this stage

### A) Manufacturing — missing columns used by the code
**File:** `database/migrations/2026_01_05_000004_create_manufacturing_tables.php`

1) `production_orders`
- Added `approved_at` (nullable timestamp) because the model/workflow uses it.

2) `production_order_items`
- Added missing workflow columns used by `ProductionOrderItem`:
  - `quantity_issued`
  - `issued_by`
  - `is_returned`
  - `returned_at`
  - `returned_by`
  - `notes`
  - `metadata`
- Normalized decimals for costs:
  - `unit_cost` and `total_cost` changed to **decimal(18,4)** to match model casts.

### B) Purchases & Sales — remove legacy sort column
**Files:**
- `resources/views/livewire/purchases/index.blade.php`
- `resources/views/livewire/sales/index.blade.php`

- Updated sorting from `sortBy('code')` to `sortBy('reference_number')`.
- Updated sort indicator comparison `$sortField === ...` accordingly.

### C) Global Search — correct canonical identifier
**File:** `app/Services/GlobalSearchService.php`

- Updated searchable config for:
  - `sales.title` => `reference_number`
  - `purchases.title` => `reference_number`

This avoids producing empty/incorrect titles after removing `code` aliases from the models.

---

## How to verify locally

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

If anything fails, the error should now be a true missing feature/logic issue (not column mismatch / seeder mismatch).

---

## Next (if you continue with another agent)

1) Run runtime smoke tests on core flows:
- Purchases create → GRN receive → post
- Sales create → POS close day
- Manufacturing create production order → issue materials → complete

2) If new SQL errors appear, treat **the code + UI as the reference**, then:
- add canonical columns to the **create migrations** (fresh DB approach)
- update seeders accordingly
- avoid adding alias columns unless the UI explicitly requires them

