# Final Fix Report (Feb 19, 2026)

## 1) ERP Store API Token Management (Admin → Stores)

- Added a full **ERP Store API** section to the store edit screen:
  - Generate Store API tokens
  - Select token abilities (read/write per domain)
  - Optional expiry date
  - Show the token **once** after creation (copy-to-clipboard)
  - List existing tokens + revoke

## 2) Store API Authentication + Security

- **Store::generateApiToken()** now always stamps `branch_id` from the store itself.
- Store token authentication middleware no longer uses `withoutGlobalScopes()`.
  - It now bypasses **only BranchScope** (`withoutBranchScope()`), keeping SoftDeletes intact.
  - This prevents revoked (soft-deleted) tokens from authenticating.

## 3) Webhooks Safety

- Webhook store resolution no longer uses `withoutGlobalScopes()`.
  - Uses `withoutBranchScope()` (preserves SoftDeletes).
- Warehouse resolution for webhooks also bypasses only BranchScope.

## 4) API Documentation Updated

- Updated **Admin → API Docs** endpoint definitions:
  - Inventory payloads now match the real API (`qty`, `direction`, `external_id`, etc.)
  - Added missing endpoints (e.g., external ID endpoints + Laravel webhook)
  - Updated examples to match the real response envelope (`success/message/data`).

## 5) UI Stability

- Added global support for `wire:confirm="..."` across the app using SweetAlert.
  - Prevents broken actions where `wire:confirm` was previously ignored.

## 6) Extra Docs

- Added `docs/STORE_API.md` for a practical integration guide.
