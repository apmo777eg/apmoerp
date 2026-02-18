# Stage17 (Code-as-Reference) – 2026-02-18

## Base
- Started from: `apmoerp81_stage13_dbfirst_clean_phaseCD_20260218.zip`

## Goal
- Align **database schema** to match what **backend + Livewire + Blade** already expect for Warehouses.
- Fix the migration failure you hit in `2026_01_09_000001_create_missing_tables`.

## Fix 1 – Warehouses schema aligned to code
**Problem:** Backend/Livewire expect `warehouses.status` and `warehouses.notes` (and also write `created_by/updated_by`), but the table migration did not include these columns → seeding fails with `Unknown column 'status'`.

**Changes (DB):** `database/migrations/2026_01_02_000003_create_warehouses_table.php`
- Added columns:
  - `section` (string, nullable)
  - `status` (string, default `active`)
  - `notes` (text, nullable)
  - `created_by` / `updated_by` (FK users, nullable)
- Added indexes for `status`, `created_by`, `updated_by`.

**Changes (Model):** `app/Models/Warehouse.php`
- Added to `$fillable`: `notes`, `created_by`, `updated_by`.
- Removed `getStatusAttribute()` so `status` is read from DB (now that the column exists).
- Kept `setStatusAttribute()` + `setIsActiveAttribute()` to keep `status` and `is_active` consistent.

**Changes (Livewire logic):**
- `app/Livewire/Warehouse/Warehouses/Form.php`
  - Code uniqueness checks + validation now run **per branch** (matches DB unique index `uq_wh_branch_code`).
- `app/Livewire/Warehouse/Locations/Form.php`
  - Same per-branch unique validation for `code`.

## Fix 2 – Remove duplicate `cost_center_id` alter
**Problem:** Migration `2026_01_09_000001_create_missing_tables` was altering `purchase_requisitions` to add `cost_center_id` (already exists in main purchases migration) → Duplicate column.

**Changes:** `database/migrations/2026_01_09_000001_create_missing_tables.php`
- Removed the `Schema::table('purchase_requisitions'...)` block entirely.
- Removed unrelated `down()` drops for `departments/cost_centers` (they are created in their own migration).

## Expected outcome
- `php artisan migrate:fresh --seed` should pass the WarehousesSeeder step (no `warehouses.status` error).
- No more duplicate column error from `create_missing_tables`.
