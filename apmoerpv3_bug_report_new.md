# APMO ERP v3 — New / Unfixed Bug Report

Scan target: `apmoerpv3.zip` (extracted locally).  
Scope: **all project code** (PHP / Livewire / services / listeners / console commands / API controllers).  
Ignored (per request): **database migrations/seeders** (note: migrations folder inside the zip appears empty).

> ✅ This report lists **ONLY**:
> - **New bugs found in v3**, and
> - **Bugs still unfixed** (were present previously and remain).
> 
> ❌ It does **not** repeat items that appear fixed.

---

## CRITICAL-01 — Branch isolation disabled in console (queues / schedulers / commands)
**File:** `app/Models/Scopes/BranchScope.php`  
**Lines:** 31–34

### Problem
The global branch scope returns early when running in console:
```php
if (app()->runningInConsole()) {
    return;
}
```

### Impact
- **Queue workers** (`php artisan queue:work`) and **scheduled tasks** (`schedule:run`) are “console”.
- Any code executed there will run **without branch isolation**, which is extremely dangerous in a multi-branch ERP:
  - Background jobs could read/update records across all branches.
  - Reports/exports/automations may mix branch data.
  - Store sync jobs, recurring invoices, payroll runs, etc. can affect the wrong branch.

### Recommendation
Replace the blanket console skip with a **targeted allow-list** (similar to what you already did in `AuditsChanges`). Examples:
- Skip only for known safe commands: migrations, seeders, tinker, etc.
- Otherwise apply the scope normally.

---

## CRITICAL-02 — Warehouse “status” is an accessor, but code queries it as a DB column
**Reference model:** `app/Models/Warehouse.php`  
**Lines:** 85–104

Warehouse uses **`is_active`** column and provides `getStatusAttribute()` accessor:
```php
public function scopeActive(...) { return $query->where('is_active', true); }
public function getStatusAttribute(): string { return $this->is_active ? 'active' : 'inactive'; }
```

### 1) Inventory API warehouse resolution will throw SQL errors
**File:** `app/Http/Controllers/Api/V1/InventoryController.php`  
**Lines:** 322–368

Problematic queries:
- `->where('status', 'active')`

**Impact:** SQL error (“unknown column status”) OR silently wrong filtering (depending on DB).

### 2) Orders API warehouse resolution will throw SQL errors
**File:** `app/Http/Controllers/Api/V1/OrdersController.php`  
**Lines:** 332–366

Problematic queries:
- `->where('status', 'active')`

### 3) Purchases Livewire form uses the same invalid filter
**File:** `app/Livewire/Purchases/Form.php`  
**Lines:** 389–399

Problematic query:
- `Warehouse::where('status', 'active')`

### Recommendation
Replace everywhere:
- `where('status', 'active')` → `where('is_active', true)`

Or use the model scope:
- `Warehouse::active()`

---

## CRITICAL-03 — Store Integration: `syncStock()` references undefined variables (will crash)
**File:** `app/Http/Controllers/Api/StoreIntegrationController.php`  
**Lines:** 141–156

### Problem
Inside `syncStock()`, the code references variables that do not exist in this method:
- `$saleItem`
- `$sale`
- `$storeOrder`

Example:
```php
'quantity' => -$saleItem->quantity,
'reference_id' => $sale->id,
'description' => "Sale #{$sale->reference_number} - Store Order #{$storeOrder->order_number}",
```

### Impact
- Any call to `syncStock` will raise a **fatal error**, breaking the endpoint completely.

### Recommendation
Refactor `syncStock()` to use the **actual payload** (`sku`, `quantity`) and resolve the ERP product + warehouse properly. If the feature is intentionally disabled, remove the broken code path entirely.

---

## CRITICAL-04 — API Orders creation uses wrong SaleItem field names (items may not insert)
**File:** `app/Http/Controllers/Api/V1/OrdersController.php`

### 1) Wrong sort field allows SQL error
**Lines:** 36–45 and 57–64

Validation allows `sort_by=total`:
```php
'sort_by' => '... in:created_at,order_date,total,status'
```
But query does:
```php
->orderBy($sortBy)
```
If `total` is not a real DB column (the project migrated to `total_amount`), this will cause SQL errors.

**Fix:** map `total` → `total_amount` or change validation list.

### 2) Order items are built with legacy keys not fillable on `SaleItem`
**Lines:** 166–177

The controller creates items like:
```php
'qty' => $item['quantity'],
'discount' => $lineDiscount,
'tax_rate' => 0,
```
But `SaleItem` fillable fields are:
- `quantity`
- `discount_amount`
- `tax_percent`

So these values will be **ignored by mass assignment**, leading to:
- DB insert failures (NOT NULL constraints), or
- Incorrect item data (quantity/tax/discount missing).

**Fix:** write the correct keys:
- `qty` → `quantity`
- `discount` → `discount_amount`
- `tax_rate` → `tax_percent`

---

## CRITICAL-05 — StoreSyncService order sync is broken (legacy schema keys + cross-branch collisions)
**File:** `app/Services/Store/StoreSyncService.php`

### A) Order creation uses legacy totals / item keys (ignored by current `Sale` / `SaleItem` schema)
**Shopify:** lines **345–368**  
**WooCommerce:** lines **451–475**  
**Laravel store:** lines **656–693**

The service creates sales using legacy keys like:
- `sub_total`, `tax_total`, `discount_total`, `grand_total`

And sale items using legacy keys like:
- `qty`, `discount`

But the current ERP schema expects (per models / fillables):
- `Sale`: `subtotal`, `tax_amount`, `discount_amount`, `total_amount`
- `SaleItem`: `quantity`, `discount_amount`, `tax_percent`

**Impact**
- Synced orders may be created with **missing/zero totals** or fail DB constraints.
- Line items may insert with missing `quantity/discount/tax` values (or fail).

### B) Existing-order idempotency checks are not scoped by branch
**Shopify:** lines **317–320**  
**WooCommerce:** lines **420–423**  
**Laravel store:** lines **623–626**

Example:
```php
Sale::where('external_reference', $externalId)
    ->where('channel', 'shopify')
    ->first();
```

**Impact**
- If two branches/stores have the same external order ID (common across independent stores), one branch can **update the other branch’s sale**.

### C) Customer lookup is not scoped by branch (dangerous in webhook/no-auth context)
**Shopify:** `Customer::firstOrCreate()` at **lines 334–342**  
**WooCommerce:** **lines 437–447**  
**Laravel store:** **lines 641–651**

`firstOrCreate` searches only by `email`:
```php
Customer::firstOrCreate(['email' => ...], ['branch_id' => $store->branch_id, ...])
```

If the same email exists in another branch, the existing customer can be reused and the new sale will be attached to the **wrong branch customer**.  
This is especially risky because store webhooks often run **without an authenticated user**, so the global BranchScope may not protect the query.

### D) Line items can be created with `product_id = null` when no mapping exists
**Shopify:** lines **357–368** (`$productMapping?->product_id`)  
**WooCommerce:** lines **463–475**  
(Also likely in Laravel store order items)

**Impact**
- If `sale_items.product_id` is NOT NULL → sync crashes.
- Even if nullable → orphan items break inventory & reporting.

### Recommendation
1) Create a single mapping layer: store payload → ERP schema:
   - `sub_total` → `subtotal`
   - `tax_total` → `tax_amount`
   - `discount_total` → `discount_amount`
   - `grand_total` → `total_amount`
   - `qty` → `quantity`
   - `discount` → `discount_amount`

2) Fix idempotency scope:
   - Add `->where('branch_id', $store->branch_id)` to the existing-order query.

3) Fix customer uniqueness:
   - Use `firstOrCreate(['email' => ..., 'branch_id' => $store->branch_id], [...])` (and consider phone-based matching too).

4) Handle unmapped products:
   - Reject the order with clear logging, or create a special “unmapped SKU” placeholder workflow.


---

## HIGH-01 — Analytics services use `SUM(qty)` but DB column is `quantity`
### 1) ABCAnalysisService
**File:** `app/Services/Analytics/ABCAnalysisService.php`  
**Lines:** 52–57 and 164–168

Uses:
- `DB::raw('SUM(qty) as total_qty')`

### 2) CustomerBehaviorService
**File:** `app/Services/Analytics/CustomerBehaviorService.php`  
**Lines:** 194–199

Uses:
- `DB::raw('SUM(qty) as total_qty')`

### Impact
These queries will fail at runtime (unknown column `qty`) or return incorrect results.

### Fix
Replace `SUM(qty)` with `SUM(quantity)`.

---

## HIGH-02 — Branch “active” column referenced but Branch uses `is_active`
### 1) GenerateRecurringInvoices command
**File:** `app/Console/Commands/GenerateRecurringInvoices.php`  
**Line:** 39

```php
$branches = Branch::query()->where('active', true);
```

### 2) RunPayroll command
**File:** `app/Console/Commands/RunPayroll.php`  
**Line:** 52

```php
$branches = Branch::query()->where('active', true);
```

### Impact
Likely SQL error (unknown column `active`) OR no filtering.

### Fix
Use:
- `Branch::active()` (Branch already defines `scopeActive()` using `is_active`), or
- `where('is_active', true)`.

---

## HIGH-03 — StoreOrderToSaleService can create sale items with `product_id = null`
**File:** `app/Services/Store/StoreOrderToSaleService.php`  
**Lines:** 143–163

### Problem
If mapping cannot find a product by SKU/variation, it still proceeds:
```php
$productId = $product?->id;
...
SaleItem::query()->create([
    'product_id' => $productId,
    ...
]);
```

### Impact
- If `sale_items.product_id` is NOT NULL → conversion crashes.
- Even if nullable, you’ll have orphan items that break inventory & reporting.

### Fix
- Validate: if product not found → either reject conversion or create a special “unmapped product” record.
- At minimum, **skip item** and log error including store SKU.

---

## MEDIUM-01 — External order id field is inconsistent between integrations
**Files:**
- `app/Http/Controllers/Api/V1/OrdersController.php` (uses `reference_number` for `external_id`) — lines 113–121
- `app/Services/Store/StoreSyncService.php` (uses `external_reference`) — lines 317–321 / 620–626

### Problem
Some paths treat “external id” as `reference_number`, others as `external_reference`.

### Impact
- Duplicate orders or inability to find existing orders for updates.
- Potential conflicts with internal invoice numbering (`Sale::creating()` auto-generates `reference_number` when empty).

### Recommendation
Standardize:
- Internal invoice number: `reference_number`
- External channel order id: `external_reference`

And always scope idempotency by **branch_id** (at least).

---

## STILL-UNFIXED (from earlier) — Stock quantity drift vs stock movements
### Symptom
- Stock movements are being recorded (`stock_movements`), but `products.stock_quantity` is not consistently updated.

### Evidence (examples)
1) Stock movement creation does not update product cached quantity
- **File:** `app/Repositories/StockMovementRepository.php` (create method)

2) Dashboard uses `products.stock_quantity` directly
- **File:** `app/Services/Dashboard/DashboardDataService.php` — lines 195–243

### Impact
- Low-stock, inventory KPIs, and any code relying on `stock_quantity` can become incorrect.

### Recommendation
Pick one source of truth:
- **Option A (preferred):** compute stock from movements everywhere (and remove the cached `stock_quantity` dependency), OR
- **Option B:** update `products.stock_quantity` atomically whenever a movement is created.

