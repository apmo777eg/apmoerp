# apmoerpv17 — Bug Report (New + Still-Unfixed Only)

**Scan target:** `apmoerpv17.zip`

**Scope:** Full codebase scan (ignored `database/`, migrations, seeders).

**Comparison baseline:** `apmoerpv16_bug_report_new_rerun.md`.


## 0) Result Summary

- **New bugs in v17:** **6**

- **Still-unfixed from v16 baseline:** **0** (the previously reported missing-route-method issues + Products warehouse_id issue appear fixed in v17).


---

## 1) New Bugs Found in v17


### NEW-CRITICAL-01

- **File:** `app/Services/SaleService.php`

- **Line:** 216

- **Problem:** Null property access: $journalEntry->is_reversible ?? true will crash if JournalEntry::find() returns null.

- **Impact:** When a sale has `journal_entry_id` that points to a deleted/missing journal entry, voiding the sale will throw a fatal error and the void operation may fail mid-transaction (inventory/accounting inconsistencies possible).

- **Fix:** Use nullsafe access and validate existence before reading properties:
  - `\$isReversible = $journalEntry?->is_reversible ?? true;`
  - Move all property reads inside `if ($journalEntry)`.


**Code context:**

```php
 212:                         try {
 213:                             $accountingService = app(AccountingService::class);
 214:                             $journalEntry = \App\Models\JournalEntry::find($sale->journal_entry_id);
 215:                             // Check if journal entry exists, is posted, and is reversible (default to true if null)
 216:                             $isReversible = $journalEntry->is_reversible ?? true;
 217:                             if ($journalEntry && $journalEntry->status === 'posted' && $isReversible) {
 218:                                 $accountingService->reverseJournalEntry(
 219:                                     $journalEntry,
 220:                                     "Sale voided: {$reason}",
```


### NEW-HIGH-02

- **File:** `app/Services/TaxService.php`

- **Line:** 22

- **Problem:** Null property access: TaxService::rate() uses $tax->rate ?? 0.0 without nullsafe; crashes if Tax::find returns null.

- **Impact:** Any call to `TaxService::rate()` with an invalid/missing tax id can crash the request/job, potentially breaking sales/purchases/quotes calculations.

- **Fix:** Guard null: `return (float) ($tax?->rate ?? 0.0);` or `if (! $tax) return 0.0;`.


**Code context:**

```php
  18:             return 0.0;
  19:         }
  20:         $tax = Tax::find($taxId);
  21: 
  22:         return (float) ($tax->rate ?? 0.0);
  23:     }
  24: 
  25:     public function compute(float $base, ?int $taxId): float
  26:     {
```


### NEW-MEDIUM-03

- **File:** `app/Services/UX/SmartSuggestionsService.php`

- **Line:** 181

- **Problem:** Null property access risk: Product::find may return null but code uses $baseProduct->default_price ?? 0 and $bundledProduct->default_price ?? 0 (property access on null).

- **Impact:** If the base product or bundled product is deleted/inactive while suggestions are generated, the feature can crash (dashboard/widgets/API depending on usage).

- **Fix:** Use nullsafe + skip null products:
  - `if (! $baseProduct || ! $bundledProduct) { return null; }` then filter nulls.


**Code context:**

```php
 175:             ->orderByDesc('frequency')
 176:             ->limit($limit)
 177:             ->get();
 178: 
 179:         // Calculate bundle discounts and savings
 180:         return $frequentlyBoughtTogether->map(function ($item) use ($productId) {
 181:             $baseProduct = Product::find($productId);
 182:             $bundledProduct = Product::find($item->product_id);
 183: 
 184:             $totalPrice = bcadd((string) ($baseProduct->default_price ?? 0), (string) ($bundledProduct->default_price ?? 0), 2);
 185:             $suggestedBundlePrice = bcmul($totalPrice, '0.90', 2); // 10% bundle discount
 186:             $savings = bcsub($totalPrice, $suggestedBundlePrice, 2);
 187: 
```


### NEW-HIGH-04

- **File:** `app/Http/Controllers/Api/V1/ProductsController.php`

- **Line:** 178

- **Problem:** Multi-branch logic risk: API enforces global unique SKU (unique:products,sku). If products are branch-scoped, this blocks same SKU across branches and breaks multi-branch ERP.

- **Impact:** In a true multi-branch ERP, enforcing global SKU uniqueness can block legitimate branch catalogs (same SKU in different branches), causing failed sync/import and operational friction.

- **Fix (if SKUs should be per-branch):** change validation to be scoped by `branch_id` (and optionally allow global products with `branch_id = null`). Example using `Rule::unique('products','sku')->where('branch_id', $store->branch_id)`.


**Code context:**

```php
 170:         $store = $this->getStore($request);
 171: 
 172:         if (! $store || ! $store->branch_id) {
 173:             return $this->errorResponse(__('Store authentication required'), 401);
 174:         }
 175: 
 176:         $validated = $request->validate([
 177:             'name' => 'required|string|max:255',
 178:             'sku' => 'required|string|max:100|unique:products,sku',
 179:             'description' => 'nullable|string',
 180:             'price' => 'required|numeric|min:0',
 181:             'cost_price' => 'nullable|numeric|min:0',
 182:             'quantity' => 'required|integer|min:0',
 183:             'category_id' => 'nullable|exists:product_categories,id',
 184:             'warehouse_id' => [
 185:                 'required_with:quantity',
 186:                 Rule::exists('warehouses', 'id')->where('branch_id', $store->branch_id),
```


### NEW-MEDIUM-05

- **File:** `app/Http/Controllers/Api/V1/ProductsController.php`

- **Line:** 182

- **Problem:** Inventory precision bug: API validates quantity as integer, but Product stock_quantity cast is decimal:4 and StockService uses floats. Fractional units will be rejected or truncated.

- **Impact:** Businesses that track fractional quantities (weight/volume/meters) cannot update stock via API; integrations will fail or round incorrectly.

- **Fix:** validate as `numeric` (or `decimal:0,4`) instead of `integer`, and ensure consistent rounding rules.


**Code context:**

```php
 176:         $validated = $request->validate([
 177:             'name' => 'required|string|max:255',
 178:             'sku' => 'required|string|max:100|unique:products,sku',
 179:             'description' => 'nullable|string',
 180:             'price' => 'required|numeric|min:0',
 181:             'cost_price' => 'nullable|numeric|min:0',
 182:             'quantity' => 'required|integer|min:0',
 183:             'category_id' => 'nullable|exists:product_categories,id',
 184:             'warehouse_id' => [
 185:                 'required_with:quantity',
 186:                 Rule::exists('warehouses', 'id')->where('branch_id', $store->branch_id),
 187:             ],
 188:             'barcode' => 'nullable|string|max:100',
```


### NEW-HIGH-06

- **File:** `app/Http/Requests/WarehouseStoreRequest.php`

- **Line:** 19

- **Problem:** Multi-branch logic risk: Warehouse name/code validated unique globally (unique:warehouses,...) instead of per-branch; blocks same warehouse codes across branches.

- **Impact:** Multi-branch setups cannot reuse common warehouse names/codes across branches, which is common (e.g., “Main Warehouse” in each branch). This can block onboarding new branches.

- **Fix:** scope uniqueness by `branch_id` in the request rules (Rule::unique with `where('branch_id', $branchId)`), mirroring how warehouses are already branch-scoped elsewhere.


**Code context:**

```php
  11:     public function authorize(): bool
  12:     {
  13:         return $this->user()?->can('warehouses.create') ?? false;
  14:     }
  15: 
  16:     public function rules(): array
  17:     {
  18:         return [
  19:             'name' => ['required', 'string', 'max:255', 'unique:warehouses,name'],
  20:             'code' => ['nullable', 'string', 'max:50', 'unique:warehouses,code'],
  21:             'address' => ['nullable', 'string', 'max:500'],
  22:         ];
  23:     }
  24: }
```


---

## 2) Still-Unfixed From v16 Baseline

None detected in v17.
