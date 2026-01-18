# APMO ERP v39 — Old (Unsolved) + New Bugs

## Summary

- **Compared versions:** `v38` → `v39`
- **Laravel:** `^12.0`
- **Livewire:** `^4.0.1`
- **Baseline bugs (from v38 report):** `529`
- **Old bugs fixed in v39:** `225`
- **Old bugs still present:** `304`
- **New bugs detected in v39:** `142`

### Notes
- Static heuristic scan (no runtime tests).
- `database/` ignored as requested.

---

## Old bugs not solved yet

### 1. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Livewire/Concerns/LoadsDashboardData.php`
- **Line:** `318`
- **Evidence:** `->selectRaw("{$stockExpr} as current_quantity")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 2. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Livewire/Reports/SalesAnalytics.php`
- **Line:** `248`
- **Evidence:** `->selectRaw("{$dateFormat} as period")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 3. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Livewire/Reports/SalesAnalytics.php`
- **Line:** `414`
- **Evidence:** `->selectRaw("{$hourExpr} as hour")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 4. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `45`
- **Evidence:** `->selectRaw("{$datediffExpr} as recency_days")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 5. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `171`
- **Evidence:** `->selectRaw("{$datediffExpr} as days_since_purchase")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 6. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `52`
- **Evidence:** `->selectRaw("{$daysDiffExpr} as days_since_sale")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 7. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Services/SmartNotificationsService.php`
- **Line:** `58`
- **Evidence:** `->selectRaw("{$stockExpr} as current_quantity")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 8. [HIGH] selectRaw contains interpolated variable (SQL injection risk)
- **Rule ID:** `SELECT_RAW_INTERPOLATION`
- **File:** `app/Services/WorkflowAutomationService.php`
- **Line:** `188`
- **Evidence:** `->selectRaw("*, ({$stockSubquery}) as calculated_stock")`
- **Why it matters:** Never interpolate variables into raw SQL; use bindings or strict token whitelist.

### 9. [HIGH] DB::raw() argument is variable (must be strict whitelist)
- **Rule ID:** `SQL_DBRAW_VAR`
- **File:** `app/Services/Analytics/ProfitMarginAnalysisService.php`
- **Line:** `209`
- **Evidence:** `->groupBy(DB::raw($periodExpr))`
- **Why it matters:** DB::raw with variable expression can become SQL injection if any part is influenced by user input; restrict to known-safe tokens.

### 10. [HIGH] DB::raw() argument is variable (must be strict whitelist)
- **Rule ID:** `SQL_DBRAW_VAR`
- **File:** `app/Services/Analytics/SalesForecastingService.php`
- **Line:** `116`
- **Evidence:** `->groupBy(DB::raw($periodExpr))`
- **Why it matters:** DB::raw with variable expression can become SQL injection if any part is influenced by user input; restrict to known-safe tokens.

### 11. [HIGH] DB::raw() argument is variable (must be strict whitelist)
- **Rule ID:** `SQL_DBRAW_VAR`
- **File:** `app/Services/Analytics/SalesForecastingService.php`
- **Line:** `292`
- **Evidence:** `->groupBy(DB::raw($dateExpr))`
- **Why it matters:** DB::raw with variable expression can become SQL injection if any part is influenced by user input; restrict to known-safe tokens.

### 12. [HIGH] DB::raw() argument is variable (must be strict whitelist)
- **Rule ID:** `SQL_DBRAW_VAR`
- **File:** `app/Services/ScheduledReportService.php`
- **Line:** `119`
- **Evidence:** `return $query->groupBy(DB::raw($dateExpr))`
- **Why it matters:** DB::raw with variable expression can become SQL injection if any part is influenced by user input; restrict to known-safe tokens.

### 13. [HIGH] Raw SQL expression comes from variable (must be strict whitelist)
- **Rule ID:** `SQL_RAW_EXPR_VAR`
- **File:** `app/Console/Commands/CheckDatabaseIntegrity.php`
- **Line:** `264`
- **Evidence:** `$query->whereRaw($where);`
- **Why it matters:** If the expression is influenced by request params (period/grouping/order), enforce a strict whitelist and never pass user input directly.

### 14. [HIGH] Raw SQL expression comes from variable (must be strict whitelist)
- **Rule ID:** `SQL_RAW_EXPR_VAR`
- **File:** `app/Http/Controllers/Api/StoreIntegrationController.php`
- **Line:** `100`
- **Evidence:** `->selectRaw($stockExpr.' as current_stock');`
- **Why it matters:** If the expression is influenced by request params (period/grouping/order), enforce a strict whitelist and never pass user input directly.

### 15. [HIGH] Raw SQL expression comes from variable (must be strict whitelist)
- **Rule ID:** `SQL_RAW_EXPR_VAR`
- **File:** `app/Livewire/Concerns/LoadsDashboardData.php`
- **Line:** `323`
- **Evidence:** `->orderByRaw($stockExpr)`
- **Why it matters:** If the expression is influenced by request params (period/grouping/order), enforce a strict whitelist and never pass user input directly.

### 16. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Livewire/Concerns/LoadsDashboardData.php`
- **Line:** `170`
- **Evidence:** `->whereRaw("{$stockExpr} <= min_stock")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 17. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Livewire/Concerns/LoadsDashboardData.php`
- **Line:** `318`
- **Evidence:** `->selectRaw("{$stockExpr} as current_quantity")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 18. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Livewire/Concerns/LoadsDashboardData.php`
- **Line:** `320`
- **Evidence:** `->whereRaw("{$stockExpr} <= products.min_stock")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 19. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Livewire/Reports/SalesAnalytics.php`
- **Line:** `248`
- **Evidence:** `->selectRaw("{$dateFormat} as period")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 20. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Livewire/Reports/SalesAnalytics.php`
- **Line:** `414`
- **Evidence:** `->selectRaw("{$hourExpr} as hour")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 21. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Models/Product.php`
- **Line:** `308`
- **Evidence:** `->whereRaw("({$stockSubquery}) <= stock_alert_threshold");`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 22. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Models/Product.php`
- **Line:** `328`
- **Evidence:** `return $query->whereRaw("({$stockSubquery}) <= 0");`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 23. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Models/Product.php`
- **Line:** `348`
- **Evidence:** `return $query->whereRaw("({$stockSubquery}) > 0");`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 24. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/AutomatedAlertService.php`
- **Line:** `227`
- **Evidence:** `->whereRaw("({$stockSubquery}) > 0")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 25. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `45`
- **Evidence:** `->selectRaw("{$datediffExpr} as recency_days")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 26. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `171`
- **Evidence:** `->selectRaw("{$datediffExpr} as days_since_purchase")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 27. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `52`
- **Evidence:** `->selectRaw("{$daysDiffExpr} as days_since_sale")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 28. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/ScheduledReportService.php`
- **Line:** `151`
- **Evidence:** `$query->whereRaw("({$stockSubquery}) <= COALESCE(products.reorder_point, 0)");`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 29. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/ScheduledReportService.php`
- **Line:** `151`
- **Evidence:** `$query->whereRaw("({$stockSubquery}) <= COALESCE(products.reorder_point, 0)");`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 30. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/SmartNotificationsService.php`
- **Line:** `58`
- **Evidence:** `->selectRaw("{$stockExpr} as current_quantity")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 31. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/SmartNotificationsService.php`
- **Line:** `59`
- **Evidence:** `->whereRaw("{$stockExpr} <= products.min_stock")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 32. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `56`
- **Evidence:** `->whereRaw("({$stockSubquery}) <= reorder_point")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 33. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `81`
- **Evidence:** `->whereRaw("({$stockSubquery}) <= stock_alert_threshold")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 34. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `82`
- **Evidence:** `->whereRaw("({$stockSubquery}) > COALESCE(reorder_point, 0)")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 35. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/WorkflowAutomationService.php`
- **Line:** `46`
- **Evidence:** `->whereRaw("({$stockSubquery}) <= COALESCE(reorder_point, min_stock, 0)")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 36. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/WorkflowAutomationService.php`
- **Line:** `188`
- **Evidence:** `->selectRaw("*, ({$stockSubquery}) as calculated_stock")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 37. [HIGH] Raw SQL string contains PHP variable (possible SQL injection / unsafe expression)
- **Rule ID:** `SQL_RAW_INTERPOLATION`
- **File:** `app/Services/WorkflowAutomationService.php`
- **Line:** `189`
- **Evidence:** `->orderByRaw("(COALESCE(reorder_point, min_stock, 0) - ({$stockSubquery})) DESC")`
- **Why it matters:** Prefer parameter bindings (? + bindings array) or strict whitelists for identifiers/date parts; never interpolate request-derived input into raw SQL.

### 38. [HIGH] API token accepted from query/body (leak risk)
- **Rule ID:** `TOKEN_IN_QUERY`
- **File:** `app/Http/Middleware/AuthenticateStoreToken.php`
- **Line:** `198`
- **Evidence:** `$queryToken = $request->query('api_token');`
- **Why it matters:** Tokens in query strings leak via logs/referrers/history. Prefer Authorization: Bearer header, signed URLs, or cookies (as appropriate).

### 39. [HIGH] API token accepted from query/body (leak risk)
- **Rule ID:** `TOKEN_IN_QUERY`
- **File:** `app/Http/Middleware/AuthenticateStoreToken.php`
- **Line:** `204`
- **Evidence:** `$bodyToken = $request->input('api_token');`
- **Why it matters:** Tokens in query strings leak via logs/referrers/history. Prefer Authorization: Bearer header, signed URLs, or cookies (as appropriate).

### 40. [MEDIUM] Blade outputs unescaped variable (XSS risk)
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/card.blade.php`
- **Line:** `50`
- **Evidence:** `{!! $actions !!}`
- **Why it matters:** Unescaped output can lead to XSS. Prefer {{ }} escaping, or sanitize/strip tags when HTML is required.

### 41. [MEDIUM] Blade outputs unescaped variable (XSS risk)
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/form/input.blade.php`
- **Line:** `72`
- **Evidence:** `@if($wireModel) {!! $wireDirective !!} @endif`
- **Why it matters:** Unescaped output can lead to XSS. Prefer {{ }} escaping, or sanitize/strip tags when HTML is required.

### 42. [MEDIUM] Blade outputs unescaped variable (XSS risk)
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/auth/two-factor-setup.blade.php`
- **Line:** `4`
- **Evidence:** `This view uses {!! $qrCodeSvg !!} to render a QR code image. This is safe because:`
- **Why it matters:** Unescaped output can lead to XSS. Prefer {{ }} escaping, or sanitize/strip tags when HTML is required.

### 43. [MEDIUM] Blade outputs unescaped variable (XSS risk)
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/auth/two-factor-setup.blade.php`
- **Line:** `4`
- **Evidence:** `This view uses {!! $qrCodeSvg !!} to render a QR code image. This is safe because:`
- **Why it matters:** Unescaped output can lead to XSS. Prefer {{ }} escaping, or sanitize/strip tags when HTML is required.

### 44. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Http/Controllers/Api/V1/POSController.php`
- **Line:** `218`
- **Evidence:** `(float) $request->input('closing_cash'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 45. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Http/Resources/SaleResource.php`
- **Line:** `30`
- **Evidence:** `'discount_amount' => $this->discount_amount ? (float) $this->discount_amount : null,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 46. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Http/Resources/SupplierResource.php`
- **Line:** `18`
- **Evidence:** `return $value ? (float) $value : null;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 47. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Http/Resources/SupplierResource.php`
- **Line:** `54`
- **Evidence:** `fn () => (float) $this->purchases->sum('total_amount')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 48. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Jobs/ClosePosDayJob.php`
- **Line:** `72`
- **Evidence:** `$gross = (float) $grossString;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 49. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Jobs/ClosePosDayJob.php`
- **Line:** `73`
- **Evidence:** `$paid = (float) $paidString;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 50. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Listeners/ApplyLateFee.php`
- **Line:** `43`
- **Evidence:** `$invoice->amount = (float) $newAmount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 51. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Listeners/UpdateStockOnPurchase.php`
- **Line:** `27`
- **Evidence:** `$itemQty = (float) $item->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 52. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Listeners/UpdateStockOnSale.php`
- **Line:** `49`
- **Evidence:** `$baseQuantity = (float) $item->quantity * (float) $conversionFactor;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 53. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Accounting/JournalEntries/Form.php`
- **Line:** `144`
- **Evidence:** `'amount' => number_format((float) ltrim($difference, '-'), 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 54. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/CurrencyRate/Form.php`
- **Line:** `51`
- **Evidence:** `$this->rate = (float) $rate->rate;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 55. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Installments/Index.php`
- **Line:** `45`
- **Evidence:** `$this->paymentAmount = (float) $payment->remaining_amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 56. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Modules/RentalPeriods/Form.php`
- **Line:** `73`
- **Evidence:** `$this->price_multiplier = (float) $period->price_multiplier;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 57. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `67`
- **Evidence:** `$totalRevenue = (float) $ordersForStats->sum('total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 58. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `68`
- **Evidence:** `$totalDiscount = (float) $ordersForStats->sum('discount_total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 59. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `69`
- **Evidence:** `$totalShipping = (float) $ordersForStats->sum('shipping_total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 60. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `70`
- **Evidence:** `$totalTax = (float) $ordersForStats->sum('tax_total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 61. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `84`
- **Evidence:** `$sources[$source]['revenue'] += (float) $order->total;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 62. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `121`
- **Evidence:** `return (float) $s['revenue'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 63. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `139`
- **Evidence:** `$dayValues[] = (float) $items->sum('total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 64. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Hrm/Employees/Form.php`
- **Line:** `178`
- **Evidence:** `$employee->salary = (float) $this->form['salary'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 65. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Hrm/Payroll/Run.php`
- **Line:** `95`
- **Evidence:** `$model->basic = (float) $basic;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 66. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Hrm/Payroll/Run.php`
- **Line:** `96`
- **Evidence:** `$model->allowances = (float) $allowances;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 67. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Hrm/Payroll/Run.php`
- **Line:** `97`
- **Evidence:** `$model->deductions = (float) $deductions;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 68. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Hrm/Payroll/Run.php`
- **Line:** `98`
- **Evidence:** `$model->net = (float) $net;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 69. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Hrm/Reports/Dashboard.php`
- **Line:** `126`
- **Evidence:** `'total_net' => (float) $group->sum('net'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 70. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Income/Form.php`
- **Line:** `102`
- **Evidence:** `$this->amount = (float) $income->amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 71. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Inventory/ProductHistory.php`
- **Line:** `114`
- **Evidence:** `$this->currentStock = (float) $currentStock;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 72. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Inventory/Products/Form.php`
- **Line:** `143`
- **Evidence:** `$this->form['max_stock'] = $p->max_stock ? (float) $p->max_stock : null;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 73. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Inventory/Products/Form.php`
- **Line:** `145`
- **Evidence:** `$this->form['lead_time_days'] = $p->lead_time_days ? (float) $p->lead_time_days : null;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 74. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Inventory/Services/Form.php`
- **Line:** `136`
- **Evidence:** `$this->defaultPrice = (float) $product->default_price;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 75. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Inventory/Services/Form.php`
- **Line:** `234`
- **Evidence:** `$this->defaultPrice = (float) bcround($calculated, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 76. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Purchases/Form.php`
- **Line:** `171`
- **Evidence:** `'qty' => (float) $item->qty,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 77. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Purchases/Form.php`
- **Line:** `172`
- **Evidence:** `'unit_cost' => (float) $item->unit_cost,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 78. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `102`
- **Evidence:** `'max_qty' => (float) $item->qty,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 79. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `104`
- **Evidence:** `'cost' => (float) $item->unit_cost,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 80. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `160`
- **Evidence:** `$qty = min((float) $it['qty'], (float) $pi->qty);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 81. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `162`
- **Evidence:** `$unitCost = (float) $pi->unit_cost;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 82. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Contracts/Form.php`
- **Line:** `177`
- **Evidence:** `$this->form['rent'] = (float) $model->rent;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 83. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Contracts/Form.php`
- **Line:** `178`
- **Evidence:** `$this->form['deposit'] = (float) $model->deposit;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 84. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Contracts/Form.php`
- **Line:** `339`
- **Evidence:** `$contract->rent = (float) $this->form['rent'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 85. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Contracts/Form.php`
- **Line:** `340`
- **Evidence:** `$contract->deposit = (float) $this->form['deposit'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 86. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Reports/Dashboard.php`
- **Line:** `69`
- **Evidence:** `$occupancyRate = $total > 0 ? (float) bcdiv(bcmul((string) $occupied, '100', 4), (string) $total, 1) : 0;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 87. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Units/Form.php`
- **Line:** `96`
- **Evidence:** `$this->form['rent'] = (float) $unitModel->rent;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 88. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Units/Form.php`
- **Line:** `97`
- **Evidence:** `$this->form['deposit'] = (float) $unitModel->deposit;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 89. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Units/Form.php`
- **Line:** `155`
- **Evidence:** `$unit->rent = (float) $this->form['rent'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 90. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Livewire/Rental/Units/Form.php`
- **Line:** `156`
- **Evidence:** `$unit->deposit = (float) $this->form['deposit'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 91. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/BillOfMaterial.php`
- **Line:** `123`
- **Evidence:** `$itemQuantity = (float) $item->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 92. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/BomItem.php`
- **Line:** `69`
- **Evidence:** `$baseQuantity = (float) $this->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 93. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/BomOperation.php`
- **Line:** `58`
- **Evidence:** `return (float) $this->duration_minutes + (float) $this->setup_time_minutes;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 94. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/BomOperation.php`
- **Line:** `67`
- **Evidence:** `$workCenterCost = $timeHours * (float) $this->workCenter->cost_per_hour;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 95. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/BomOperation.php`
- **Line:** `68`
- **Evidence:** `$laborCost = (float) $this->labor_cost;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 96. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/GRNItem.php`
- **Line:** `93`
- **Evidence:** `return (abs($expectedQty - (float) $acceptedQty) / $expectedQty) * 100;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 97. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/GoodsReceivedNote.php`
- **Line:** `162`
- **Evidence:** `return (float) $this->items->sum('received_quantity');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 98. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/GoodsReceivedNote.php`
- **Line:** `167`
- **Evidence:** `return (float) $this->items->sum('accepted_quantity');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 99. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/GoodsReceivedNote.php`
- **Line:** `172`
- **Evidence:** `return (float) $this->items->sum('rejected_quantity');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 100. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/InstallmentPayment.php`
- **Line:** `45`
- **Evidence:** `return max(0, (float) $this->amount_due - (float) ($this->amount_paid ?? 0));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 101. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/InstallmentPlan.php`
- **Line:** `71`
- **Evidence:** `return (float) $this->payments()->sum('amount_paid');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 102. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/InstallmentPlan.php`
- **Line:** `76`
- **Evidence:** `return max(0, (float) $this->total_amount - (float) $this->down_payment - $this->paid_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 103. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/ModuleSetting.php`
- **Line:** `53`
- **Evidence:** `'float', 'decimal' => (float) $this->setting_value,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 104. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/ProductFieldValue.php`
- **Line:** `39`
- **Evidence:** `'decimal' => (float) $this->value,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 105. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/ProductionOrder.php`
- **Line:** `189`
- **Evidence:** `return (float) $this->planned_quantity - (float) $this->produced_quantity - (float) $this->rejected_quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 106. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Project.php`
- **Line:** `195`
- **Evidence:** `return (float) $this->budget;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 107. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/ProjectTask.php`
- **Line:** `150`
- **Evidence:** `return (float) $this->timeLogs()->sum('hours');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 108. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/ProjectTask.php`
- **Line:** `156`
- **Evidence:** `$estimated = (float) $this->estimated_hours;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 109. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Purchase.php`
- **Line:** `189`
- **Evidence:** `return (float) $this->paid_amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 110. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Purchase.php`
- **Line:** `194`
- **Evidence:** `return max(0, (float) $this->total_amount - (float) $this->paid_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 111. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Purchase.php`
- **Line:** `229`
- **Evidence:** `$paidAmount = (float) $this->paid_amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 112. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Purchase.php`
- **Line:** `230`
- **Evidence:** `$totalAmount = (float) $this->total_amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 113. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Sale.php`
- **Line:** `205`
- **Evidence:** `return (float) $this->payments()`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 114. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Sale.php`
- **Line:** `212`
- **Evidence:** `return max(0, (float) $this->total_amount - $this->total_paid);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 115. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Sale.php`
- **Line:** `235`
- **Evidence:** `$totalAmount = (float) $this->total_amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 116. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/StockTransferItem.php`
- **Line:** `74`
- **Evidence:** `return (float) bcsub((string)$this->qty_shipped, (string)$this->qty_received, 3);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 117. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/SystemSetting.php`
- **Line:** `103`
- **Evidence:** `'float', 'decimal' => is_numeric($value) ? (float) $value : $default,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 118. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Traits/CommonQueryScopes.php`
- **Line:** `192`
- **Evidence:** `return number_format((float) $value, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 119. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/Transfer.php`
- **Line:** `220`
- **Evidence:** `return (float) $this->items()`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 120. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/UnitOfMeasure.php`
- **Line:** `87`
- **Evidence:** `$baseValue = $value * (float) $this->conversion_factor;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 121. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Models/UnitOfMeasure.php`
- **Line:** `89`
- **Evidence:** `$targetFactor = (float) $targetUnit->conversion_factor;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 122. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/FinancialTransactionObserver.php`
- **Line:** `142`
- **Evidence:** `$customer->addBalance((float) $model->total_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 123. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/FinancialTransactionObserver.php`
- **Line:** `144`
- **Evidence:** `$customer->subtractBalance((float) $model->total_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 124. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/FinancialTransactionObserver.php`
- **Line:** `151`
- **Evidence:** `$supplier->addBalance((float) $model->total_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 125. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/FinancialTransactionObserver.php`
- **Line:** `153`
- **Evidence:** `$supplier->subtractBalance((float) $model->total_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 126. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/ProductObserver.php`
- **Line:** `49`
- **Evidence:** `$product->default_price = round((float) $product->default_price, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 127. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/ProductObserver.php`
- **Line:** `52`
- **Evidence:** `$product->standard_cost = round((float) $product->standard_cost, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 128. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Observers/ProductObserver.php`
- **Line:** `55`
- **Evidence:** `$product->cost = round((float) $product->cost, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 129. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Rules/ValidDiscount.php`
- **Line:** `35`
- **Evidence:** `$num = (float) $value;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 130. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Analytics/SalesForecastingService.php`
- **Line:** `126`
- **Evidence:** `'revenue' => (float) $row->revenue,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 131. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Analytics/SalesForecastingService.php`
- **Line:** `127`
- **Evidence:** `'avg_order_value' => (float) $row->avg_order_value,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 132. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/HelpdeskService.php`
- **Line:** `294`
- **Evidence:** `return (float) bcdiv((string) $totalMinutes, (string) $tickets->count(), 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 133. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/LeaveManagementService.php`
- **Line:** `621`
- **Evidence:** `$daysToDeduct = (float) $leaveRequest->days_count;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 134. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/LoyaltyService.php`
- **Line:** `37`
- **Evidence:** `$points = (int) floor((float) $pointsDecimal);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 135. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/LoyaltyService.php`
- **Line:** `208`
- **Evidence:** `return (float) bcmul((string) $points, (string) $settings->redemption_rate, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 136. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/ProductService.php`
- **Line:** `113`
- **Evidence:** `$product->default_price = (float) $price;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 137. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/ProductService.php`
- **Line:** `118`
- **Evidence:** `$product->cost = (float) $cost;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 138. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `103`
- **Evidence:** `$qtyReturned = (float) $itemData['qty_returned'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 139. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `104`
- **Evidence:** `$purchaseQty = (float) $purchaseItem->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 140. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `313`
- **Evidence:** `if ((float) $item->qty_returned <= 0) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 141. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `322`
- **Evidence:** `'qty' => (float) $item->qty_returned,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 142. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `328`
- **Evidence:** `'unit_cost' => (float) $item->unit_cost,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 143. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `77`
- **Evidence:** `$qty = (float) $it['qty'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 144. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `105`
- **Evidence:** `$lineTax = (float) bcmul($taxableAmount, bcdiv((string) $taxPercent, '100', 6), 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 145. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `129`
- **Evidence:** `'line_total' => (float) $lineTotal,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 146. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `138`
- **Evidence:** `$p->subtotal = (float) bcround($subtotal, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 147. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `139`
- **Evidence:** `$p->tax_amount = (float) bcround($totalTax, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 148. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `140`
- **Evidence:** `$p->discount_amount = (float) bcround($totalDiscount, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 149. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `142`
- **Evidence:** `$p->total_amount = (float) bcround(`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 150. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `221`
- **Evidence:** `$remainingDue = max(0, (float) $p->total_amount - (float) $p->paid_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 151. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `254`
- **Evidence:** `$p->paid_amount = (float) $newPaidAmount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 152. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `257`
- **Evidence:** `if ((float) $p->paid_amount >= (float) $p->total_amount) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 153. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `259`
- **Evidence:** `} elseif ((float) $p->paid_amount > 0) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 154. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `284`
- **Evidence:** `if ($p->payment_status === 'paid' || (float) $p->paid_amount > 0) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 155. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `284`
- **Evidence:** `if ($p->payment_status === 'paid' || (float) $p->paid_amount > 0) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 156. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `133`
- **Evidence:** `'rent' => (float) $payload['rent'],`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 157. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `154`
- **Evidence:** `$c->rent = (float) $payload['rent'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 158. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `243`
- **Evidence:** `$i->paid_total = (float) $newPaidTotal;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 159. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `271`
- **Evidence:** `$i->amount = (float) $newAmount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 160. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `389`
- **Evidence:** `? (float) bcmul(bcdiv((string) $occupiedUnits, (string) $totalUnits, 4), '100', 2)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 161. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `520`
- **Evidence:** `? (float) bcmul(bcdiv((string) $collectedAmount, (string) $totalAmount, 4), '100', 2)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 162. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/ReportService.php`
- **Line:** `94`
- **Evidence:** `return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'gross' => (float) $r->gross])->all();`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 163. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `39`
- **Evidence:** `'current_cash' => (float) $currentCash,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 164. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `41`
- **Evidence:** `'total_expected_inflows' => (float) $expectedInflows->sum('amount'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 165. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `42`
- **Evidence:** `'total_expected_outflows' => (float) $expectedOutflows->sum('amount'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 166. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `43`
- **Evidence:** `'ending_cash_forecast' => (float) $dailyForecast->last()['ending_balance'],`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 167. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `138`
- **Evidence:** `'inflows' => (float) $dailyInflowsStr,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 168. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `139`
- **Evidence:** `'outflows' => (float) $dailyOutflowsStr,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 169. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `140`
- **Evidence:** `'net_flow' => (float) $netFlow,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 170. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `141`
- **Evidence:** `'ending_balance' => (float) $runningBalance,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 171. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `152`
- **Evidence:** `'total_revenue' => (float) $totalRevenue,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 172. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `154`
- **Evidence:** `? (float) bcdiv($totalRevenue, (string) count($customers), 2)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 173. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/CustomerSegmentationService.php`
- **Line:** `192`
- **Evidence:** `'revenue_at_risk' => (float) $at_risk->sum('lifetime_revenue'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 174. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `77`
- **Evidence:** `'stock_value' => (float) $stockValue,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 175. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `80`
- **Evidence:** `'daily_sales_rate' => (float) $dailyRate,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 176. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `88`
- **Evidence:** `'total_stock_value' => (float) $products->sum(function ($product) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 177. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `121`
- **Evidence:** `'potential_loss' => (float) $potentialLoss,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 178. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Reports/SlowMovingStockService.php`
- **Line:** `127`
- **Evidence:** `'total_potential_loss' => (float) $products->sum(function ($product) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 179. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `104`
- **Evidence:** `$availableToReturn = max(0, (float) $si->quantity - $alreadyReturned);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 180. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `104`
- **Evidence:** `$availableToReturn = max(0, (float) $si->quantity - $alreadyReturned);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 181. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `173`
- **Evidence:** `'total_amount' => (float) $refund,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 182. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `234`
- **Evidence:** `'amount' => (float) $refund,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 183. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `234`
- **Evidence:** `'amount' => (float) $refund,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 184. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `297`
- **Evidence:** `$returned[$itemId] = abs((float) $returnedQty);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 185. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `317`
- **Evidence:** `$currentReturnMap[$saleItemId] = ($currentReturnMap[$saleItemId] ?? 0) + (float) $item['qty'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 186. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `317`
- **Evidence:** `$currentReturnMap[$saleItemId] = ($currentReturnMap[$saleItemId] ?? 0) + (float) $item['qty'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 187. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `322`
- **Evidence:** `$soldQty = (float) $saleItem->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 188. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `322`
- **Evidence:** `$soldQty = (float) $saleItem->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 189. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SalesReturnService.php`
- **Line:** `218`
- **Evidence:** `$remainingRefundable = (float) $return->refund_amount - (float) $alreadyRefunded;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 190. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/SmartNotificationsService.php`
- **Line:** `202`
- **Evidence:** `$dueTotal = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 191. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `102`
- **Evidence:** `return (float) $product->reorder_qty;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 192. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `114`
- **Evidence:** `return (float) $product->minimum_order_quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 193. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `119`
- **Evidence:** `return (float) $product->maximum_order_quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 194. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `123`
- **Evidence:** `return (float) bcround((string) $optimalQty, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 195. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `127`
- **Evidence:** `return $product->reorder_point ? ((float) $product->reorder_point * 2) : 50;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 196. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `152`
- **Evidence:** `return $totalSold ? ((float) $totalSold / $days) : 0;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 197. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `187`
- **Evidence:** `'sales_velocity' => (float) bcround((string) $salesVelocity, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 198. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockReorderService.php`
- **Line:** `308`
- **Evidence:** `'total_estimated_cost' => (float) bcround((string) $totalEstimatedCost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 199. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockService.php`
- **Line:** `61`
- **Evidence:** `return (float) $query->selectRaw('COALESCE(SUM(quantity), 0) as stock')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 200. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockService.php`
- **Line:** `167`
- **Evidence:** `return (float) DB::table('stock_movements')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 201. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockService.php`
- **Line:** `322`
- **Evidence:** `$stockBefore = (float) DB::table('stock_movements')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 202. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockService.php`
- **Line:** `381`
- **Evidence:** `$totalStock = (float) StockMovement::where('product_id', $productId)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 203. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/StockTransferService.php`
- **Line:** `236`
- **Evidence:** `$itemQuantities[(int) $itemId] = (float) $itemData['qty_shipped'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 204. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `158`
- **Evidence:** `$qty = (float) Arr::get($item, 'qty', 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 205. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `164`
- **Evidence:** `$price = (float) Arr::get($item, 'price', 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 206. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `165`
- **Evidence:** `$discount = (float) Arr::get($item, 'discount', 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 207. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `35`
- **Evidence:** `return (float) bcround($taxAmount, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 208. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `51`
- **Evidence:** `$rate = (float) $tax->rate;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 209. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `51`
- **Evidence:** `$rate = (float) $tax->rate;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 210. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `63`
- **Evidence:** `return (float) bcdiv($taxPortion, '1', 4);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 211. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `69`
- **Evidence:** `return (float) bcdiv($taxAmount, '1', 4);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 212. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `82`
- **Evidence:** `return (float) bcdiv((string) $base, '1', 4);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 213. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `98`
- **Evidence:** `return (float) bcdiv($total, '1', 4);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 214. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `98`
- **Evidence:** `return (float) bcdiv($total, '1', 4);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 215. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `102`
- **Evidence:** `defaultValue: (float) bcdiv((string) $base, '1', 4)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 216. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `142`
- **Evidence:** `'total_with_tax' => (float) bcadd((string) $subtotal, (string) $taxAmount, 4),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 217. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `151`
- **Evidence:** `'total_tax' => (float) bcround($totalTax, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 218. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UIHelperService.php`
- **Line:** `190`
- **Evidence:** `$value = (float) bcdiv((string) $value, '1024', $precision + 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 219. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `61`
- **Evidence:** `$suggestedQty = max((float) $eoq, (float) $product->minimum_order_quantity ?? 1);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 220. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `69`
- **Evidence:** `$urgency = $this->determineReorderUrgency((float) $currentStock, (float) $reorderPoint, (float) $product->min_stock ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 221. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `76`
- **Evidence:** `'reorder_point' => (float) $reorderPoint,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 222. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `78`
- **Evidence:** `'sales_velocity' => (float) $salesVelocity,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 223. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `79`
- **Evidence:** `'days_of_stock_remaining' => (float) $daysOfStock,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 224. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `83`
- **Evidence:** `'recommendation' => $this->generateReorderRecommendation($urgency, (float) $daysOfStock, $suggestedQty),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 225. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `121`
- **Evidence:** `'price' => (float) $price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 226. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `121`
- **Evidence:** `'price' => (float) $price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 227. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `123`
- **Evidence:** `'profit_per_unit' => (float) bcsub($price, (string) $cost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 228. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `123`
- **Evidence:** `'profit_per_unit' => (float) bcsub($price, (string) $cost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 229. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `141`
- **Evidence:** `'current_margin' => (float) $currentMargin.'%',`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 230. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `142`
- **Evidence:** `'suggested_price' => (float) $suggestedPrice,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 231. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `144`
- **Evidence:** `'profit_per_unit' => (float) bcsub($suggestedPrice, (string) $cost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 232. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `144`
- **Evidence:** `'profit_per_unit' => (float) bcsub($suggestedPrice, (string) $cost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 233. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `147`
- **Evidence:** `'recommendation' => $this->generatePricingRecommendation((float) $suggestedPrice, $currentPrice, (float) $currentMargin),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 234. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `197`
- **Evidence:** `'price' => (float) $item->default_price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 235. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `197`
- **Evidence:** `'price' => (float) $item->default_price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 236. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `200`
- **Evidence:** `'avg_quantity' => (float) $item->avg_quantity,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 237. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `201`
- **Evidence:** `'individual_total' => (float) $totalPrice,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 238. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `202`
- **Evidence:** `'suggested_bundle_price' => (float) $suggestedBundlePrice,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 239. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `203`
- **Evidence:** `'customer_savings' => (float) $savings,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 240. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `245`
- **Evidence:** `'price' => (float) $product->default_price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 241. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `245`
- **Evidence:** `'price' => (float) $product->default_price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 242. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `246`
- **Evidence:** `'margin' => (float) $margin,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 243. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `246`
- **Evidence:** `'margin' => (float) $margin,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 244. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `277`
- **Evidence:** `return (float) bcdiv((string) ($totalSold ?? 0), (string) $days, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 245. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `415`
- **Evidence:** `? (float) bcmul(bcdiv(bcsub((string) $product->default_price, (string) $product->standard_cost, 2), (string) $product->default_price, 4), '100', 2)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 246. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `106`
- **Evidence:** `return "• {$item->product->name} x{$item->qty} = ".number_format((float) $item->line_total, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 247. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `117`
- **Evidence:** `'subtotal' => number_format((float) $sale->sub_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 248. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `118`
- **Evidence:** `'tax' => number_format((float) $sale->tax_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 249. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `119`
- **Evidence:** `'discount' => number_format((float) $sale->discount_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 250. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `120`
- **Evidence:** `'total' => number_format((float) $sale->grand_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 251. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WoodService.php`
- **Line:** `39`
- **Evidence:** `'efficiency' => $this->efficiency((float) $payload['input_qty'], (float) $payload['output_qty']),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 252. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WoodService.php`
- **Line:** `56`
- **Evidence:** `$eff = $this->efficiency((float) $row->input_qty, (float) $row->output_qty);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 253. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/Services/WoodService.php`
- **Line:** `105`
- **Evidence:** `return (float) bcround($percentage, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 254. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/ValueObjects/Money.php`
- **Line:** `73`
- **Evidence:** `return number_format((float) $this->amount, $decimals).' '.$this->currency;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 255. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `app/ValueObjects/Money.php`
- **Line:** `81`
- **Evidence:** `return (float) $this->amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 256. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/admin/dashboard.blade.php`
- **Line:** `58`
- **Evidence:** `'data' => $salesSeries->pluck('total')->map(fn ($v) => (float) $v)->toArray(),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 257. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/hrm/employees/index.blade.php`
- **Line:** `198`
- **Evidence:** `{{ number_format((float) $employee->salary, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 258. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `86`
- **Evidence:** `{{ number_format((float) $row->basic, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 259. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `89`
- **Evidence:** `{{ number_format((float) $row->allowances, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 260. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `92`
- **Evidence:** `{{ number_format((float) $row->deductions, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 261. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `95`
- **Evidence:** `{{ number_format((float) $row->net, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 262. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/manufacturing/bills-of-materials/index.blade.php`
- **Line:** `146`
- **Evidence:** `<td>{{ number_format((float)$bom->quantity, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 263. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/manufacturing/production-orders/index.blade.php`
- **Line:** `151`
- **Evidence:** `<td>{{ number_format((float)$order->quantity_planned, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 264. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/manufacturing/production-orders/index.blade.php`
- **Line:** `152`
- **Evidence:** `<td>{{ number_format((float)$order->quantity_produced, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 265. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/manufacturing/work-centers/index.blade.php`
- **Line:** `147`
- **Evidence:** `<td>{{ number_format((float)$workCenter->capacity_per_hour, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 266. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/manufacturing/work-centers/index.blade.php`
- **Line:** `148`
- **Evidence:** `<td>{{ number_format((float)$workCenter->cost_per_hour, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 267. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `62`
- **Evidence:** `<td class="font-mono text-orange-600">{{ number_format((float)$return->total, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 268. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `111`
- **Evidence:** `{{ number_format((float)$purchase->grand_total, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 269. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `120`
- **Evidence:** `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedPurchase->grand_total, 2) }}</p>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 270. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/rental/contracts/index.blade.php`
- **Line:** `96`
- **Evidence:** `{{ number_format((float) $row->rent, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 271. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/rental/units/index.blade.php`
- **Line:** `84`
- **Evidence:** `{{ number_format((float) $unit->rent, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 272. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/rental/units/index.blade.php`
- **Line:** `87`
- **Evidence:** `{{ number_format((float) $unit->deposit, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 273. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `62`
- **Evidence:** `<td class="font-mono text-red-600">{{ number_format((float)$return->total, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 274. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `111`
- **Evidence:** `{{ number_format((float)$sale->grand_total, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 275. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `120`
- **Evidence:** `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedSale->grand_total, 2) }}</p>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 276. [MEDIUM] Float/double cast in finance/qty context (rounding drift)
- **Rule ID:** `FLOAT_CAST_FINANCE`
- **File:** `resources/views/livewire/shared/dynamic-table.blade.php`
- **Line:** `226`
- **Evidence:** `<span class="font-medium">{{ $currency }}{{ number_format((float)$value, 2) }}</span>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 277. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Livewire/Accounting/JournalEntries/Form.php`
- **Line:** `144`
- **Evidence:** `'amount' => number_format((float) ltrim($difference, '-'), 2),`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 278. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Models/Traits/CommonQueryScopes.php`
- **Line:** `192`
- **Evidence:** `return number_format((float) $value, 2);`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 279. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `106`
- **Evidence:** `return "• {$item->product->name} x{$item->qty} = ".number_format((float) $item->line_total, 2);`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 280. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `117`
- **Evidence:** `'subtotal' => number_format((float) $sale->sub_total, 2),`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 281. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `118`
- **Evidence:** `'tax' => number_format((float) $sale->tax_total, 2),`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 282. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `119`
- **Evidence:** `'discount' => number_format((float) $sale->discount_total, 2),`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 283. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `120`
- **Evidence:** `'total' => number_format((float) $sale->grand_total, 2),`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 284. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `app/ValueObjects/Money.php`
- **Line:** `73`
- **Evidence:** `return number_format((float) $this->amount, $decimals).' '.$this->currency;`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 285. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/hrm/employees/index.blade.php`
- **Line:** `198`
- **Evidence:** `{{ number_format((float) $employee->salary, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 286. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `86`
- **Evidence:** `{{ number_format((float) $row->basic, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 287. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `89`
- **Evidence:** `{{ number_format((float) $row->allowances, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 288. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `92`
- **Evidence:** `{{ number_format((float) $row->deductions, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 289. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `95`
- **Evidence:** `{{ number_format((float) $row->net, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 290. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/manufacturing/bills-of-materials/index.blade.php`
- **Line:** `146`
- **Evidence:** `<td>{{ number_format((float)$bom->quantity, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 291. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/manufacturing/production-orders/index.blade.php`
- **Line:** `151`
- **Evidence:** `<td>{{ number_format((float)$order->quantity_planned, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 292. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/manufacturing/production-orders/index.blade.php`
- **Line:** `152`
- **Evidence:** `<td>{{ number_format((float)$order->quantity_produced, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 293. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/manufacturing/work-centers/index.blade.php`
- **Line:** `147`
- **Evidence:** `<td>{{ number_format((float)$workCenter->capacity_per_hour, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 294. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/manufacturing/work-centers/index.blade.php`
- **Line:** `148`
- **Evidence:** `<td>{{ number_format((float)$workCenter->cost_per_hour, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 295. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `62`
- **Evidence:** `<td class="font-mono text-orange-600">{{ number_format((float)$return->total, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 296. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `111`
- **Evidence:** `{{ number_format((float)$purchase->grand_total, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 297. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `120`
- **Evidence:** `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedPurchase->grand_total, 2) }}</p>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 298. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/rental/contracts/index.blade.php`
- **Line:** `96`
- **Evidence:** `{{ number_format((float) $row->rent, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 299. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/rental/units/index.blade.php`
- **Line:** `84`
- **Evidence:** `{{ number_format((float) $unit->rent, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 300. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/rental/units/index.blade.php`
- **Line:** `87`
- **Evidence:** `{{ number_format((float) $unit->deposit, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 301. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `62`
- **Evidence:** `<td class="font-mono text-red-600">{{ number_format((float)$return->total, 2) }}</td>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 302. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `111`
- **Evidence:** `{{ number_format((float)$sale->grand_total, 2) }}`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 303. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `120`
- **Evidence:** `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedSale->grand_total, 2) }}</p>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

### 304. [MEDIUM] number_format((float)...) used for money (rounding drift)
- **Rule ID:** `NUMBER_FORMAT_FLOAT`
- **File:** `resources/views/livewire/shared/dynamic-table.blade.php`
- **Line:** `226`
- **Evidence:** `<span class="font-medium">{{ $currency }}{{ number_format((float)$value, 2) }}</span>`
- **Why it matters:** Formatting should not require float casts; store as DECIMAL and format via string-safe conversions.

---

## New bugs

### 1. [HIGH] Branch fallback via Branch::first() can corrupt multi-branch ERP integrity
- **Rule ID:** `BRANCH_FIRST_FALLBACK`
- **File:** `app/Livewire/Manufacturing/BillsOfMaterials/Form.php`
- **Line:** `84`
- **Evidence:** `// V32-HIGH-A01 FIX: Don't fallback to Branch::first() as it may assign records to wrong branch`
- **Why it matters:** In multi-branch ERP, defaulting to the first branch may write/read data under the wrong branch, causing cross-branch data corruption.

### 2. [HIGH] Branch fallback via Branch::first() can corrupt multi-branch ERP integrity
- **Rule ID:** `BRANCH_FIRST_FALLBACK`
- **File:** `app/Livewire/Manufacturing/ProductionOrders/Form.php`
- **Line:** `89`
- **Evidence:** `// V32-HIGH-A02 FIX: Don't fallback to Branch::first() as it may assign records to wrong branch`
- **Why it matters:** In multi-branch ERP, defaulting to the first branch may write/read data under the wrong branch, causing cross-branch data corruption.

### 3. [HIGH] Branch fallback via Branch::first() can corrupt multi-branch ERP integrity
- **Rule ID:** `BRANCH_FIRST_FALLBACK`
- **File:** `app/Livewire/Manufacturing/WorkCenters/Form.php`
- **Line:** `97`
- **Evidence:** `// V32-HIGH-A03 FIX: Don't fallback to Branch::first() - use user's assigned branch`
- **Why it matters:** In multi-branch ERP, defaulting to the first branch may write/read data under the wrong branch, causing cross-branch data corruption.

### 4. [HIGH] Branch fallback via Branch::first() can corrupt multi-branch ERP integrity
- **Rule ID:** `BRANCH_FIRST_FALLBACK`
- **File:** `app/Livewire/Manufacturing/WorkCenters/Form.php`
- **Line:** `131`
- **Evidence:** `// V32-HIGH-A04 FIX: Don't fallback to Branch::first() as it may assign records to wrong branch`
- **Why it matters:** In multi-branch ERP, defaulting to the first branch may write/read data under the wrong branch, causing cross-branch data corruption.

### 5. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/form/input.blade.php`
- **Line:** `4`
- **Evidence:** `This component uses {!! !!} for the $icon prop. This is safe because:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 6. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/form/input.blade.php`
- **Line:** `10`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 7. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/form/input.blade.php`
- **Line:** `76`
- **Evidence:** `{!! sanitize_svg_icon($icon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 8. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/icon.blade.php`
- **Line:** `44`
- **Evidence:** `{!! sanitize_svg_icon($iconPath) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 9. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/button.blade.php`
- **Line:** `5`
- **Evidence:** `This component uses {!! !!} to render SVG icons. This is safe because:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 10. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/button.blade.php`
- **Line:** `11`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 11. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/button.blade.php`
- **Line:** `56`
- **Evidence:** `{!! sanitize_svg_icon($icon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 12. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/card.blade.php`
- **Line:** `3`
- **Evidence:** `SECURITY NOTE: This component uses {!! !!} for two types of content:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 13. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/card.blade.php`
- **Line:** `27`
- **Evidence:** `{!! sanitize_svg_icon($icon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 14. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/empty-state.blade.php`
- **Line:** `5`
- **Evidence:** `This component uses {!! !!} for SVG icons. This is safe because:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 15. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/empty-state.blade.php`
- **Line:** `10`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 16. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/empty-state.blade.php`
- **Line:** `43`
- **Evidence:** `{!! sanitize_svg_icon($displayIcon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 17. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/form/input.blade.php`
- **Line:** `3`
- **Evidence:** `SECURITY NOTE: This component uses {!! !!} for:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 18. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/form/input.blade.php`
- **Line:** `60`
- **Evidence:** `{!! sanitize_svg_icon($icon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 19. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/page-header.blade.php`
- **Line:** `5`
- **Evidence:** `This component uses {!! !!} for the $icon prop. This is safe because:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 20. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/page-header.blade.php`
- **Line:** `11`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 21. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/components/ui/page-header.blade.php`
- **Line:** `57`
- **Evidence:** `{!! sanitize_svg_icon($icon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 22. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/auth/two-factor-setup.blade.php`
- **Line:** `9`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 23. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/shared/dynamic-form.blade.php`
- **Line:** `5`
- **Evidence:** `This component uses {!! !!} for the $icon field from form schema. This is safe because:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 24. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/shared/dynamic-form.blade.php`
- **Line:** `11`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 25. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/shared/dynamic-form.blade.php`
- **Line:** `52`
- **Evidence:** `<span class="text-slate-400">{!! sanitize_svg_icon($icon) !!}</span>`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 26. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/shared/dynamic-table.blade.php`
- **Line:** `5`
- **Evidence:** `This component uses {!! !!} for action icons. This is safe because:`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 27. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/shared/dynamic-table.blade.php`
- **Line:** `11`
- **Evidence:** `Static analysis tools may flag {!! !!} as XSS risks. This is a false positive`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 28. [MEDIUM] Unescaped Blade output ({!! !!}) may cause XSS if input is not trusted
- **Rule ID:** `BLADE_UNESCAPED`
- **File:** `resources/views/livewire/shared/dynamic-table.blade.php`
- **Line:** `273`
- **Evidence:** `{!! sanitize_svg_icon($actionIcon) !!}`
- **Why it matters:** Prefer escaped output ({{ }}) or sanitize/whitelist HTML before rendering unescaped content.

### 29. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Http/Controllers/Api/V1/InventoryController.php`
- **Line:** `346`
- **Evidence:** `return (float) ($query->selectRaw('SUM(quantity) as balance')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 30. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Http/Controllers/Api/V1/OrdersController.php`
- **Line:** `227`
- **Evidence:** `$orderDiscount = max(0, (float) ($validated['discount'] ?? 0));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 31. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Http/Controllers/Api/V1/OrdersController.php`
- **Line:** `229`
- **Evidence:** `$tax = max(0, (float) ($validated['tax'] ?? 0));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 32. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Http/Middleware/EnforceDiscountLimit.php`
- **Line:** `43`
- **Evidence:** `$disc = (float) ($row['discount'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 33. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Http/Resources/CustomerResource.php`
- **Line:** `38`
- **Evidence:** `(float) ($this->balance ?? 0.0)`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 34. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Jobs/ClosePosDayJob.php`
- **Line:** `72`
- **Evidence:** `$gross = (float) $grossString;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 35. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Jobs/ClosePosDayJob.php`
- **Line:** `73`
- **Evidence:** `$paid = (float) $paidString;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 36. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Listeners/ApplyLateFee.php`
- **Line:** `43`
- **Evidence:** `$invoice->amount = (float) $newAmount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 37. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Listeners/UpdateStockOnPurchase.php`
- **Line:** `27`
- **Evidence:** `$itemQty = (float) $item->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 38. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Listeners/UpdateStockOnSale.php`
- **Line:** `49`
- **Evidence:** `$baseQuantity = (float) $item->quantity * (float) $conversionFactor;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 39. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Accounting/JournalEntries/Form.php`
- **Line:** `144`
- **Evidence:** `'amount' => number_format((float) ltrim($difference, '-'), 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 40. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Admin/CurrencyRate/Form.php`
- **Line:** `51`
- **Evidence:** `$this->rate = (float) $rate->rate;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 41. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `67`
- **Evidence:** `$totalRevenue = (float) $ordersForStats->sum('total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 42. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `84`
- **Evidence:** `$sources[$source]['revenue'] += (float) $order->total;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 43. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Admin/Store/OrdersDashboard.php`
- **Line:** `139`
- **Evidence:** `$dayValues[] = (float) $items->sum('total');`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 44. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Hrm/Payroll/Run.php`
- **Line:** `98`
- **Evidence:** `$model->net = (float) $net;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 45. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Hrm/Reports/Dashboard.php`
- **Line:** `126`
- **Evidence:** `'total_net' => (float) $group->sum('net'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 46. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Income/Form.php`
- **Line:** `102`
- **Evidence:** `$this->amount = (float) $income->amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 47. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Inventory/Products/Form.php`
- **Line:** `132`
- **Evidence:** `$this->form['price'] = (float) ($p->default_price ?? $p->price ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 48. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Inventory/Products/Form.php`
- **Line:** `133`
- **Evidence:** `$this->form['cost'] = (float) ($p->standard_cost ?? $p->cost ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 49. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Inventory/Services/Form.php`
- **Line:** `137`
- **Evidence:** `$this->cost = (float) ($product->cost ?: $product->standard_cost);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 50. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Form.php`
- **Line:** `171`
- **Evidence:** `'qty' => (float) $item->qty,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 51. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Form.php`
- **Line:** `173`
- **Evidence:** `'discount' => (float) ($item->discount ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 52. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Form.php`
- **Line:** `239`
- **Evidence:** `'unit_cost' => (float) ($product->cost ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 53. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Form.php`
- **Line:** `357`
- **Evidence:** `$discountAmount = max(0, (float) ($item['discount'] ?? 0));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 54. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `102`
- **Evidence:** `'max_qty' => (float) $item->qty,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 55. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `104`
- **Evidence:** `'cost' => (float) $item->unit_cost,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 56. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Purchases/Returns/Index.php`
- **Line:** `160`
- **Evidence:** `$qty = min((float) $it['qty'], (float) $pi->qty);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 57. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Livewire/Rental/Reports/Dashboard.php`
- **Line:** `69`
- **Evidence:** `$occupancyRate = $total > 0 ? (float) bcdiv(bcmul((string) $occupied, '100', 4), (string) $total, 1) : 0;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 58. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Models/BillOfMaterial.php`
- **Line:** `123`
- **Evidence:** `$itemQuantity = (float) $item->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 59. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Models/BomItem.php`
- **Line:** `69`
- **Evidence:** `$baseQuantity = (float) $this->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 60. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Models/JournalEntry.php`
- **Line:** `101`
- **Evidence:** `return (float) ($this->attributes['total_debit'] ?? $this->lines()->sum('debit'));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 61. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Models/JournalEntry.php`
- **Line:** `106`
- **Evidence:** `return (float) ($this->attributes['total_credit'] ?? $this->lines()->sum('credit'));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 62. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Observers/ProductObserver.php`
- **Line:** `55`
- **Evidence:** `$product->cost = round((float) $product->cost, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 63. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Repositories/StockMovementRepository.php`
- **Line:** `141`
- **Evidence:** `$qty = abs((float) ($data['qty'] ?? $data['quantity'] ?? 0));`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 64. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ImportService.php`
- **Line:** `568`
- **Evidence:** `'cost' => (float) ($data['cost'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 65. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ProductService.php`
- **Line:** `113`
- **Evidence:** `$product->default_price = (float) $price;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 66. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ProductService.php`
- **Line:** `118`
- **Evidence:** `$product->cost = (float) $cost;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 67. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `104`
- **Evidence:** `$purchaseQty = (float) $purchaseItem->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 68. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/PurchaseReturnService.php`
- **Line:** `322`
- **Evidence:** `'qty' => (float) $item->qty_returned,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 69. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `77`
- **Evidence:** `$qty = (float) $it['qty'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 70. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `79`
- **Evidence:** `$unitPrice = (float) ($it['unit_price'] ?? $it['price'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 71. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `138`
- **Evidence:** `$p->subtotal = (float) bcround($subtotal, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 72. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/PurchaseService.php`
- **Line:** `284`
- **Evidence:** `if ($p->payment_status === 'paid' || (float) $p->paid_amount > 0) {`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 73. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/RentalService.php`
- **Line:** `271`
- **Evidence:** `$i->amount = (float) $newAmount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 74. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ReportService.php`
- **Line:** `70`
- **Evidence:** `'sales' => ['total' => (float) ($sales->total ?? 0), 'paid' => (float) ($sales->paid ?? 0)],`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 75. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ReportService.php`
- **Line:** `71`
- **Evidence:** `'purchases' => ['total' => (float) ($purchases->total ?? 0), 'paid' => (float) ($purchases->paid ?? 0)],`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 76. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ReportService.php`
- **Line:** `72`
- **Evidence:** `'pnl' => (float) ($sales->total ?? 0) - (float) ($purchases->total ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 77. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ReportService.php`
- **Line:** `94`
- **Evidence:** `return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'gross' => (float) $r->gross])->all();`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 78. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/ReportService.php`
- **Line:** `192`
- **Evidence:** `'total_value' => $items->sum(fn ($p) => ((float) ($p->stock_quantity ?? 0)) * ((float) ($p->cost ?? $p->standard_cost ?? 0))),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 79. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `41`
- **Evidence:** `'total_expected_inflows' => (float) $expectedInflows->sum('amount'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 80. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Reports/CashFlowForecastService.php`
- **Line:** `42`
- **Evidence:** `'total_expected_outflows' => (float) $expectedOutflows->sum('amount'),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 81. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `71`
- **Evidence:** `$requestedQty = (float) ($it['qty'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 82. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `104`
- **Evidence:** `$availableToReturn = max(0, (float) $si->quantity - $alreadyReturned);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 83. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `234`
- **Evidence:** `'amount' => (float) $refund,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 84. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `317`
- **Evidence:** `$currentReturnMap[$saleItemId] = ($currentReturnMap[$saleItemId] ?? 0) + (float) $item['qty'];`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 85. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SaleService.php`
- **Line:** `322`
- **Evidence:** `$soldQty = (float) $saleItem->quantity;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 86. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SalesReturnService.php`
- **Line:** `92`
- **Evidence:** `$qtyToReturn = (float) ($itemData['qty'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 87. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/SalesReturnService.php`
- **Line:** `211`
- **Evidence:** `$requestedAmount = (float) ($validated['amount'] ?? $return->refund_amount);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 88. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/StockService.php`
- **Line:** `61`
- **Evidence:** `return (float) $query->selectRaw('COALESCE(SUM(quantity), 0) as stock')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 89. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/StockService.php`
- **Line:** `131`
- **Evidence:** `return (float) ($query->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 90. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/StockTransferService.php`
- **Line:** `105`
- **Evidence:** `$requestedQty = (float) ($itemData['qty'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 91. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `158`
- **Evidence:** `$qty = (float) Arr::get($item, 'qty', 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 92. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `164`
- **Evidence:** `$price = (float) Arr::get($item, 'price', 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 93. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `165`
- **Evidence:** `$discount = (float) Arr::get($item, 'discount', 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 94. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `255`
- **Evidence:** `$total = (float) ($order->total ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 95. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `256`
- **Evidence:** `$tax = (float) ($order->tax_total ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 96. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreOrderToSaleService.php`
- **Line:** `258`
- **Evidence:** `$discount = (float) ($order->discount_total ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 97. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `320`
- **Evidence:** `'default_price' => (float) ($data['variants'][0]['price'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 98. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `410`
- **Evidence:** `'subtotal' => (float) ($data['subtotal_price'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 99. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `458`
- **Evidence:** `'unit_price' => (float) ($lineItem['price'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 100. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `461`
- **Evidence:** `'line_total' => (float) ($lineItem['quantity'] ?? 1) * (float) ($lineItem['price'] ?? 0) - (float) ($lineItem['total_discount'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 101. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `488`
- **Evidence:** `'default_price' => (float) ($data['price'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 102. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `581`
- **Evidence:** `'subtotal' => (float) ($data['total'] ?? 0) - (float) ($data['total_tax'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 103. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `584`
- **Evidence:** `'total_amount' => (float) ($data['total'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 104. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `632`
- **Evidence:** `'line_total' => (float) ($lineItem['total'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 105. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `756`
- **Evidence:** `'default_price' => (float) ($data['default_price'] ?? $data['price'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 106. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `757`
- **Evidence:** `'cost' => (float) ($data['cost'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 107. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `826`
- **Evidence:** `'subtotal' => (float) ($data['sub_total'] ?? $data['subtotal'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 108. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `827`
- **Evidence:** `'tax_amount' => (float) ($data['tax_total'] ?? $data['tax'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 109. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `828`
- **Evidence:** `'discount_amount' => (float) ($data['discount_total'] ?? $data['discount'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 110. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `829`
- **Evidence:** `'total_amount' => (float) ($data['grand_total'] ?? $data['total'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 111. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `856`
- **Evidence:** `'quantity' => (float) ($lineItem['qty'] ?? $lineItem['quantity'] ?? 1),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 112. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `857`
- **Evidence:** `'unit_price' => (float) ($lineItem['unit_price'] ?? $lineItem['price'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 113. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `858`
- **Evidence:** `'discount_amount' => (float) ($lineItem['discount'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 114. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `859`
- **Evidence:** `'line_total' => (float) ($lineItem['line_total'] ?? $lineItem['total'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 115. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/Store/StoreSyncService.php`
- **Line:** `992`
- **Evidence:** `return (float) ($product->standard_cost ?? $product->cost ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 116. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `23`
- **Evidence:** `return (float) ($tax?->rate ?? 0.0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 117. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `51`
- **Evidence:** `$rate = (float) $tax->rate;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 118. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `98`
- **Evidence:** `return (float) bcdiv($total, '1', 4);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 119. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `130`
- **Evidence:** `$subtotal = (float) ($item['subtotal'] ?? $item['line_total'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 120. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `142`
- **Evidence:** `'total_with_tax' => (float) bcadd((string) $subtotal, (string) $taxAmount, 4),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 121. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/TaxService.php`
- **Line:** `175`
- **Evidence:** `$rate = (float) ($taxRateRules['rate'] ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 122. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `97`
- **Evidence:** `$cost = (float) ($product->standard_cost ?? 0);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 123. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `121`
- **Evidence:** `'price' => (float) $price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 124. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `123`
- **Evidence:** `'profit_per_unit' => (float) bcsub($price, (string) $cost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 125. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `144`
- **Evidence:** `'profit_per_unit' => (float) bcsub($suggestedPrice, (string) $cost, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 126. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `197`
- **Evidence:** `'price' => (float) $item->default_price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 127. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/UX/SmartSuggestionsService.php`
- **Line:** `245`
- **Evidence:** `'price' => (float) $product->default_price,`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 128. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `106`
- **Evidence:** `return "• {$item->product->name} x{$item->qty} = ".number_format((float) $item->line_total, 2);`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 129. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `117`
- **Evidence:** `'subtotal' => number_format((float) $sale->sub_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 130. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `118`
- **Evidence:** `'tax' => number_format((float) $sale->tax_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 131. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `119`
- **Evidence:** `'discount' => number_format((float) $sale->discount_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 132. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/WhatsAppService.php`
- **Line:** `120`
- **Evidence:** `'total' => number_format((float) $sale->grand_total, 2),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 133. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/Services/WoodService.php`
- **Line:** `83`
- **Evidence:** `'qty' => (float) ($payload['qty'] ?? 0),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 134. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/ValueObjects/Money.php`
- **Line:** `73`
- **Evidence:** `return number_format((float) $this->amount, $decimals).' '.$this->currency;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 135. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `app/ValueObjects/Money.php`
- **Line:** `81`
- **Evidence:** `return (float) $this->amount;`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 136. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/admin/dashboard.blade.php`
- **Line:** `58`
- **Evidence:** `'data' => $salesSeries->pluck('total')->map(fn ($v) => (float) $v)->toArray(),`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 137. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/hrm/payroll/index.blade.php`
- **Line:** `95`
- **Evidence:** `{{ number_format((float) $row->net, 2) }}`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 138. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/manufacturing/bills-of-materials/index.blade.php`
- **Line:** `146`
- **Evidence:** `<td>{{ number_format((float)$bom->quantity, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 139. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `62`
- **Evidence:** `<td class="font-mono text-orange-600">{{ number_format((float)$return->total, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 140. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/purchases/returns/index.blade.php`
- **Line:** `120`
- **Evidence:** `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedPurchase->grand_total, 2) }}</p>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 141. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `62`
- **Evidence:** `<td class="font-mono text-red-600">{{ number_format((float)$return->total, 2) }}</td>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.

### 142. [MEDIUM] Float cast/format used for money/qty (rounding drift risk)
- **Rule ID:** `FLOAT_FINANCE`
- **File:** `resources/views/livewire/sales/returns/index.blade.php`
- **Line:** `120`
- **Evidence:** `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedSale->grand_total, 2) }}</p>`
- **Why it matters:** Use decimal-safe arithmetic (BCMath/Money) and DB DECIMAL for money/qty.
