# Static Scan Report (Stage20)

This project follows **code-as-reference** for a fresh database.
The goal of this report is to reduce "Unknown column" and schema mismatch bugs by running a best-effort static scan.

## What was scanned

- **Migrations**: `database/migrations` (Schema::create blocks)
- **Backend code**: `app/**/*.php`
- Focus patterns:
  - `where('...')`, `orWhere('...')`
  - `orderBy('...')`, `groupBy('...')`
  - `select('...')`, `addSelect('...')`
  - Legacy columns known to cause 500s: `code`, `grand_total`, `period`, `key`, and manufacturing legacy names.

## Results summary

- No confirmed `where/orderBy/groupBy/select` references to columns that are missing from *project migrations*.
- "Unknown" hits found by the scanner were **intentionally ignored** because they are one of:
  - Package-managed columns (e.g. Spatie permissions `guard_name`)
  - JSON path queries (e.g. `data->type`, `payload->meta->source`)
  - Report aliases (e.g. `hour`, `segment`, `branch_name`, etc.)

## How to run the scan

```bash
php artisan db:static-scan
```

To fail the command if any issues are found:

```bash
php artisan db:static-scan --fail
```

## Next recommended runtime checks

Even with static scans, runtime smoke tests are still required:

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

Then open the main list pages (Purchases/Sales/Inventory/Manufacturing/HR/Projects) and try basic create/edit flows.
