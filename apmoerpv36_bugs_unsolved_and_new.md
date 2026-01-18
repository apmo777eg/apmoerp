# APMO ERP — Bug Delta Report (v36 vs v35 baseline)

This report contains **only**:
1) **Old bugs** from the **v35 baseline report** that are **still present** in v36
2) **New bugs** detected in v36 (not present in v35 baseline report)

## Summary
- Baseline bugs (unique by file+evidence, from v35 report): **687**
- Old bugs fixed in v36: **9**
- Old bugs NOT solved yet: **678**
- New bugs found in v36: **6**

### Breakdown (counts)
**Old (unsolved) by category**: Finance/Precision: 603, Security/SQL: 53, Security/XSS: 12, Perf/Security: 7, Security/Auth: 2, Logic/Files: 1
**New by category**: Security/SQL: 6

---

## A) Old bugs NOT solved yet (still present in v36)

### A.1 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.1`
- Previous baseline ID (older): `A.3`
- File: `app/Console/Commands/CheckDatabaseIntegrity.php`
- Evidence: `DB::statement($fix);`

### A.2 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.2`
- Previous baseline ID (older): `A.2`
- File: `app/Console/Commands/CheckDatabaseIntegrity.php`
- Evidence: `$query->whereRaw($where);`

### A.3 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.3`
- Previous baseline ID (older): `A.1`
- File: `app/Console/Commands/CheckDatabaseIntegrity.php`
- Evidence: `->select($column, DB::raw('COUNT(*) as count'))`

### A.4 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.6`
- Previous baseline ID (older): `A.4`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Evidence: `$data = $query->selectRaw('`

### A.5 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.7`
- Previous baseline ID (older): `A.5`
- File: `app/Http/Controllers/Api/StoreIntegrationController.php`
- Evidence: `->selectRaw($stockExpr.' as current_stock');`

### A.6 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.9`
- Previous baseline ID (older): `A.6`
- File: `app/Http/Controllers/Api/V1/InventoryController.php`
- Evidence: `$query->havingRaw('current_quantity <= products.min_stock');`

### A.7 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.10`
- Previous baseline ID (older): `A.7`
- File: `app/Http/Controllers/Api/V1/InventoryController.php`
- Evidence: `return (float) ($query->selectRaw('SUM(quantity) as balance')`

### A.8 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.11`
- Previous baseline ID (older): `A.8`
- File: `app/Livewire/Admin/Branch/Reports.php`
- Evidence: `'due_amount' => (clone $query)->selectRaw('SUM(total_amount - paid_amount) as due')->value('due') ?? 0,`

### A.9 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.13`
- Previous baseline ID (older): `A.13`
- File: `app/Livewire/Concerns/LoadsDashboardData.php`
- Evidence: `->orderByRaw($stockExpr)`

### A.10 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.14`
- Previous baseline ID (older): `A.11`
- File: `app/Livewire/Concerns/LoadsDashboardData.php`
- Evidence: `->selectRaw("{$stockExpr} as current_quantity")`

### A.11 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.15`
- Previous baseline ID (older): `A.12`
- File: `app/Livewire/Concerns/LoadsDashboardData.php`
- Evidence: `->whereRaw("{$stockExpr} <= products.min_stock")`

### A.12 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.16`
- Previous baseline ID (older): `A.10`
- File: `app/Livewire/Concerns/LoadsDashboardData.php`
- Evidence: `->whereRaw("{$stockExpr} <= min_stock")`

### A.13 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.18`
- Previous baseline ID (older): `A.14`
- File: `app/Livewire/Dashboard/CustomizableDashboard.php`
- Evidence: `$totalValue = (clone $productsQuery)->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(default_price, 0) * COALESCE(stock_quantity, 0)'));`

### A.14 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.19`
- Previous baseline ID (older): `A.15`
- File: `app/Livewire/Helpdesk/Dashboard.php`
- Evidence: `$ticketsByPriority = Ticket::select('priority_id', DB::raw('count(*) as count'))`

### A.15 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.20`
- Previous baseline ID (older): `A.17`
- File: `app/Livewire/Inventory/StockAlerts.php`
- Evidence: `$query->whereRaw('COALESCE(stock_calc.total_stock, 0) <= 0');`

### A.16 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.21`
- Previous baseline ID (older): `A.16`
- File: `app/Livewire/Inventory/StockAlerts.php`
- Evidence: `$query->whereRaw('COALESCE(stock_calc.total_stock, 0) <= products.min_stock AND COALESCE(stock_calc.total_stock, 0) > 0');`

### A.17 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.22`
- Previous baseline ID (older): `A.18`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `->selectRaw("{$dateFormat} as period")`

### A.18 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.23`
- Previous baseline ID (older): `A.19`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `->selectRaw("{$hourExpr} as hour")`

### A.19 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.24`
- Previous baseline ID (older): `A.20`
- File: `app/Livewire/Warehouse/Index.php`
- Evidence: `$totalValue = (clone $stockMovementQuery)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->value('value') ?? 0;`

### A.20 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.25`
- Previous baseline ID (older): `A.21`
- File: `app/Livewire/Warehouse/Movements/Index.php`
- Evidence: `'total_value' => (clone $baseQuery)->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')->value('value') ?? 0,`

### A.21 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.26`
- Previous baseline ID (older): `A.22`
- File: `app/Models/Product.php`
- Evidence: `->whereRaw("({$stockSubquery}) <= stock_alert_threshold");`

### A.22 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.27`
- Previous baseline ID (older): `A.23`
- File: `app/Models/Product.php`
- Evidence: `return $query->whereRaw("({$stockSubquery}) <= 0");`

### A.23 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.28`
- Previous baseline ID (older): `A.24`
- File: `app/Models/Product.php`
- Evidence: `return $query->whereRaw("({$stockSubquery}) > 0");`

### A.24 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.29`
- Previous baseline ID (older): `A.25`
- File: `app/Models/Project.php`
- Evidence: `return $query->whereRaw('actual_cost > budget');`

### A.25 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.30`
- Previous baseline ID (older): `A.27`
- File: `app/Models/SearchIndex.php`
- Evidence: `$q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])`

### A.26 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.31`
- Previous baseline ID (older): `A.26`
- File: `app/Models/SearchIndex.php`
- Evidence: `$builder->whereRaw(`

### A.27 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.32`
- Previous baseline ID (older): `A.29`
- File: `app/Services/Analytics/InventoryTurnoverService.php`
- Evidence: `$avgInventoryValue = $inventoryQuery->sum(DB::raw('COALESCE(stock_quantity, 0) * COALESCE(cost, 0)'));`

### A.28 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.44`
- Previous baseline ID (older): `A.34`
- File: `app/Services/AutomatedAlertService.php`
- Evidence: `->whereRaw("({$stockSubquery}) > 0")`

### A.29 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.45`
- Previous baseline ID (older): `A.35`
- File: `app/Services/Performance/QueryOptimizationService.php`
- Evidence: `DB::statement($optimizeStatement);`

### A.30 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.47`
- Previous baseline ID (older): `A.36`
- File: `app/Services/PurchaseReturnService.php`
- Evidence: `return $query->select('condition', DB::raw('COUNT(*) as count'), DB::raw('SUM(qty_returned) as total_qty'))`

### A.31 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.48`
- Previous baseline ID (older): `A.37`
- File: `app/Services/QueryPerformanceService.php`
- Evidence: `$explain = DB::select('EXPLAIN FORMAT=JSON '.$sql);`

### A.32 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.49`
- Previous baseline ID (older): `A.38`
- File: `app/Services/RentalService.php`
- Evidence: `$stats = $query->selectRaw('`

### A.33 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.50`
- Previous baseline ID (older): `A.40`
- File: `app/Services/Reports/CustomerSegmentationService.php`
- Evidence: `->selectRaw("{$datediffExpr} as days_since_purchase")`

### A.34 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.51`
- Previous baseline ID (older): `A.39`
- File: `app/Services/Reports/CustomerSegmentationService.php`
- Evidence: `->selectRaw("{$datediffExpr} as recency_days")`

### A.35 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.52`
- Previous baseline ID (older): `A.41`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Evidence: `->havingRaw('COALESCE(days_since_sale, 999) > ?', [$days])`

### A.36 — High — Security/SQL — Raw SQL contains interpolated variable inside string
- Baseline ID (v35): `B.2`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Line (v35): `33`
- Evidence: `->selectRaw("{$daysDiffExpr} as days_since_sale")`

### A.37 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.53`
- Previous baseline ID (older): `A.42`
- File: `app/Services/ScheduledReportService.php`
- Evidence: `DB::raw("{$dateExpr} as date"),`

### A.38 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.54`
- Previous baseline ID (older): `A.45`
- File: `app/Services/ScheduledReportService.php`
- Evidence: `$query->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_movements sm INNER JOIN warehouses w ON sm.warehouse_id = w.id WHERE sm.product_id = products.id AND w.branch_id = ?), 0) as quantity', [$branchId]);`

### A.39 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.55`
- Previous baseline ID (older): `A.46`
- File: `app/Services/ScheduledReportService.php`
- Evidence: `$query->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_movements sm INNER JOIN warehouses w ON sm.warehouse_id = w.id WHERE sm.product_id = products.id AND w.branch_id = products.branch_id), 0) as quantity');`

### A.40 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.56`
- Previous baseline ID (older): `A.44`
- File: `app/Services/ScheduledReportService.php`
- Evidence: `$query->whereRaw("({$stockSubquery}) <= COALESCE(products.reorder_point, 0)");`

### A.41 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.57`
- Previous baseline ID (older): `A.43`
- File: `app/Services/ScheduledReportService.php`
- Evidence: `return $query->groupBy(DB::raw($dateExpr))`

### A.42 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.60`
- Previous baseline ID (older): `A.47`
- File: `app/Services/SmartNotificationsService.php`
- Evidence: `->selectRaw("{$stockExpr} as current_quantity")`

### A.43 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.61`
- Previous baseline ID (older): `A.48`
- File: `app/Services/SmartNotificationsService.php`
- Evidence: `->whereRaw("{$stockExpr} <= products.min_stock")`

### A.44 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.62`
- Previous baseline ID (older): `A.49`
- File: `app/Services/StockReorderService.php`
- Evidence: `->whereRaw("({$stockSubquery}) <= reorder_point")`

### A.45 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.63`
- Previous baseline ID (older): `A.50`
- File: `app/Services/StockReorderService.php`
- Evidence: `->whereRaw("({$stockSubquery}) <= stock_alert_threshold")`

### A.46 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.64`
- Previous baseline ID (older): `A.51`
- File: `app/Services/StockReorderService.php`
- Evidence: `->whereRaw("({$stockSubquery}) > COALESCE(reorder_point, 0)")`

### A.47 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.65`
- Previous baseline ID (older): `A.52`
- File: `app/Services/StockService.php`
- Evidence: `return (float) $query->selectRaw('COALESCE(SUM(quantity), 0) as stock')`

### A.48 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.66`
- Previous baseline ID (older): `A.53`
- File: `app/Services/StockService.php`
- Evidence: `return (float) ($query->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as value')`

### A.49 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.67`
- Previous baseline ID (older): `A.56`
- File: `app/Services/WorkflowAutomationService.php`
- Evidence: `->orderByRaw("(COALESCE(reorder_point, min_stock, 0) - ({$stockSubquery})) DESC")`

### A.50 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.68`
- Previous baseline ID (older): `A.55`
- File: `app/Services/WorkflowAutomationService.php`
- Evidence: `->selectRaw("*, ({$stockSubquery}) as calculated_stock")`

### A.51 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.69`
- Previous baseline ID (older): `A.54`
- File: `app/Services/WorkflowAutomationService.php`
- Evidence: `->whereRaw("({$stockSubquery}) <= COALESCE(reorder_point, min_stock, 0)")`

### A.52 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.73`
- Previous baseline ID (older): `A.61`
- File: `resources/views/livewire/admin/dashboard.blade.php`
- Evidence: `? $contractModel::selectRaw('status, COUNT(*) as total')`

### A.53 — High — Security/SQL — Raw SQL with variable interpolation
- Baseline ID (v35): `A.74`
- Previous baseline ID (older): `A.60`
- File: `resources/views/livewire/admin/dashboard.blade.php`
- Evidence: `? $saleModel::selectRaw('DATE(created_at) as day, SUM(total_amount) as total')`

### A.54 — High — Security/XSS — Unescaped Blade output (XSS risk)
- Baseline ID (v35): `A.70`
- Previous baseline ID (older): `A.57`
- File: `resources/views/components/icon.blade.php`
- Evidence: `{!! sanitize_svg_icon($iconPath) !!}`

### A.55 — High — Security/XSS — Unescaped Blade output (XSS risk)
- Baseline ID (v35): `A.71`
- Previous baseline ID (older): `A.58`
- File: `resources/views/components/ui/card.blade.php`
- Evidence: `{!! $actions !!}`

### A.56 — High — Security/XSS — Unescaped Blade output (XSS risk)
- Baseline ID (v35): `A.72`
- Previous baseline ID (older): `A.59`
- File: `resources/views/components/ui/form/input.blade.php`
- Evidence: `@if($wireModel) {!! $wireDirective !!} @endif`

### A.57 — High — Security/XSS — Unescaped Blade output (XSS risk)
- Baseline ID (v35): `A.75`
- Previous baseline ID (older): `A.62`
- File: `resources/views/livewire/auth/two-factor-setup.blade.php`
- Evidence: `{!! $qrCodeSvg !!}`

### A.58 — High — Security/XSS — Unescaped Blade output (XSS risk)
- Baseline ID (v35): `A.76`
- Previous baseline ID (older): `A.63`
- File: `resources/views/livewire/shared/dynamic-form.blade.php`
- Evidence: `<span class="text-slate-400">{!! sanitize_svg_icon($icon) !!}</span>`

### A.59 — High — Security/XSS — Unescaped Blade output (XSS risk)
- Baseline ID (v35): `A.77`
- Previous baseline ID (older): `A.64`
- File: `resources/views/livewire/shared/dynamic-table.blade.php`
- Evidence: `{!! sanitize_svg_icon($actionIcon) !!}`

### A.60 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.3`
- File: `app/Helpers/helpers.php`
- Line (v35): `107`
- Evidence: `$formatted = number_format((float) $normalized, $scale, '.', ',');`

### A.61 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.4`
- File: `app/Helpers/helpers.php`
- Line (v35): `118`
- Evidence: `return number_format((float) $normalized, $decimals, '.', ',').'%';`

### A.62 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.79`
- Previous baseline ID (older): `A.65`
- File: `app/Http/Controllers/Admin/Reports/InventoryReportsExportController.php`
- Evidence: `$stock = (float) ($stockData[$product->id] ?? 0);`

### A.63 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.80`
- Previous baseline ID (older): `A.66`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Evidence: `$inflows = (float) (clone $query)->where('type', 'deposit')->sum('amount');`

### A.64 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.81`
- Previous baseline ID (older): `A.67`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Evidence: `$outflows = (float) (clone $query)->where('type', 'withdrawal')->sum('amount');`

### A.65 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.84`
- Previous baseline ID (older): `B.20`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Evidence: `'cost_of_goods' => (float) $totalPurchases,`

### A.66 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.85`
- Previous baseline ID (older): `B.21`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Evidence: `'expenses' => (float) $totalExpenses,`

### A.67 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.86`
- Previous baseline ID (older): `B.19`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Evidence: `'revenue' => (float) $totalSales,`

### A.68 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.5`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Line (v35): `223`
- Evidence: `'gross_profit' => (float) $grossProfit,`

### A.69 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.6`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Line (v35): `225`
- Evidence: `'net_profit' => (float) $netProfit,`

### A.70 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.7`
- File: `app/Http/Controllers/Admin/ReportsController.php`
- Line (v35): `333`
- Evidence: `$agingFloat = array_map(fn ($v) => (float) $v, $aging);`

### A.71 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.87`
- Previous baseline ID (older): `A.68`
- File: `app/Http/Controllers/Api/StoreIntegrationController.php`
- Evidence: `'current_stock' => (float) ($product->current_stock ?? 0),`

### A.72 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.88`
- Previous baseline ID (older): `B.25`
- File: `app/Http/Controllers/Api/StoreIntegrationController.php`
- Evidence: `$qty = (float) $item['qty'];`

### A.73 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.89`
- Previous baseline ID (older): `B.24`
- File: `app/Http/Controllers/Api/StoreIntegrationController.php`
- Evidence: `$qty = (float) $row['qty'];`

### A.74 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.90`
- Previous baseline ID (older): `B.29`
- File: `app/Http/Controllers/Api/V1/InventoryController.php`
- Evidence: `$actualQty = abs((float) $item['qty']);`

### A.75 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.91`
- Previous baseline ID (older): `B.27`
- File: `app/Http/Controllers/Api/V1/InventoryController.php`
- Evidence: `$actualQty = abs((float) $validated['qty']);`

### A.76 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.92`
- Previous baseline ID (older): `B.28`
- File: `app/Http/Controllers/Api/V1/InventoryController.php`
- Evidence: `$newQuantity = (float) $item['qty'];`

### A.77 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.93`
- Previous baseline ID (older): `B.26`
- File: `app/Http/Controllers/Api/V1/InventoryController.php`
- Evidence: `$newQuantity = (float) $validated['qty'];`

### A.78 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.95`
- Previous baseline ID (older): `A.70`
- File: `app/Http/Controllers/Api/V1/OrdersController.php`
- Evidence: `$lineDiscount = max(0, (float) ($item['discount'] ?? 0));`

### A.79 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.96`
- Previous baseline ID (older): `A.69`
- File: `app/Http/Controllers/Api/V1/OrdersController.php`
- Evidence: `$lineSubtotal = (float) $item['price'] * (float) $item['quantity'];`

### A.80 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.97`
- Previous baseline ID (older): `A.71`
- File: `app/Http/Controllers/Api/V1/OrdersController.php`
- Evidence: `$orderDiscount = max(0, (float) ($validated['discount'] ?? 0));`

### A.81 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.98`
- Previous baseline ID (older): `A.73`
- File: `app/Http/Controllers/Api/V1/OrdersController.php`
- Evidence: `$shipping = max(0, (float) ($validated['shipping'] ?? 0));`

### A.82 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.99`
- Previous baseline ID (older): `A.72`
- File: `app/Http/Controllers/Api/V1/OrdersController.php`
- Evidence: `$tax = max(0, (float) ($validated['tax'] ?? 0));`

### A.83 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.105`
- Previous baseline ID (older): `A.74`
- File: `app/Http/Controllers/Api/V1/POSController.php`
- Evidence: `(float) ($request->input('opening_cash') ?? 0)`

### A.84 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.8`
- File: `app/Http/Controllers/Api/V1/POSController.php`
- Line (v35): `218`
- Evidence: `(float) $request->input('closing_cash'),`

### A.85 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.106`
- Previous baseline ID (older): `A.75`
- File: `app/Http/Controllers/Api/V1/ProductsController.php`
- Evidence: `'price' => (float) $product->default_price,`

### A.86 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.107`
- Previous baseline ID (older): `B.39`
- File: `app/Http/Controllers/Api/V1/ProductsController.php`
- Evidence: `$newQuantity = (float) $validated['quantity'];`

### A.87 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.108`
- Previous baseline ID (older): `B.38`
- File: `app/Http/Controllers/Api/V1/ProductsController.php`
- Evidence: `$quantity = (float) $validated['quantity'];`

### A.88 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.110`
- Previous baseline ID (older): `B.37`
- File: `app/Http/Controllers/Api/V1/ProductsController.php`
- Evidence: `'sale_price' => (float) $product->default_price, // Frontend fallback`

### A.89 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.112`
- Previous baseline ID (older): `A.77`
- File: `app/Http/Controllers/Branch/PurchaseController.php`
- Evidence: `return $this->ok($this->purchases->pay($purchase, (float) $data['amount']), __('Paid'));`

### A.90 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.114`
- Previous baseline ID (older): `A.81`
- File: `app/Http/Controllers/Branch/Purchases/ExportImportController.php`
- Evidence: `'discount_amount' => (float) ($rowData['discount'] ?? 0),`

### A.91 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.115`
- Previous baseline ID (older): `A.82`
- File: `app/Http/Controllers/Branch/Purchases/ExportImportController.php`
- Evidence: `'paid_amount' => (float) ($rowData['paid'] ?? 0),`

### A.92 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.116`
- Previous baseline ID (older): `A.79`
- File: `app/Http/Controllers/Branch/Purchases/ExportImportController.php`
- Evidence: `'subtotal' => (float) ($rowData['subtotal'] ?? $rowData['total']),`

### A.93 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.117`
- Previous baseline ID (older): `A.80`
- File: `app/Http/Controllers/Branch/Purchases/ExportImportController.php`
- Evidence: `'tax_amount' => (float) ($rowData['tax'] ?? 0),`

### A.94 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.118`
- Previous baseline ID (older): `A.78`
- File: `app/Http/Controllers/Branch/Purchases/ExportImportController.php`
- Evidence: `'total_amount' => (float) $rowData['total'],`

### A.95 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.124`
- Previous baseline ID (older): `A.83`
- File: `app/Http/Controllers/Branch/Rental/InvoiceController.php`
- Evidence: `(float) $data['amount'],`

### A.96 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.9`
- File: `app/Http/Controllers/Branch/Rental/InvoiceController.php`
- Line (v35): `68`
- Evidence: `return $this->ok($this->rental->applyPenalty($invoice->id, (float) $data['penalty'], $branch->id), __('Penalty applied'));`

### A.97 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.126`
- Previous baseline ID (older): `A.87`
- File: `app/Http/Controllers/Branch/Sales/ExportImportController.php`
- Evidence: `'discount_amount' => (float) ($rowData['discount'] ?? 0),`

### A.98 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.127`
- Previous baseline ID (older): `A.88`
- File: `app/Http/Controllers/Branch/Sales/ExportImportController.php`
- Evidence: `'paid_amount' => (float) ($rowData['paid'] ?? 0),`

### A.99 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.128`
- Previous baseline ID (older): `A.85`
- File: `app/Http/Controllers/Branch/Sales/ExportImportController.php`
- Evidence: `'subtotal' => (float) ($rowData['subtotal'] ?? $rowData['total']),`

### A.100 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.129`
- Previous baseline ID (older): `A.86`
- File: `app/Http/Controllers/Branch/Sales/ExportImportController.php`
- Evidence: `'tax_amount' => (float) ($rowData['tax'] ?? 0),`

### A.101 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.130`
- Previous baseline ID (older): `A.84`
- File: `app/Http/Controllers/Branch/Sales/ExportImportController.php`
- Evidence: `'total_amount' => (float) $rowData['total'],`

### A.102 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.136`
- Previous baseline ID (older): `B.52`
- File: `app/Http/Controllers/Branch/StockController.php`
- Evidence: `$m = $this->inv->adjust($product->id, (float) $data['qty'], $warehouseId, $data['note'] ?? null);`

### A.103 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.137`
- Previous baseline ID (older): `B.53`
- File: `app/Http/Controllers/Branch/StockController.php`
- Evidence: `$res = $this->inv->transfer($product->id, (float) $data['qty'], $data['from_warehouse'], $data['to_warehouse']);`

### A.104 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.141`
- Previous baseline ID (older): `A.91`
- File: `app/Http/Middleware/EnforceDiscountLimit.php`
- Evidence: `$disc = (float) ($row['discount'] ?? 0);`

### A.105 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.142`
- Previous baseline ID (older): `A.92`
- File: `app/Http/Middleware/EnforceDiscountLimit.php`
- Evidence: `$invDisc = (float) ($payload['invoice_discount'] ?? 0);`

### A.106 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.143`
- Previous baseline ID (older): `A.94`
- File: `app/Http/Middleware/EnforceDiscountLimit.php`
- Evidence: `return (float) (config('erp.discount.max_invoice', 20));`

### A.107 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.144`
- Previous baseline ID (older): `A.93`
- File: `app/Http/Middleware/EnforceDiscountLimit.php`
- Evidence: `return (float) (config('erp.discount.max_line', 15)); // sensible default`

### A.108 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.147`
- Previous baseline ID (older): `B.59`
- File: `app/Http/Middleware/EnforceDiscountLimit.php`
- Evidence: `return (float) $user->max_invoice_discount;`

### A.109 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.148`
- Previous baseline ID (older): `B.57`
- File: `app/Http/Middleware/EnforceDiscountLimit.php`
- Evidence: `return (float) $user->max_line_discount;`

### A.110 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.151`
- Previous baseline ID (older): `A.97`
- File: `app/Http/Resources/CustomerResource.php`
- Evidence: `(float) ($this->balance ?? 0.0)`

### A.111 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.152`
- Previous baseline ID (older): `A.95`
- File: `app/Http/Resources/CustomerResource.php`
- Evidence: `(float) ($this->credit_limit ?? 0.0)`

### A.112 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.153`
- Previous baseline ID (older): `A.96`
- File: `app/Http/Resources/CustomerResource.php`
- Evidence: `(float) ($this->discount_percentage ?? 0.0)`

### A.113 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.154`
- Previous baseline ID (older): `A.98`
- File: `app/Http/Resources/CustomerResource.php`
- Evidence: `(float) ($this->total_purchases ?? 0.0)`

### A.114 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.159`
- Previous baseline ID (older): `A.99`
- File: `app/Http/Resources/OrderItemResource.php`
- Evidence: `'discount' => (float) $this->discount,`

### A.115 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.160`
- Previous baseline ID (older): `A.100`
- File: `app/Http/Resources/OrderItemResource.php`
- Evidence: `'tax' => (float) ($this->tax ?? 0),`

### A.116 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.161`
- Previous baseline ID (older): `A.101`
- File: `app/Http/Resources/OrderItemResource.php`
- Evidence: `'total' => (float) $this->line_total,`

### A.117 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.165`
- Previous baseline ID (older): `B.65`
- File: `app/Http/Resources/OrderItemResource.php`
- Evidence: `'unit_price' => (float) $this->unit_price,`

### A.118 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.166`
- Previous baseline ID (older): `A.102`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `'discount' => (float) $this->discount,`

### A.119 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.167`
- Previous baseline ID (older): `A.105`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `'paid_amount' => (float) ($this->paid_total ?? 0),`

### A.120 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.168`
- Previous baseline ID (older): `A.103`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `'tax' => (float) $this->tax,`

### A.121 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.169`
- Previous baseline ID (older): `A.104`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `'total' => (float) $this->grand_total,`

### A.122 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.170`
- Previous baseline ID (older): `A.107`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `$grandTotal = (float) ($this->grand_total ?? 0);`

### A.123 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.171`
- Previous baseline ID (older): `A.106`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `$paidTotal = (float) ($this->paid_total ?? 0);`

### A.124 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.175`
- Previous baseline ID (older): `B.74`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `'due_amount' => (float) $this->due_total,`

### A.125 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.177`
- Previous baseline ID (older): `B.69`
- File: `app/Http/Resources/OrderResource.php`
- Evidence: `'subtotal' => (float) $this->sub_total,`

### A.126 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.180`
- Previous baseline ID (older): `A.109`
- File: `app/Http/Resources/ProductResource.php`
- Evidence: `'cost' => $this->when(self::$canViewCost, (float) $this->cost),`

### A.127 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.181`
- Previous baseline ID (older): `A.108`
- File: `app/Http/Resources/ProductResource.php`
- Evidence: `'price' => (float) $this->default_price,`

### A.128 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.184`
- Previous baseline ID (older): `B.79`
- File: `app/Http/Resources/ProductResource.php`
- Evidence: `'reorder_qty' => $this->reorder_qty ? (float) $this->reorder_qty : 0.0,`

### A.129 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.10`
- File: `app/Http/Resources/ProductResource.php`
- Line (v35): `50`
- Evidence: `'min_stock' => $this->min_stock ? (float) $this->min_stock : 0.0,`

### A.130 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.11`
- File: `app/Http/Resources/ProductResource.php`
- Line (v35): `51`
- Evidence: `'max_stock' => $this->max_stock ? (float) $this->max_stock : null,`

### A.131 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.12`
- File: `app/Http/Resources/ProductResource.php`
- Line (v35): `52`
- Evidence: `'reorder_point' => $this->reorder_point ? (float) $this->reorder_point : 0.0,`

### A.132 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.13`
- File: `app/Http/Resources/ProductResource.php`
- Line (v35): `54`
- Evidence: `'lead_time_days' => $this->lead_time_days ? (float) $this->lead_time_days : null,`

### A.133 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.185`
- Previous baseline ID (older): `A.112`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'discount_total' => (float) ($this->discount_total ?? 0.0),`

### A.134 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.186`
- Previous baseline ID (older): `A.116`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'due_total' => (float) ($this->due_total ?? 0.0),`

### A.135 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.187`
- Previous baseline ID (older): `A.114`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'grand_total' => (float) ($this->grand_total ?? 0.0),`

### A.136 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.188`
- Previous baseline ID (older): `A.115`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'paid_total' => (float) ($this->paid_total ?? 0.0),`

### A.137 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.189`
- Previous baseline ID (older): `A.113`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'shipping_total' => (float) ($this->shipping_total ?? 0.0),`

### A.138 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.190`
- Previous baseline ID (older): `A.110`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'sub_total' => (float) ($this->sub_total ?? 0.0),`

### A.139 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.191`
- Previous baseline ID (older): `A.111`
- File: `app/Http/Resources/PurchaseResource.php`
- Evidence: `'tax_total' => (float) ($this->tax_total ?? 0.0),`

### A.140 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.199`
- Previous baseline ID (older): `A.119`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'discount_total' => (float) ($this->discount_total ?? 0.0),`

### A.141 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.200`
- Previous baseline ID (older): `A.123`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'due_total' => (float) ($this->due_total ?? 0.0),`

### A.142 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.201`
- Previous baseline ID (older): `A.121`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'grand_total' => (float) ($this->grand_total ?? 0.0),`

### A.143 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.202`
- Previous baseline ID (older): `A.122`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'paid_total' => (float) ($this->paid_total ?? 0.0),`

### A.144 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.203`
- Previous baseline ID (older): `A.120`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'shipping_total' => (float) ($this->shipping_total ?? 0.0),`

### A.145 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.204`
- Previous baseline ID (older): `A.117`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'sub_total' => (float) ($this->sub_total ?? 0.0),`

### A.146 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.205`
- Previous baseline ID (older): `A.118`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'tax_total' => (float) ($this->tax_total ?? 0.0),`

### A.147 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.206`
- Previous baseline ID (older): `B.87`
- File: `app/Http/Resources/SaleResource.php`
- Evidence: `'discount_amount' => $this->discount_amount ? (float) $this->discount_amount : null,`

### A.148 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.214`
- Previous baseline ID (older): `A.124`
- File: `app/Http/Resources/SupplierResource.php`
- Evidence: `(float) ($this->minimum_order_value ?? 0.0)`

### A.149 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.216`
- Previous baseline ID (older): `B.97`
- File: `app/Http/Resources/SupplierResource.php`
- Evidence: `fn () => (float) $this->purchases->sum('total_amount')`

### A.150 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.217`
- Previous baseline ID (older): `B.95`
- File: `app/Http/Resources/SupplierResource.php`
- Evidence: `return $value ? (float) $value : null;`

### A.151 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.218`
- Previous baseline ID (older): `A.125`
- File: `app/Jobs/ClosePosDayJob.php`
- Evidence: `$paid = (float) $paidString;`

### A.152 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.14`
- File: `app/Jobs/ClosePosDayJob.php`
- Line (v35): `71`
- Evidence: `$gross = (float) $grossString;`

### A.153 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.220`
- Previous baseline ID (older): `A.126`
- File: `app/Listeners/ApplyLateFee.php`
- Evidence: `$invoice->amount = (float) $newAmount;`

### A.154 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.222`
- Previous baseline ID (older): `B.100`
- File: `app/Listeners/UpdateStockOnPurchase.php`
- Evidence: `$itemQty = (float) $item->quantity;`

### A.155 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.223`
- Previous baseline ID (older): `B.101`
- File: `app/Listeners/UpdateStockOnSale.php`
- Evidence: `$baseQuantity = (float) $item->quantity * (float) $conversionFactor;`

### A.156 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.224`
- Previous baseline ID (older): `A.127`
- File: `app/Livewire/Accounting/JournalEntries/Form.php`
- Evidence: `'amount' => number_format((float) ltrim($difference, '-'), 2),`

### A.157 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.226`
- Previous baseline ID (older): `B.103`
- File: `app/Livewire/Admin/CurrencyRate/Form.php`
- Evidence: `$this->rate = (float) $rate->rate;`

### A.158 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.227`
- Previous baseline ID (older): `B.104`
- File: `app/Livewire/Admin/Installments/Index.php`
- Evidence: `$this->paymentAmount = (float) $payment->remaining_amount;`

### A.159 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.228`
- Previous baseline ID (older): `B.106`
- File: `app/Livewire/Admin/Loyalty/Index.php`
- Evidence: `$this->amount_per_point = (float) $settings->amount_per_point;`

### A.160 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.229`
- Previous baseline ID (older): `B.105`
- File: `app/Livewire/Admin/Loyalty/Index.php`
- Evidence: `$this->points_per_amount = (float) $settings->points_per_amount;`

### A.161 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.230`
- Previous baseline ID (older): `B.107`
- File: `app/Livewire/Admin/Loyalty/Index.php`
- Evidence: `$this->redemption_rate = (float) $settings->redemption_rate;`

### A.162 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.231`
- Previous baseline ID (older): `B.108`
- File: `app/Livewire/Admin/Modules/RentalPeriods/Form.php`
- Evidence: `$this->price_multiplier = (float) $period->price_multiplier;`

### A.163 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.232`
- Previous baseline ID (older): `B.109`
- File: `app/Livewire/Admin/Reports/InventoryChartsDashboard.php`
- Evidence: `$totalStock = (float) $products->sum('current_stock');`

### A.164 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.233`
- Previous baseline ID (older): `B.110`
- File: `app/Livewire/Admin/Reports/InventoryChartsDashboard.php`
- Evidence: `$values[] = (float) $product->current_stock;`

### A.165 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.234`
- Previous baseline ID (older): `B.113`
- File: `app/Livewire/Admin/Reports/PosChartsDashboard.php`
- Evidence: `$branchValues[] = (float) $items->sum('grand_total');`

### A.166 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.235`
- Previous baseline ID (older): `B.112`
- File: `app/Livewire/Admin/Reports/PosChartsDashboard.php`
- Evidence: `$dayValues[] = (float) $items->sum('grand_total');`

### A.167 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.236`
- Previous baseline ID (older): `B.111`
- File: `app/Livewire/Admin/Reports/PosChartsDashboard.php`
- Evidence: `$totalRevenue = (float) $sales->sum('grand_total');`

### A.168 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.15`
- File: `app/Livewire/Admin/Settings/AdvancedSettings.php`
- Line (v35): `184`
- Evidence: `'late_penalty_percent' => (float) $this->settingsService->get('notifications.late_penalty_percent', 5),`

### A.169 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.237`
- Previous baseline ID (older): `A.128`
- File: `app/Livewire/Admin/Settings/PurchasesSettings.php`
- Evidence: `$this->purchase_approval_threshold = (float) ($settings['purchases.approval_threshold'] ?? 10000);`

### A.170 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.238`
- Previous baseline ID (older): `A.133`
- File: `app/Livewire/Admin/Settings/UnifiedSettings.php`
- Evidence: `$this->hrm_health_insurance_deduction = (float) ($settings['hrm.health_insurance_deduction'] ?? 0.0);`

### A.171 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.239`
- Previous baseline ID (older): `A.131`
- File: `app/Livewire/Admin/Settings/UnifiedSettings.php`
- Evidence: `$this->hrm_housing_allowance_value = (float) ($settings['hrm.housing_allowance_value'] ?? 0.0);`

### A.172 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.240`
- Previous baseline ID (older): `A.132`
- File: `app/Livewire/Admin/Settings/UnifiedSettings.php`
- Evidence: `$this->hrm_meal_allowance = (float) ($settings['hrm.meal_allowance'] ?? 0.0);`

### A.173 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.241`
- Previous baseline ID (older): `A.130`
- File: `app/Livewire/Admin/Settings/UnifiedSettings.php`
- Evidence: `$this->hrm_transport_allowance_value = (float) ($settings['hrm.transport_allowance_value'] ?? 10.0);`

### A.174 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.242`
- Previous baseline ID (older): `A.129`
- File: `app/Livewire/Admin/Settings/UnifiedSettings.php`
- Evidence: `$this->hrm_working_hours_per_day = (float) ($settings['hrm.working_hours_per_day'] ?? 8.0);`

### A.175 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.243`
- Previous baseline ID (older): `A.134`
- File: `app/Livewire/Admin/Settings/UnifiedSettings.php`
- Evidence: `$this->rental_penalty_value = (float) ($settings['rental.penalty_value'] ?? 5.0);`

### A.176 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.247`
- Previous baseline ID (older): `A.137`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Evidence: `$dayValues[] = (float) $items->sum('total');`

### A.177 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.248`
- Previous baseline ID (older): `A.136`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Evidence: `$sources[$source]['revenue'] += (float) $order->total;`

### A.178 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.249`
- Previous baseline ID (older): `A.135`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Evidence: `$totalRevenue = (float) $ordersForStats->sum('total');`

### A.179 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.252`
- Previous baseline ID (older): `B.118`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Evidence: `$totalDiscount = (float) $ordersForStats->sum('discount_total');`

### A.180 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.254`
- Previous baseline ID (older): `B.119`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Evidence: `$totalShipping = (float) $ordersForStats->sum('shipping_total');`

### A.181 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.255`
- Previous baseline ID (older): `B.120`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Evidence: `$totalTax = (float) $ordersForStats->sum('tax_total');`

### A.182 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.16`
- File: `app/Livewire/Admin/Store/OrdersDashboard.php`
- Line (v35): `121`
- Evidence: `return (float) $s['revenue'];`

### A.183 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.17`
- File: `app/Livewire/Admin/UnitsOfMeasure/Form.php`
- Line (v35): `63`
- Evidence: `$this->conversionFactor = (float) $unit->conversion_factor;`

### A.184 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.256`
- Previous baseline ID (older): `A.138`
- File: `app/Livewire/Banking/Reconciliation.php`
- Evidence: `'amount' => number_format((float) $this->difference, 2),`

### A.185 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.258`
- Previous baseline ID (older): `A.139`
- File: `app/Livewire/Concerns/LoadsDashboardData.php`
- Evidence: `$data[] = (float) ($salesByDate[$dateKey] ?? 0);`

### A.186 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.259`
- Previous baseline ID (older): `A.140`
- File: `app/Livewire/Customers/Form.php`
- Evidence: `$this->credit_limit = (float) ($customer->credit_limit ?? 0);`

### A.187 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.260`
- Previous baseline ID (older): `A.141`
- File: `app/Livewire/Customers/Form.php`
- Evidence: `$this->discount_percentage = (float) ($customer->discount_percentage ?? 0);`

### A.188 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.263`
- Previous baseline ID (older): `A.142`
- File: `app/Livewire/Hrm/Employees/Form.php`
- Evidence: `$this->form['salary'] = (float) ($employeeModel->salary ?? 0);`

### A.189 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.18`
- File: `app/Livewire/Hrm/Employees/Form.php`
- Line (v35): `178`
- Evidence: `$employee->salary = (float) $this->form['salary'];`

### A.190 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.19`
- File: `app/Livewire/Hrm/Payroll/Run.php`
- Line (v35): `95`
- Evidence: `$model->basic = (float) $basic;`

### A.191 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.20`
- File: `app/Livewire/Hrm/Payroll/Run.php`
- Line (v35): `96`
- Evidence: `$model->allowances = (float) $allowances;`

### A.192 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.21`
- File: `app/Livewire/Hrm/Payroll/Run.php`
- Line (v35): `97`
- Evidence: `$model->deductions = (float) $deductions;`

### A.193 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.22`
- File: `app/Livewire/Hrm/Payroll/Run.php`
- Line (v35): `98`
- Evidence: `$model->net = (float) $net;`

### A.194 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.264`
- Previous baseline ID (older): `B.126`
- File: `app/Livewire/Hrm/Reports/Dashboard.php`
- Evidence: `'total_net' => (float) $group->sum('net'),`

### A.195 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.265`
- Previous baseline ID (older): `A.143`
- File: `app/Livewire/Income/Form.php`
- Evidence: `$this->amount = (float) $income->amount;`

### A.196 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.23`
- File: `app/Livewire/Inventory/ProductHistory.php`
- Line (v35): `114`
- Evidence: `$this->currentStock = (float) $currentStock;`

### A.197 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.267`
- Previous baseline ID (older): `A.145`
- File: `app/Livewire/Inventory/Products/Form.php`
- Evidence: `$this->form['cost'] = (float) ($p->standard_cost ?? $p->cost ?? 0);`

### A.198 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.268`
- Previous baseline ID (older): `A.146`
- File: `app/Livewire/Inventory/Products/Form.php`
- Evidence: `$this->form['min_stock'] = (float) ($p->min_stock ?? 0);`

### A.199 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.269`
- Previous baseline ID (older): `A.144`
- File: `app/Livewire/Inventory/Products/Form.php`
- Evidence: `$this->form['price'] = (float) ($p->default_price ?? $p->price ?? 0);`

### A.200 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.270`
- Previous baseline ID (older): `A.147`
- File: `app/Livewire/Inventory/Products/Form.php`
- Evidence: `$this->form['reorder_point'] = (float) ($p->reorder_point ?? 0);`

### A.201 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.24`
- File: `app/Livewire/Inventory/Products/Form.php`
- Line (v35): `143`
- Evidence: `$this->form['max_stock'] = $p->max_stock ? (float) $p->max_stock : null;`

### A.202 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.25`
- File: `app/Livewire/Inventory/Products/Form.php`
- Line (v35): `145`
- Evidence: `$this->form['lead_time_days'] = $p->lead_time_days ? (float) $p->lead_time_days : null;`

### A.203 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.273`
- Previous baseline ID (older): `A.148`
- File: `app/Livewire/Inventory/Services/Form.php`
- Evidence: `$this->cost = (float) ($product->cost ?: $product->standard_cost);`

### A.204 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.275`
- Previous baseline ID (older): `B.130`
- File: `app/Livewire/Inventory/Services/Form.php`
- Evidence: `$this->defaultPrice = (float) $product->default_price;`

### A.205 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.276`
- Previous baseline ID (older): `B.132`
- File: `app/Livewire/Inventory/Services/Form.php`
- Evidence: `$this->defaultPrice = (float) bcround($calculated, 2);`

### A.206 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.277`
- Previous baseline ID (older): `B.133`
- File: `app/Livewire/Manufacturing/BillsOfMaterials/Form.php`
- Evidence: `$this->quantity = (float) $this->bom->quantity;`

### A.207 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.26`
- File: `app/Livewire/Manufacturing/BillsOfMaterials/Form.php`
- Line (v35): `72`
- Evidence: `$this->scrap_percentage = (float) $this->bom->scrap_percentage;`

### A.208 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.278`
- Previous baseline ID (older): `B.134`
- File: `app/Livewire/Manufacturing/ProductionOrders/Form.php`
- Evidence: `$this->quantity_planned = (float) $this->productionOrder->quantity_planned;`

### A.209 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.279`
- Previous baseline ID (older): `B.135`
- File: `app/Livewire/Manufacturing/WorkCenters/Form.php`
- Evidence: `$this->cost_per_hour = (float) $this->workCenter->cost_per_hour;`

### A.210 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.27`
- File: `app/Livewire/Manufacturing/WorkCenters/Form.php`
- Line (v35): `77`
- Evidence: `$this->capacity_per_hour = $this->workCenter->capacity_per_hour ? (float) $this->workCenter->capacity_per_hour : null;`

### A.211 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.280`
- Previous baseline ID (older): `A.150`
- File: `app/Livewire/Projects/TimeLogs.php`
- Evidence: `'billable_hours' => (float) ($stats->billable_hours ?? 0),`

### A.212 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.281`
- Previous baseline ID (older): `A.151`
- File: `app/Livewire/Projects/TimeLogs.php`
- Evidence: `'non_billable_hours' => (float) ($stats->non_billable_hours ?? 0),`

### A.213 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.282`
- Previous baseline ID (older): `A.152`
- File: `app/Livewire/Projects/TimeLogs.php`
- Evidence: `'total_cost' => (float) ($stats->total_cost ?? 0),`

### A.214 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.283`
- Previous baseline ID (older): `A.149`
- File: `app/Livewire/Projects/TimeLogs.php`
- Evidence: `'total_hours' => (float) ($stats->total_hours ?? 0),`

### A.215 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.286`
- Previous baseline ID (older): `A.158`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `$discountAmount = max(0, (float) ($item['discount'] ?? 0));`

### A.216 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.287`
- Previous baseline ID (older): `A.155`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `'discount' => (float) ($item->discount ?? 0),`

### A.217 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.288`
- Previous baseline ID (older): `A.156`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `'tax_rate' => (float) ($item->tax_rate ?? 0),`

### A.218 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.289`
- Previous baseline ID (older): `A.157`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `'unit_cost' => (float) ($product->cost ?? 0),`

### A.219 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.290`
- Previous baseline ID (older): `A.153`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `$this->discount_total = (float) ($purchase->discount_total ?? 0);`

### A.220 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.291`
- Previous baseline ID (older): `A.154`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `$this->shipping_total = (float) ($purchase->shipping_total ?? 0);`

### A.221 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.296`
- Previous baseline ID (older): `B.140`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `'qty' => (float) $item->qty,`

### A.222 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.298`
- Previous baseline ID (older): `B.141`
- File: `app/Livewire/Purchases/Form.php`
- Evidence: `'unit_cost' => (float) $item->unit_cost,`

### A.223 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.300`
- Previous baseline ID (older): `A.159`
- File: `app/Livewire/Purchases/Returns/Index.php`
- Evidence: `'cost' => (float) $item->unit_cost,`

### A.224 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.301`
- Previous baseline ID (older): `B.148`
- File: `app/Livewire/Purchases/Returns/Index.php`
- Evidence: `$qty = min((float) $it['qty'], (float) $pi->qty);`

### A.225 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.302`
- Previous baseline ID (older): `B.149`
- File: `app/Livewire/Purchases/Returns/Index.php`
- Evidence: `$unitCost = (float) $pi->unit_cost;`

### A.226 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.304`
- Previous baseline ID (older): `B.146`
- File: `app/Livewire/Purchases/Returns/Index.php`
- Evidence: `'max_qty' => (float) $item->qty,`

### A.227 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.28`
- File: `app/Livewire/Rental/Contracts/Form.php`
- Line (v35): `177`
- Evidence: `$this->form['rent'] = (float) $model->rent;`

### A.228 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.29`
- File: `app/Livewire/Rental/Contracts/Form.php`
- Line (v35): `178`
- Evidence: `$this->form['deposit'] = (float) $model->deposit;`

### A.229 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.30`
- File: `app/Livewire/Rental/Contracts/Form.php`
- Line (v35): `339`
- Evidence: `$contract->rent = (float) $this->form['rent'];`

### A.230 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.31`
- File: `app/Livewire/Rental/Contracts/Form.php`
- Line (v35): `340`
- Evidence: `$contract->deposit = (float) $this->form['deposit'];`

### A.231 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.305`
- Previous baseline ID (older): `A.160`
- File: `app/Livewire/Rental/Reports/Dashboard.php`
- Evidence: `$occupancyRate = $total > 0 ? (float) bcdiv(bcmul((string) $occupied, '100', 4), (string) $total, 1) : 0;`

### A.232 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.32`
- File: `app/Livewire/Rental/Units/Form.php`
- Line (v35): `96`
- Evidence: `$this->form['rent'] = (float) $unitModel->rent;`

### A.233 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.33`
- File: `app/Livewire/Rental/Units/Form.php`
- Line (v35): `97`
- Evidence: `$this->form['deposit'] = (float) $unitModel->deposit;`

### A.234 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.34`
- File: `app/Livewire/Rental/Units/Form.php`
- Line (v35): `155`
- Evidence: `$unit->rent = (float) $this->form['rent'];`

### A.235 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.35`
- File: `app/Livewire/Rental/Units/Form.php`
- Line (v35): `156`
- Evidence: `$unit->deposit = (float) $this->form['deposit'];`

### A.236 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.307`
- Previous baseline ID (older): `A.162`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `$salesGrowth = (float) bcdiv(bcmul($diff, '100', 6), (string) $prevTotalSales, 1);`

### A.237 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.308`
- Previous baseline ID (older): `A.164`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `'totals' => $results->pluck('total')->map(fn ($v) => (float) $v)->toArray(),`

### A.238 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.309`
- Previous baseline ID (older): `A.161`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `$avgOrderValue = $totalOrders > 0 ? (float) bcdiv((string) $totalSales, (string) $totalOrders, 2) : 0;`

### A.239 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.310`
- Previous baseline ID (older): `A.163`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `$completionRate = $totalOrders > 0 ? (float) bcdiv(bcmul((string) $completedOrders, '100', 4), (string) $totalOrders, 1) : 0;`

### A.240 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.314`
- Previous baseline ID (older): `B.154`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `'revenue' => (float) $p->total_revenue,`

### A.241 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.315`
- Previous baseline ID (older): `B.157`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `'revenues' => $results->pluck('total_revenue')->map(fn ($v) => (float) $v)->toArray(),`

### A.242 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.316`
- Previous baseline ID (older): `B.155`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Evidence: `'total_spent' => (float) $c->total_spent,`

### A.243 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.36`
- File: `app/Livewire/Reports/SalesAnalytics.php`
- Line (v35): `229`
- Evidence: `'revenue' => $results->pluck('revenue')->map(fn ($v) => (float) $v)->toArray(),`

### A.244 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.318`
- Previous baseline ID (older): `A.174`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'discount_amount' => (float) bcdiv($discountAmount, '1', BCMATH_STORAGE_SCALE),`

### A.245 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.319`
- Previous baseline ID (older): `A.176`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'line_total' => (float) bcdiv($lineTotal, '1', BCMATH_STORAGE_SCALE),`

### A.246 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.320`
- Previous baseline ID (older): `A.175`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'tax_amount' => (float) bcdiv($taxAmount, '1', BCMATH_STORAGE_SCALE),`

### A.247 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.321`
- Previous baseline ID (older): `A.173`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `$validatedPrice = (float) ($product->default_price ?? 0);`

### A.248 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.322`
- Previous baseline ID (older): `A.169`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `$this->payment_amount = (float) ($firstPayment->amount ?? 0);`

### A.249 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.323`
- Previous baseline ID (older): `A.167`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'discount' => (float) ($item->discount ?? 0),`

### A.250 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.324`
- Previous baseline ID (older): `A.168`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'tax_rate' => (float) ($item->tax_rate ?? 0),`

### A.251 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.325`
- Previous baseline ID (older): `A.170`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'unit_price' => (float) ($product->default_price ?? 0),`

### A.252 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.326`
- Previous baseline ID (older): `A.165`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `$this->discount_total = (float) ($sale->discount_total ?? 0);`

### A.253 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.327`
- Previous baseline ID (older): `A.166`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `$this->shipping_total = (float) ($sale->shipping_total ?? 0);`

### A.254 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.328`
- Previous baseline ID (older): `A.172`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `return (float) bcdiv($result, '1', BCMATH_STORAGE_SCALE);`

### A.255 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.329`
- Previous baseline ID (older): `A.171`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `return (float) bcdiv($total, '1', BCMATH_STORAGE_SCALE);`

### A.256 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.333`
- Previous baseline ID (older): `B.168`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `$validatedPrice = (float) $item['unit_price'];`

### A.257 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.338`
- Previous baseline ID (older): `B.160`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'qty' => (float) $item->qty,`

### A.258 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.341`
- Previous baseline ID (older): `B.161`
- File: `app/Livewire/Sales/Form.php`
- Evidence: `'unit_price' => (float) $item->unit_price,`

### A.259 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.344`
- Previous baseline ID (older): `A.177`
- File: `app/Livewire/Sales/Returns/Index.php`
- Evidence: `'price' => (float) $item->unit_price,`

### A.260 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.345`
- Previous baseline ID (older): `B.172`
- File: `app/Livewire/Sales/Returns/Index.php`
- Evidence: `'max_qty' => (float) $item->qty,`

### A.261 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.347`
- Previous baseline ID (older): `B.174`
- File: `app/Livewire/Sales/Returns/Index.php`
- Evidence: `'qty' => min((float) $item['qty'], (float) $item['max_qty']),`

### A.262 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.349`
- Previous baseline ID (older): `A.180`
- File: `app/Livewire/Suppliers/Form.php`
- Evidence: `$this->delivery_rating = (float) ($supplier->delivery_rating ?? 0);`

### A.263 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.350`
- Previous baseline ID (older): `A.178`
- File: `app/Livewire/Suppliers/Form.php`
- Evidence: `$this->minimum_order_value = (float) ($supplier->minimum_order_value ?? 0);`

### A.264 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.351`
- Previous baseline ID (older): `A.179`
- File: `app/Livewire/Suppliers/Form.php`
- Evidence: `$this->quality_rating = (float) ($supplier->quality_rating ?? 0);`

### A.265 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.352`
- Previous baseline ID (older): `A.181`
- File: `app/Livewire/Suppliers/Form.php`
- Evidence: `$this->service_rating = (float) ($supplier->service_rating ?? 0);`

### A.266 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.354`
- Previous baseline ID (older): `B.178`
- File: `app/Livewire/Warehouse/Adjustments/Form.php`
- Evidence: `'direction' => (float) $item['qty'] >= 0 ? 'in' : 'out',`

### A.267 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.355`
- Previous baseline ID (older): `B.177`
- File: `app/Livewire/Warehouse/Adjustments/Form.php`
- Evidence: `'qty' => abs((float) $item['qty']),`

### A.268 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.356`
- Previous baseline ID (older): `B.179`
- File: `app/Livewire/Warehouse/Transfers/Index.php`
- Evidence: `$qty = (float) $item->quantity;`

### A.269 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.357`
- Previous baseline ID (older): `A.182`
- File: `app/Models/BankTransaction.php`
- Evidence: `$amount = (float) $this->amount;`

### A.270 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.359`
- Previous baseline ID (older): `A.186`
- File: `app/Models/BillOfMaterial.php`
- Evidence: `$costPerHour = (float) ($operation->workCenter->cost_per_hour ?? 0);`

### A.271 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.360`
- Previous baseline ID (older): `A.185`
- File: `app/Models/BillOfMaterial.php`
- Evidence: `$durationHours = (float) ($operation->duration_minutes ?? 0) / 60;`

### A.272 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.361`
- Previous baseline ID (older): `A.183`
- File: `app/Models/BillOfMaterial.php`
- Evidence: `$scrapFactor = 1 + ((float) ($item->scrap_percentage ?? 0) / 100);`

### A.273 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.362`
- Previous baseline ID (older): `A.187`
- File: `app/Models/BillOfMaterial.php`
- Evidence: `return $durationHours * $costPerHour + (float) ($operation->labor_cost ?? 0);`

### A.274 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.363`
- Previous baseline ID (older): `A.184`
- File: `app/Models/BillOfMaterial.php`
- Evidence: `$yieldFactor = (float) ($this->yield_percentage ?? 100) / 100;`

### A.275 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.365`
- Previous baseline ID (older): `B.181`
- File: `app/Models/BillOfMaterial.php`
- Evidence: `$itemQuantity = (float) $item->quantity;`

### A.276 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.367`
- Previous baseline ID (older): `A.188`
- File: `app/Models/BomItem.php`
- Evidence: `$scrapFactor = 1 + ((float) ($this->scrap_percentage ?? 0) / 100);`

### A.277 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.368`
- Previous baseline ID (older): `B.184`
- File: `app/Models/BomItem.php`
- Evidence: `$baseQuantity = (float) $this->quantity;`

### A.278 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.369`
- Previous baseline ID (older): `B.186`
- File: `app/Models/BomOperation.php`
- Evidence: `$laborCost = (float) $this->labor_cost;`

### A.279 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.370`
- Previous baseline ID (older): `B.185`
- File: `app/Models/BomOperation.php`
- Evidence: `$workCenterCost = $timeHours * (float) $this->workCenter->cost_per_hour;`

### A.280 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.37`
- File: `app/Models/BomOperation.php`
- Line (v35): `58`
- Evidence: `return (float) $this->duration_minutes + (float) $this->setup_time_minutes;`

### A.281 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.371`
- Previous baseline ID (older): `B.187`
- File: `app/Models/CurrencyRate.php`
- Evidence: `$rateValue = (float) $rate->rate;`

### A.282 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.372`
- Previous baseline ID (older): `A.189`
- File: `app/Models/FixedAsset.php`
- Evidence: `$currentValue = (float) ($this->current_value ?? 0);`

### A.283 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.373`
- Previous baseline ID (older): `A.191`
- File: `app/Models/FixedAsset.php`
- Evidence: `$purchaseCost = (float) ($this->purchase_cost ?? 0);`

### A.284 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.374`
- Previous baseline ID (older): `A.190`
- File: `app/Models/FixedAsset.php`
- Evidence: `$salvageValue = (float) ($this->salvage_value ?? 0);`

### A.285 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.378`
- Previous baseline ID (older): `A.192`
- File: `app/Models/GRNItem.php`
- Evidence: `$expectedQty = (float) ($this->expected_quantity ?? 0);`

### A.286 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.380`
- Previous baseline ID (older): `B.192`
- File: `app/Models/GRNItem.php`
- Evidence: `return (abs($expectedQty - (float) $acceptedQty) / $expectedQty) * 100;`

### A.287 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.381`
- Previous baseline ID (older): `B.194`
- File: `app/Models/GoodsReceivedNote.php`
- Evidence: `return (float) $this->items->sum('accepted_quantity');`

### A.288 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.382`
- Previous baseline ID (older): `B.193`
- File: `app/Models/GoodsReceivedNote.php`
- Evidence: `return (float) $this->items->sum('received_quantity');`

### A.289 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.383`
- Previous baseline ID (older): `B.195`
- File: `app/Models/GoodsReceivedNote.php`
- Evidence: `return (float) $this->items->sum('rejected_quantity');`

### A.290 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.384`
- Previous baseline ID (older): `A.194`
- File: `app/Models/InstallmentPayment.php`
- Evidence: `$amountPaid = (float) ($this->amount_paid ?? 0);`

### A.291 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.385`
- Previous baseline ID (older): `A.195`
- File: `app/Models/InstallmentPayment.php`
- Evidence: `$newAmountPaid = min($amountPaid + $amount, (float) $this->amount_due);`

### A.292 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.386`
- Previous baseline ID (older): `A.196`
- File: `app/Models/InstallmentPayment.php`
- Evidence: `$newStatus = $newAmountPaid >= (float) $this->amount_due ? 'paid' : 'partial';`

### A.293 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.387`
- Previous baseline ID (older): `A.193`
- File: `app/Models/InstallmentPayment.php`
- Evidence: `return max(0, (float) $this->amount_due - (float) ($this->amount_paid ?? 0));`

### A.294 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.392`
- Previous baseline ID (older): `B.200`
- File: `app/Models/InstallmentPlan.php`
- Evidence: `return (float) $this->payments()->sum('amount_paid');`

### A.295 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.393`
- Previous baseline ID (older): `B.201`
- File: `app/Models/InstallmentPlan.php`
- Evidence: `return max(0, (float) $this->total_amount - (float) $this->down_payment - $this->paid_amount);`

### A.296 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.394`
- Previous baseline ID (older): `A.198`
- File: `app/Models/JournalEntry.php`
- Evidence: `return (float) ($this->attributes['total_credit'] ?? $this->lines()->sum('credit'));`

### A.297 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.395`
- Previous baseline ID (older): `A.197`
- File: `app/Models/JournalEntry.php`
- Evidence: `return (float) ($this->attributes['total_debit'] ?? $this->lines()->sum('debit'));`

### A.298 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.398`
- Previous baseline ID (older): `B.204`
- File: `app/Models/ModuleSetting.php`
- Evidence: `'float', 'decimal' => (float) $this->setting_value,`

### A.299 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.399`
- Previous baseline ID (older): `B.205`
- File: `app/Models/ProductFieldValue.php`
- Evidence: `'decimal' => (float) $this->value,`

### A.300 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.400`
- Previous baseline ID (older): `A.199`
- File: `app/Models/ProductionOrder.php`
- Evidence: `$plannedQty = (float) ($this->planned_quantity ?? 0);`

### A.301 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.401`
- Previous baseline ID (older): `A.200`
- File: `app/Models/ProductionOrder.php`
- Evidence: `return ((float) ($this->produced_quantity ?? 0) / $plannedQty) * 100;`

### A.302 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.404`
- Previous baseline ID (older): `B.208`
- File: `app/Models/ProductionOrder.php`
- Evidence: `return (float) $this->planned_quantity - (float) $this->produced_quantity - (float) $this->rejected_quantity;`

### A.303 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.405`
- Previous baseline ID (older): `A.201`
- File: `app/Models/Project.php`
- Evidence: `return (float) ($timeLogsCost + $expensesCost);`

### A.304 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.38`
- File: `app/Models/Project.php`
- Line (v35): `195`
- Evidence: `return (float) $this->budget;`

### A.305 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.39`
- File: `app/Models/ProjectTask.php`
- Line (v35): `150`
- Evidence: `return (float) $this->timeLogs()->sum('hours');`

### A.306 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.40`
- File: `app/Models/ProjectTask.php`
- Line (v35): `156`
- Evidence: `$estimated = (float) $this->estimated_hours;`

### A.307 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.407`
- Previous baseline ID (older): `A.202`
- File: `app/Models/ProjectTimeLog.php`
- Evidence: `return (float) ($this->hours * $this->hourly_rate);`

### A.308 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.409`
- Previous baseline ID (older): `B.213`
- File: `app/Models/Purchase.php`
- Evidence: `$paidAmount = (float) $this->paid_amount;`

### A.309 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.410`
- Previous baseline ID (older): `B.214`
- File: `app/Models/Purchase.php`
- Evidence: `$totalAmount = (float) $this->total_amount;`

### A.310 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.411`
- Previous baseline ID (older): `B.211`
- File: `app/Models/Purchase.php`
- Evidence: `return (float) $this->paid_amount;`

### A.311 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.412`
- Previous baseline ID (older): `B.212`
- File: `app/Models/Purchase.php`
- Evidence: `return max(0, (float) $this->total_amount - (float) $this->paid_amount);`

### A.312 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.413`
- Previous baseline ID (older): `B.216`
- File: `app/Models/Sale.php`
- Evidence: `$totalAmount = (float) $this->total_amount;`

### A.313 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.414`
- Previous baseline ID (older): `B.215`
- File: `app/Models/Sale.php`
- Evidence: `return max(0, (float) $this->total_amount - $this->total_paid);`

### A.314 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.41`
- File: `app/Models/Sale.php`
- Line (v35): `205`
- Evidence: `return (float) $this->payments()`

### A.315 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.415`
- Previous baseline ID (older): `A.203`
- File: `app/Models/StockTransferItem.php`
- Evidence: `return (float) bcsub((string)$this->qty_shipped, (string)$this->qty_received, 3);`

### A.316 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.417`
- Previous baseline ID (older): `A.204`
- File: `app/Models/Supplier.php`
- Evidence: `return (float) ($this->rating ?? 0);`

### A.317 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.418`
- Previous baseline ID (older): `B.218`
- File: `app/Models/SystemSetting.php`
- Evidence: `'float', 'decimal' => is_numeric($value) ? (float) $value : $default,`

### A.318 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.419`
- Previous baseline ID (older): `B.219`
- File: `app/Models/Traits/CommonQueryScopes.php`
- Evidence: `return number_format((float) $value, 2);`

### A.319 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.43`
- File: `app/Models/Transfer.php`
- Line (v35): `220`
- Evidence: `return (float) $this->items()`

### A.320 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.420`
- Previous baseline ID (older): `B.220`
- File: `app/Models/UnitOfMeasure.php`
- Evidence: `$baseValue = $value * (float) $this->conversion_factor;`

### A.321 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.44`
- File: `app/Models/UnitOfMeasure.php`
- Line (v35): `89`
- Evidence: `$targetFactor = (float) $targetUnit->conversion_factor;`

### A.322 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.421`
- Previous baseline ID (older): `B.221`
- File: `app/Observers/FinancialTransactionObserver.php`
- Evidence: `$customer->addBalance((float) $model->total_amount);`

### A.323 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.422`
- Previous baseline ID (older): `B.222`
- File: `app/Observers/FinancialTransactionObserver.php`
- Evidence: `$customer->subtractBalance((float) $model->total_amount);`

### A.324 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.423`
- Previous baseline ID (older): `B.223`
- File: `app/Observers/FinancialTransactionObserver.php`
- Evidence: `$supplier->addBalance((float) $model->total_amount);`

### A.325 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.424`
- Previous baseline ID (older): `B.224`
- File: `app/Observers/FinancialTransactionObserver.php`
- Evidence: `$supplier->subtractBalance((float) $model->total_amount);`

### A.326 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.425`
- Previous baseline ID (older): `A.205`
- File: `app/Observers/ProductObserver.php`
- Evidence: `$product->cost = round((float) $product->cost, 2);`

### A.327 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.427`
- Previous baseline ID (older): `B.225`
- File: `app/Observers/ProductObserver.php`
- Evidence: `$product->default_price = round((float) $product->default_price, 2);`

### A.328 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.428`
- Previous baseline ID (older): `B.226`
- File: `app/Observers/ProductObserver.php`
- Evidence: `$product->standard_cost = round((float) $product->standard_cost, 2);`

### A.329 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.429`
- Previous baseline ID (older): `A.207`
- File: `app/Repositories/StockMovementRepository.php`
- Evidence: `$qty = abs((float) ($data['qty'] ?? $data['quantity'] ?? 0));`

### A.330 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.430`
- Previous baseline ID (older): `A.206`
- File: `app/Repositories/StockMovementRepository.php`
- Evidence: `$in = (float) (clone $baseQuery)->where('quantity', '>', 0)->sum('quantity');`

### A.331 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.432`
- Previous baseline ID (older): `B.229`
- File: `app/Repositories/StockMovementRepository.php`
- Evidence: `$out = (float) abs((clone $baseQuery)->where('quantity', '<', 0)->sum('quantity'));`

### A.332 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.434`
- Previous baseline ID (older): `B.233`
- File: `app/Repositories/StockMovementRepository.php`
- Evidence: `$totalStock = (float) StockMovement::where('product_id', $productId)`

### A.333 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.435`
- Previous baseline ID (older): `B.230`
- File: `app/Repositories/StockMovementRepository.php`
- Evidence: `return (float) $baseQuery->sum('quantity');`

### A.334 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.436`
- Previous baseline ID (older): `B.231`
- File: `app/Repositories/StockMovementRepository.php`
- Evidence: `return (float) $group->sum('quantity');`

### A.335 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.45`
- File: `app/Repositories/StockMovementRepository.php`
- Line (v35): `190`
- Evidence: `$currentStock = (float) StockMovement::where('product_id', $data['product_id'])`

### A.336 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.437`
- Previous baseline ID (older): `B.234`
- File: `app/Rules/ValidDiscount.php`
- Evidence: `$num = (float) $value;`

### A.337 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.438`
- Previous baseline ID (older): `A.208`
- File: `app/Rules/ValidDiscountPercentage.php`
- Evidence: `$discount = (float) $value;`

### A.338 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.440`
- Previous baseline ID (older): `A.209`
- File: `app/Rules/ValidPriceOverride.php`
- Evidence: `$price = (float) $value;`

### A.339 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.442`
- Previous baseline ID (older): `B.237`
- File: `app/Rules/ValidStockQuantity.php`
- Evidence: `$quantity = (float) $value;`

### A.340 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.443`
- Previous baseline ID (older): `A.210`
- File: `app/Services/AccountingService.php`
- Evidence: `return (float) ($result ?? 0);`

### A.341 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.444`
- Previous baseline ID (older): `B.239`
- File: `app/Services/AccountingService.php`
- Evidence: `$totalCost = (float) bcround($totalCost, 2);`

### A.342 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.445`
- Previous baseline ID (older): `B.238`
- File: `app/Services/AccountingService.php`
- Evidence: `'debit' => (float) $unpaidAmount,`

### A.343 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.46`
- File: `app/Services/AccountingService.php`
- Line (v35): `327`
- Evidence: `if (abs((float) $difference) >= 0.01) {`

### A.344 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.47`
- File: `app/Services/AccountingService.php`
- Line (v35): `336`
- Evidence: `return abs((float) $difference) < 0.01;`

### A.345 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.446`
- Previous baseline ID (older): `B.240`
- File: `app/Services/Analytics/SalesForecastingService.php`
- Evidence: `'avg_order_value' => (float) $row->avg_order_value,`

### A.346 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.48`
- File: `app/Services/Analytics/SalesForecastingService.php`
- Line (v35): `83`
- Evidence: `'revenue' => (float) $row->revenue,`

### A.347 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.447`
- Previous baseline ID (older): `A.212`
- File: `app/Services/AutomatedAlertService.php`
- Evidence: `$availableCredit = (float) bcsub((string) $customer->credit_limit, (string) $customer->balance, 2);`

### A.348 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.448`
- Previous baseline ID (older): `A.213`
- File: `app/Services/AutomatedAlertService.php`
- Evidence: `$estimatedLoss = (float) bcmul((string) $currentStock, (string) $unitCost, 2);`

### A.349 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.449`
- Previous baseline ID (older): `A.211`
- File: `app/Services/AutomatedAlertService.php`
- Evidence: `$utilization = (float) bcmul(`

### A.350 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.452`
- Previous baseline ID (older): `B.244`
- File: `app/Services/BankingService.php`
- Evidence: `(float) $availableBalance,`

### A.351 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.453`
- Previous baseline ID (older): `B.243`
- File: `app/Services/BankingService.php`
- Evidence: `return (float) $this->getAccountBalance($accountId);`

### A.352 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.454`
- Previous baseline ID (older): `B.252`
- File: `app/Services/CostingService.php`
- Evidence: `$batch->quantity = (float) $combinedQty;`

### A.353 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.455`
- Previous baseline ID (older): `B.253`
- File: `app/Services/CostingService.php`
- Evidence: `$batch->unit_cost = (float) $weightedAvgCost;`

### A.354 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.456`
- Previous baseline ID (older): `B.247`
- File: `app/Services/CostingService.php`
- Evidence: `$unitCost = (float) $product->standard_cost;`

### A.355 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.457`
- Previous baseline ID (older): `B.261`
- File: `app/Services/CostingService.php`
- Evidence: `'in_transit' => (float) $transitValue,`

### A.356 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.458`
- Previous baseline ID (older): `B.260`
- File: `app/Services/CostingService.php`
- Evidence: `'in_warehouses' => (float) $warehouseValue,`

### A.357 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.459`
- Previous baseline ID (older): `B.248`
- File: `app/Services/CostingService.php`
- Evidence: `'quantity' => (float) $batchQty,`

### A.358 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.460`
- Previous baseline ID (older): `B.250`
- File: `app/Services/CostingService.php`
- Evidence: `'total_cost' => (float) bcround($batchCost, 2),`

### A.359 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.461`
- Previous baseline ID (older): `B.246`
- File: `app/Services/CostingService.php`
- Evidence: `'total_cost' => (float) bcround($totalCost, 2),`

### A.360 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.462`
- Previous baseline ID (older): `B.259`
- File: `app/Services/CostingService.php`
- Evidence: `'total_quantity' => (float) $totalQuantity,`

### A.361 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.463`
- Previous baseline ID (older): `B.258`
- File: `app/Services/CostingService.php`
- Evidence: `'total_value' => (float) $totalValue,`

### A.362 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.464`
- Previous baseline ID (older): `B.257`
- File: `app/Services/CostingService.php`
- Evidence: `'transit_quantity' => (float) $transitQuantity,`

### A.363 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.465`
- Previous baseline ID (older): `B.256`
- File: `app/Services/CostingService.php`
- Evidence: `'transit_value' => (float) $transitValue,`

### A.364 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.466`
- Previous baseline ID (older): `B.245`
- File: `app/Services/CostingService.php`
- Evidence: `'unit_cost' => (float) $avgCost,`

### A.365 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.467`
- Previous baseline ID (older): `B.249`
- File: `app/Services/CostingService.php`
- Evidence: `'unit_cost' => (float) $batch->unit_cost,`

### A.366 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.468`
- Previous baseline ID (older): `B.251`
- File: `app/Services/CostingService.php`
- Evidence: `'unit_cost' => (float) $unitCost,`

### A.367 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.469`
- Previous baseline ID (older): `B.255`
- File: `app/Services/CostingService.php`
- Evidence: `'warehouse_quantity' => (float) $warehouseQuantity,`

### A.368 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.470`
- Previous baseline ID (older): `B.254`
- File: `app/Services/CostingService.php`
- Evidence: `'warehouse_value' => (float) $warehouseValue,`

### A.369 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.471`
- Previous baseline ID (older): `B.262`
- File: `app/Services/CostingService.php`
- Evidence: `if ((float) $totalStock <= self::STOCK_ZERO_TOLERANCE) {`

### A.370 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.472`
- Previous baseline ID (older): `A.214`
- File: `app/Services/CurrencyExchangeService.php`
- Evidence: `return (float) bcmul((string) $amount, (string) $rate, 4);`

### A.371 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.473`
- Previous baseline ID (older): `B.265`
- File: `app/Services/CurrencyExchangeService.php`
- Evidence: `'rate' => (float) $r->rate,`

### A.372 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.474`
- Previous baseline ID (older): `B.264`
- File: `app/Services/CurrencyExchangeService.php`
- Evidence: `return $rate ? (float) $rate->rate : null;`

### A.373 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.476`
- Previous baseline ID (older): `A.215`
- File: `app/Services/CurrencyService.php`
- Evidence: `return (float) bcmul((string) $amount, (string) $rate, 2);`

### A.374 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.478`
- Previous baseline ID (older): `A.216`
- File: `app/Services/DataValidationService.php`
- Evidence: `$amount = (float) $amount;`

### A.375 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.49`
- File: `app/Services/DataValidationService.php`
- Line (v35): `129`
- Evidence: `$percentage = (float) $percentage;`

### A.376 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.481`
- Previous baseline ID (older): `A.220`
- File: `app/Services/DiscountService.php`
- Evidence: `$value = (float) ($discount['value'] ?? 0);`

### A.377 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.482`
- Previous baseline ID (older): `A.219`
- File: `app/Services/DiscountService.php`
- Evidence: `: (float) config('pos.discount.max_amount', 1000);`

### A.378 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.483`
- Previous baseline ID (older): `A.218`
- File: `app/Services/DiscountService.php`
- Evidence: `return (float) config('pos.discount.max_amount', 1000);`

### A.379 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.484`
- Previous baseline ID (older): `B.274`
- File: `app/Services/DiscountService.php`
- Evidence: `$maxDiscountPercent = (float) config('sales.max_combined_discount_percent', 80);`

### A.380 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.487`
- Previous baseline ID (older): `B.271`
- File: `app/Services/DiscountService.php`
- Evidence: `? (float) config('sales.max_invoice_discount_percent', 30)`

### A.381 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.488`
- Previous baseline ID (older): `B.268`
- File: `app/Services/DiscountService.php`
- Evidence: `return (float) bcround($discTotal, 2);`

### A.382 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.490`
- Previous baseline ID (older): `B.269`
- File: `app/Services/DiscountService.php`
- Evidence: `return (float) config('sales.max_line_discount_percent',`

### A.383 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.491`
- Previous baseline ID (older): `A.223`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total' => (float) bcround((string) $totalAssets, 2),`

### A.384 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.492`
- Previous baseline ID (older): `A.225`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total' => (float) bcround((string) $totalEquity, 2),`

### A.385 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.493`
- Previous baseline ID (older): `A.222`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total' => (float) bcround((string) $totalExpenses, 2),`

### A.386 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.494`
- Previous baseline ID (older): `A.224`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total' => (float) bcround((string) $totalLiabilities, 2),`

### A.387 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.495`
- Previous baseline ID (older): `A.221`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total' => (float) bcround((string) $totalRevenue, 2),`

### A.388 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.496`
- Previous baseline ID (older): `B.286`
- File: `app/Services/FinancialReportService.php`
- Evidence: `$credit = (float) $line->credit;`

### A.389 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.497`
- Previous baseline ID (older): `B.285`
- File: `app/Services/FinancialReportService.php`
- Evidence: `$debit = (float) $line->debit;`

### A.390 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.498`
- Previous baseline ID (older): `B.284`
- File: `app/Services/FinancialReportService.php`
- Evidence: `$outstandingAmount = (float) $purchase->total_amount - (float) $totalPaid;`

### A.391 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.499`
- Previous baseline ID (older): `B.283`
- File: `app/Services/FinancialReportService.php`
- Evidence: `$outstandingAmount = (float) $sale->total_amount - (float) $totalPaid + (float) $totalRefunded;`

### A.392 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.500`
- Previous baseline ID (older): `B.291`
- File: `app/Services/FinancialReportService.php`
- Evidence: `$totalCredit = (float) $query->sum('credit');`

### A.393 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.501`
- Previous baseline ID (older): `B.290`
- File: `app/Services/FinancialReportService.php`
- Evidence: `$totalDebit = (float) $query->sum('debit');`

### A.394 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.502`
- Previous baseline ID (older): `B.289`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'ending_balance' => (float) bcround((string) $runningBalance, 2),`

### A.395 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.508`
- Previous baseline ID (older): `B.276`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total_credit' => (float) bcround($totalCreditStr, 2),`

### A.396 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.509`
- Previous baseline ID (older): `B.288`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total_credit' => (float) bcround((string) $totalCredit, 2),`

### A.397 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.510`
- Previous baseline ID (older): `B.275`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total_debit' => (float) bcround($totalDebitStr, 2),`

### A.398 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.511`
- Previous baseline ID (older): `B.287`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total_debit' => (float) bcround((string) $totalDebit, 2),`

### A.399 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.512`
- Previous baseline ID (older): `B.282`
- File: `app/Services/FinancialReportService.php`
- Evidence: `'total_liabilities_and_equity' => (float) $totalLiabilitiesAndEquity,`

### A.400 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.50`
- File: `app/Services/FinancialReportService.php`
- Line (v35): `73`
- Evidence: `'difference' => (float) $difference,`

### A.401 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.51`
- File: `app/Services/FinancialReportService.php`
- Line (v35): `155`
- Evidence: `'net_income' => (float) $netIncome,`

### A.402 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.513`
- Previous baseline ID (older): `A.226`
- File: `app/Services/HRMService.php`
- Evidence: `$housingAllowance = (float) ($extra['housing_allowance'] ?? 0);`

### A.403 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.514`
- Previous baseline ID (older): `A.229`
- File: `app/Services/HRMService.php`
- Evidence: `$loanDeduction = (float) ($extra['loan_deduction'] ?? 0);`

### A.404 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.515`
- Previous baseline ID (older): `A.228`
- File: `app/Services/HRMService.php`
- Evidence: `$otherAllowance = (float) ($extra['other_allowance'] ?? 0);`

### A.405 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.516`
- Previous baseline ID (older): `A.227`
- File: `app/Services/HRMService.php`
- Evidence: `$transportAllowance = (float) ($extra['transport_allowance'] ?? 0);`

### A.406 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.517`
- Previous baseline ID (older): `A.230`
- File: `app/Services/HRMService.php`
- Evidence: `return (float) bcmul((string) $dailyRate, (string) $absenceDays, 2);`

### A.407 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.518`
- Previous baseline ID (older): `B.293`
- File: `app/Services/HRMService.php`
- Evidence: `$dailyRate = (float) $emp->salary / 30;`

### A.408 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.520`
- Previous baseline ID (older): `B.292`
- File: `app/Services/HRMService.php`
- Evidence: `return (float) bcround($monthlyTax, 2);`

### A.409 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.52`
- File: `app/Services/HRMService.php`
- Line (v35): `109`
- Evidence: `$basic = (float) $emp->salary;`

### A.410 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.53`
- File: `app/Services/HRMService.php`
- Line (v35): `167`
- Evidence: `return (float) bcround($insurance, 2);`

### A.411 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.521`
- Previous baseline ID (older): `A.231`
- File: `app/Services/HelpdeskService.php`
- Evidence: `return (float) bcdiv((string) $totalMinutes, (string) $tickets->count(), 2);`

### A.412 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.526`
- Previous baseline ID (older): `A.233`
- File: `app/Services/ImportService.php`
- Evidence: `'cost' => (float) ($data['cost'] ?? 0),`

### A.413 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.527`
- Previous baseline ID (older): `A.234`
- File: `app/Services/ImportService.php`
- Evidence: `'credit_limit' => (float) ($data['credit_limit'] ?? 0),`

### A.414 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.528`
- Previous baseline ID (older): `A.232`
- File: `app/Services/ImportService.php`
- Evidence: `'default_price' => (float) ($data['default_price'] ?? 0),`

### A.415 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.532`
- Previous baseline ID (older): `A.235`
- File: `app/Services/InstallmentService.php`
- Evidence: `'amount_due' => max(0, (float) $amount),`

### A.416 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.533`
- Previous baseline ID (older): `B.306`
- File: `app/Services/InstallmentService.php`
- Evidence: `$planRemainingAmount = max(0, (float) $planRemainingAmount);`

### A.417 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.534`
- Previous baseline ID (older): `B.304`
- File: `app/Services/InstallmentService.php`
- Evidence: `$remainingAmount = (float) $payment->remaining_amount;`

### A.418 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.535`
- Previous baseline ID (older): `B.302`
- File: `app/Services/InstallmentService.php`
- Evidence: `$totalAmount = (float) $sale->grand_total;`

### A.419 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.537`
- Previous baseline ID (older): `B.305`
- File: `app/Services/InstallmentService.php`
- Evidence: `'amount_paid' => (float) $newAmountPaid,`

### A.420 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.538`
- Previous baseline ID (older): `A.236`
- File: `app/Services/InventoryService.php`
- Evidence: `return (float) ($perWarehouse->get($warehouseId, 0.0));`

### A.421 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.539`
- Previous baseline ID (older): `B.307`
- File: `app/Services/InventoryService.php`
- Evidence: `$qty = (float) $data['qty'];`

### A.422 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.540`
- Previous baseline ID (older): `B.308`
- File: `app/Services/InventoryService.php`
- Evidence: `return (float) $query->sum('quantity');`

### A.423 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.54`
- File: `app/Services/LeaveManagementService.php`
- Line (v35): `621`
- Evidence: `$daysToDeduct = (float) $leaveRequest->days_count;`

### A.424 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.541`
- Previous baseline ID (older): `A.237`
- File: `app/Services/LoyaltyService.php`
- Evidence: `return (float) bcmul((string) $points, (string) $settings->redemption_rate, 2);`

### A.425 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.55`
- File: `app/Services/LoyaltyService.php`
- Line (v35): `37`
- Evidence: `$points = (int) floor((float) $pointsDecimal);`

### A.426 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.543`
- Previous baseline ID (older): `A.245`
- File: `app/Services/POSService.php`
- Evidence: `'paid' => (float) $paidAmountString,`

### A.427 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.544`
- Previous baseline ID (older): `A.243`
- File: `app/Services/POSService.php`
- Evidence: `$amount = (float) ($payment['amount'] ?? 0);`

### A.428 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.545`
- Previous baseline ID (older): `A.244`
- File: `app/Services/POSService.php`
- Evidence: `'amount' => (float) bcround($grandTotal, 2),`

### A.429 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.546`
- Previous baseline ID (older): `A.242`
- File: `app/Services/POSService.php`
- Evidence: `$itemDiscountPercent = (float) ($it['discount'] ?? 0);`

### A.430 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.547`
- Previous baseline ID (older): `A.239`
- File: `app/Services/POSService.php`
- Evidence: `$price = isset($it['price']) ? (float) $it['price'] : (float) ($product->default_price ?? 0);`

### A.431 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.548`
- Previous baseline ID (older): `A.238`
- File: `app/Services/POSService.php`
- Evidence: `$qty = (float) ($it['qty'] ?? 1);`

### A.432 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.549`
- Previous baseline ID (older): `A.241`
- File: `app/Services/POSService.php`
- Evidence: `(new ValidPriceOverride((float) $product->cost, 0.0))->validate('price', $price, function ($m) {`

### A.433 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.550`
- Previous baseline ID (older): `A.240`
- File: `app/Services/POSService.php`
- Evidence: `if ($user && ! $user->can_modify_price && abs($price - (float) ($product->default_price ?? 0)) > 0.001) {`

### A.434 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.553`
- Previous baseline ID (older): `B.310`
- File: `app/Services/POSService.php`
- Evidence: `$previousDailyDiscount = (float) Sale::where('created_by', $user->id)`

### A.435 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.556`
- Previous baseline ID (older): `B.319`
- File: `app/Services/POSService.php`
- Evidence: `$sale->discount_amount = (float) bcround((string) $discountTotal, 2);`

### A.436 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.557`
- Previous baseline ID (older): `B.324`
- File: `app/Services/POSService.php`
- Evidence: `$sale->paid_amount = (float) bcround($paidTotal, 2);`

### A.437 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.558`
- Previous baseline ID (older): `B.318`
- File: `app/Services/POSService.php`
- Evidence: `$sale->subtotal = (float) bcround((string) $subtotal, 2);`

### A.438 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.559`
- Previous baseline ID (older): `B.320`
- File: `app/Services/POSService.php`
- Evidence: `$sale->tax_amount = (float) bcround((string) $taxTotal, 2);`

### A.439 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.560`
- Previous baseline ID (older): `B.321`
- File: `app/Services/POSService.php`
- Evidence: `$sale->total_amount = (float) bcround($grandTotal, 2);`

### A.440 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.561`
- Previous baseline ID (older): `B.316`
- File: `app/Services/POSService.php`
- Evidence: `$systemMaxDiscount = (float) setting('pos.max_discount_percent', 100);`

### A.441 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.562`
- Previous baseline ID (older): `B.325`
- File: `app/Services/POSService.php`
- Evidence: `$totalSales = (float) $salesQuery->sum('total_amount');`

### A.442 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.564`
- Previous baseline ID (older): `B.326`
- File: `app/Services/POSService.php`
- Evidence: `'gross' => (float) $totalAmountString,`

### A.443 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.565`
- Previous baseline ID (older): `B.317`
- File: `app/Services/POSService.php`
- Evidence: `'line_total' => (float) bcround($lineTotal, 2),`

### A.444 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.567`
- Previous baseline ID (older): `B.329`
- File: `app/Services/POSService.php`
- Evidence: `'paid_amount' => (float) $paidAmountString,`

### A.445 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.568`
- Previous baseline ID (older): `B.328`
- File: `app/Services/POSService.php`
- Evidence: `'total_amount' => (float) $totalAmountString,`

### A.446 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.571`
- Previous baseline ID (older): `A.249`
- File: `app/Services/PayslipService.php`
- Evidence: `$limit = (float) ($bracket['limit'] ?? PHP_FLOAT_MAX);`

### A.447 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.572`
- Previous baseline ID (older): `A.250`
- File: `app/Services/PayslipService.php`
- Evidence: `$rate = (float) ($bracket['rate'] ?? 0);`

### A.448 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.573`
- Previous baseline ID (older): `A.246`
- File: `app/Services/PayslipService.php`
- Evidence: `'total' => (float) $total,`

### A.449 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.574`
- Previous baseline ID (older): `A.248`
- File: `app/Services/PayslipService.php`
- Evidence: `$siMaxSalary = (float) ($siConfig['max_salary'] ?? 12600);`

### A.450 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.575`
- Previous baseline ID (older): `A.247`
- File: `app/Services/PayslipService.php`
- Evidence: `$siRate = (float) ($siConfig['rate'] ?? 0.14);`

### A.451 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.576`
- Previous baseline ID (older): `B.333`
- File: `app/Services/PayslipService.php`
- Evidence: `$allowances['housing'] = (float) $housingAmount;`

### A.452 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.577`
- Previous baseline ID (older): `B.331`
- File: `app/Services/PayslipService.php`
- Evidence: `$allowances['transport'] = (float) $transportAmount;`

### A.453 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.578`
- Previous baseline ID (older): `B.332`
- File: `app/Services/PayslipService.php`
- Evidence: `$housingValue = (float) setting('hrm.housing_allowance_value', 0);`

### A.454 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.581`
- Previous baseline ID (older): `B.330`
- File: `app/Services/PayslipService.php`
- Evidence: `$transportValue = (float) setting('hrm.transport_allowance_value', 10);`

### A.455 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.56`
- File: `app/Services/PayslipService.php`
- Line (v35): `102`
- Evidence: `$mealAllowance = (float) setting('hrm.meal_allowance', 0);`

### A.456 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.57`
- File: `app/Services/PayslipService.php`
- Line (v35): `106`
- Evidence: `$allowances['meal'] = (float) $mealAllowanceStr;`

### A.457 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.58`
- File: `app/Services/PayslipService.php`
- Line (v35): `131`
- Evidence: `$deductions['social_insurance'] = (float) $socialInsurance;`

### A.458 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.59`
- File: `app/Services/PayslipService.php`
- Line (v35): `161`
- Evidence: `$healthInsurance = (float) setting('hrm.health_insurance_deduction', 0);`

### A.459 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.60`
- File: `app/Services/PayslipService.php`
- Line (v35): `165`
- Evidence: `$deductions['health_insurance'] = (float) $healthInsuranceStr;`

### A.460 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.61`
- File: `app/Services/PayslipService.php`
- Line (v35): `227`
- Evidence: `'basic' => (float) bcround((string) $basic, 2),`

### A.461 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.62`
- File: `app/Services/PayslipService.php`
- Line (v35): `228`
- Evidence: `'allowances' => (float) bcround((string) $allowances, 2),`

### A.462 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.63`
- File: `app/Services/PayslipService.php`
- Line (v35): `230`
- Evidence: `'deductions' => (float) bcround((string) $deductions, 2),`

### A.463 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.64`
- File: `app/Services/PayslipService.php`
- Line (v35): `232`
- Evidence: `'gross' => (float) bcround((string) $gross, 2),`

### A.464 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.65`
- File: `app/Services/PayslipService.php`
- Line (v35): `233`
- Evidence: `'net' => (float) $net,`

### A.465 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.66`
- File: `app/Services/PayslipService.php`
- Line (v35): `259`
- Evidence: `$currentSalary = (float) $employee->salary;`

### A.466 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.67`
- File: `app/Services/PayslipService.php`
- Line (v35): `285`
- Evidence: `$salaryAtPeriodStart = (float) $salaryChanges[0]['old_salary'];`

### A.467 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.68`
- File: `app/Services/PayslipService.php`
- Line (v35): `301`
- Evidence: `$newSalary = (float) $change['new_salary'];`

### A.468 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.69`
- File: `app/Services/PayslipService.php`
- Line (v35): `328`
- Evidence: `return (float) bcround($proRataSalary, 2);`

### A.469 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.70`
- File: `app/Services/PayslipService.php`
- Line (v35): `367`
- Evidence: `'old_salary' => (float) $old['basic_salary'],`

### A.470 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.71`
- File: `app/Services/PayslipService.php`
- Line (v35): `368`
- Evidence: `'new_salary' => (float) $attributes['basic_salary'],`

### A.471 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.583`
- Previous baseline ID (older): `A.252`
- File: `app/Services/PricingService.php`
- Evidence: `return (float) bcdiv((string) $p, '1', 4);`

### A.472 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.584`
- Previous baseline ID (older): `A.256`
- File: `app/Services/PricingService.php`
- Evidence: `'discount' => (float) bcround((string) $discount, 2),`

### A.473 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.585`
- Previous baseline ID (older): `A.257`
- File: `app/Services/PricingService.php`
- Evidence: `'tax' => (float) bcround((string) $taxAmount, 2),`

### A.474 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.586`
- Previous baseline ID (older): `A.258`
- File: `app/Services/PricingService.php`
- Evidence: `'total' => (float) bcround((string) $total, 2),`

### A.475 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.587`
- Previous baseline ID (older): `A.251`
- File: `app/Services/PricingService.php`
- Evidence: `return (float) bcdiv((string) $override, '1', 4);`

### A.476 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.588`
- Previous baseline ID (older): `A.255`
- File: `app/Services/PricingService.php`
- Evidence: `$discVal = (float) Arr::get($line, 'discount', 0);`

### A.477 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.589`
- Previous baseline ID (older): `A.254`
- File: `app/Services/PricingService.php`
- Evidence: `$price = max(0.0, (float) Arr::get($line, 'price', 0));`

### A.478 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.590`
- Previous baseline ID (older): `A.253`
- File: `app/Services/PricingService.php`
- Evidence: `return (float) bcdiv((string) $base, '1', 4);`

### A.479 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.593`
- Previous baseline ID (older): `B.337`
- File: `app/Services/PricingService.php`
- Evidence: `$qty = max(0.0, (float) Arr::get($line, 'qty', 1));`

### A.480 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.595`
- Previous baseline ID (older): `B.340`
- File: `app/Services/PricingService.php`
- Evidence: `'subtotal' => (float) bcround((string) $subtotal, 2),`

### A.481 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.598`
- Previous baseline ID (older): `A.260`
- File: `app/Services/ProductService.php`
- Evidence: `$product->cost = (float) $cost;`

### A.482 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.599`
- Previous baseline ID (older): `A.259`
- File: `app/Services/ProductService.php`
- Evidence: `$product->default_price = (float) $price;`

### A.483 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.602`
- Previous baseline ID (older): `B.347`
- File: `app/Services/PurchaseReturnService.php`
- Evidence: `$purchaseQty = (float) $purchaseItem->quantity;`

### A.484 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.603`
- Previous baseline ID (older): `B.346`
- File: `app/Services/PurchaseReturnService.php`
- Evidence: `$qtyReturned = (float) $itemData['qty_returned'];`

### A.485 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.604`
- Previous baseline ID (older): `B.349`
- File: `app/Services/PurchaseReturnService.php`
- Evidence: `'qty' => (float) $item->qty_returned,`

### A.486 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.605`
- Previous baseline ID (older): `B.350`
- File: `app/Services/PurchaseReturnService.php`
- Evidence: `'unit_cost' => (float) $item->unit_cost,`

### A.487 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.606`
- Previous baseline ID (older): `B.348`
- File: `app/Services/PurchaseReturnService.php`
- Evidence: `if ((float) $item->qty_returned <= 0) {`

### A.488 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.607`
- Previous baseline ID (older): `A.265`
- File: `app/Services/PurchaseService.php`
- Evidence: `$lineTax = (float) bcmul($taxableAmount, bcdiv((string) $taxPercent, '100', 6), 2);`

### A.489 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.608`
- Previous baseline ID (older): `A.262`
- File: `app/Services/PurchaseService.php`
- Evidence: `$discountPercent = (float) ($it['discount_percent'] ?? 0);`

### A.490 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.609`
- Previous baseline ID (older): `A.264`
- File: `app/Services/PurchaseService.php`
- Evidence: `$lineTax = (float) ($it['tax_amount'] ?? 0);`

### A.491 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.610`
- Previous baseline ID (older): `A.263`
- File: `app/Services/PurchaseService.php`
- Evidence: `$taxPercent = (float) ($it['tax_percent'] ?? 0);`

### A.492 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.611`
- Previous baseline ID (older): `A.261`
- File: `app/Services/PurchaseService.php`
- Evidence: `$unitPrice = (float) ($it['unit_price'] ?? $it['price'] ?? 0);`

### A.493 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.612`
- Previous baseline ID (older): `A.266`
- File: `app/Services/PurchaseService.php`
- Evidence: `$shippingAmount = (float) ($payload['shipping_amount'] ?? 0);`

### A.494 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.613`
- Previous baseline ID (older): `A.267`
- File: `app/Services/PurchaseService.php`
- Evidence: `if ($p->payment_status === 'paid' || (float) $p->paid_amount > 0) {`

### A.495 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.617`
- Previous baseline ID (older): `B.361`
- File: `app/Services/PurchaseService.php`
- Evidence: `$p->discount_amount = (float) bcround($totalDiscount, 2);`

### A.496 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.618`
- Previous baseline ID (older): `B.364`
- File: `app/Services/PurchaseService.php`
- Evidence: `$p->paid_amount = (float) $newPaidAmount;`

### A.497 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.619`
- Previous baseline ID (older): `B.359`
- File: `app/Services/PurchaseService.php`
- Evidence: `$p->subtotal = (float) bcround($subtotal, 2);`

### A.498 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.620`
- Previous baseline ID (older): `B.360`
- File: `app/Services/PurchaseService.php`
- Evidence: `$p->tax_amount = (float) bcround($totalTax, 2);`

### A.499 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.621`
- Previous baseline ID (older): `B.362`
- File: `app/Services/PurchaseService.php`
- Evidence: `$p->total_amount = (float) bcround(`

### A.500 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.622`
- Previous baseline ID (older): `B.351`
- File: `app/Services/PurchaseService.php`
- Evidence: `$qty = (float) $it['qty'];`

### A.501 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.623`
- Previous baseline ID (older): `B.363`
- File: `app/Services/PurchaseService.php`
- Evidence: `$remainingDue = max(0, (float) $p->total_amount - (float) $p->paid_amount);`

### A.502 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.627`
- Previous baseline ID (older): `B.357`
- File: `app/Services/PurchaseService.php`
- Evidence: `'line_total' => (float) $lineTotal,`

### A.503 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.629`
- Previous baseline ID (older): `B.365`
- File: `app/Services/PurchaseService.php`
- Evidence: `if ((float) $p->paid_amount >= (float) $p->total_amount) {`

### A.504 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.630`
- Previous baseline ID (older): `B.366`
- File: `app/Services/PurchaseService.php`
- Evidence: `} elseif ((float) $p->paid_amount > 0) {`

### A.505 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.72`
- File: `app/Services/QueryPerformanceService.php`
- Line (v35): `271`
- Evidence: `'innodb_flush_method' => 'O_DIRECT to avoid double buffering',`

### A.506 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.631`
- Previous baseline ID (older): `A.268`
- File: `app/Services/RentalService.php`
- Evidence: `$i->amount = (float) $newAmount;`

### A.507 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.632`
- Previous baseline ID (older): `A.270`
- File: `app/Services/RentalService.php`
- Evidence: `? (float) bcmul(bcdiv((string) $collectedAmount, (string) $totalAmount, 4), '100', 2)`

### A.508 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.633`
- Previous baseline ID (older): `A.269`
- File: `app/Services/RentalService.php`
- Evidence: `? (float) bcmul(bcdiv((string) $occupiedUnits, (string) $totalUnits, 4), '100', 2)`

### A.509 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.635`
- Previous baseline ID (older): `B.368`
- File: `app/Services/RentalService.php`
- Evidence: `$i->paid_total = (float) $newPaidTotal;`

### A.510 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.73`
- File: `app/Services/RentalService.php`
- Line (v35): `133`
- Evidence: `'rent' => (float) $payload['rent'],`

### A.511 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.74`
- File: `app/Services/RentalService.php`
- Line (v35): `154`
- Evidence: `$c->rent = (float) $payload['rent'];`

### A.512 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.638`
- Previous baseline ID (older): `A.273`
- File: `app/Services/ReportService.php`
- Evidence: `'pnl' => (float) ($sales->total ?? 0) - (float) ($purchases->total ?? 0),`

### A.513 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.639`
- Previous baseline ID (older): `A.272`
- File: `app/Services/ReportService.php`
- Evidence: `'purchases' => ['total' => (float) ($purchases->total ?? 0), 'paid' => (float) ($purchases->paid ?? 0)],`

### A.514 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.640`
- Previous baseline ID (older): `A.271`
- File: `app/Services/ReportService.php`
- Evidence: `'sales' => ['total' => (float) ($sales->total ?? 0), 'paid' => (float) ($sales->paid ?? 0)],`

### A.515 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.75`
- File: `app/Services/ReportService.php`
- Line (v35): `83`
- Evidence: `return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'gross' => (float) $r->gross])->all();`

### A.516 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.76`
- File: `app/Services/ReportService.php`
- Line (v35): `181`
- Evidence: `'total_value' => $items->sum(fn ($p) => ((float) ($p->stock_quantity ?? 0)) * ((float) ($p->cost ?? $p->standard_cost ?? 0))),`

### A.517 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.77`
- File: `app/Services/ReportService.php`
- Line (v35): `182`
- Evidence: `'total_cost' => $items->sum(fn ($p) => (float) ($p->standard_cost ?? 0)),`

### A.518 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.644`
- Previous baseline ID (older): `A.274`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Evidence: `'total_expected_inflows' => (float) $expectedInflows->sum('amount'),`

### A.519 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.645`
- Previous baseline ID (older): `A.275`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Evidence: `'total_expected_outflows' => (float) $expectedOutflows->sum('amount'),`

### A.520 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.646`
- Previous baseline ID (older): `B.378`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Evidence: `'ending_balance' => (float) $runningBalance,`

### A.521 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.647`
- Previous baseline ID (older): `B.377`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Evidence: `'ending_cash_forecast' => (float) $dailyForecast->last()['ending_balance'],`

### A.522 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.78`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Line (v35): `39`
- Evidence: `'current_cash' => (float) $currentCash,`

### A.523 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.79`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Line (v35): `138`
- Evidence: `'inflows' => (float) $dailyInflowsStr,`

### A.524 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.80`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Line (v35): `139`
- Evidence: `'outflows' => (float) $dailyOutflowsStr,`

### A.525 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.81`
- File: `app/Services/Reports/CashFlowForecastService.php`
- Line (v35): `140`
- Evidence: `'net_flow' => (float) $netFlow,`

### A.526 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.650`
- Previous baseline ID (older): `A.276`
- File: `app/Services/Reports/CustomerSegmentationService.php`
- Evidence: `? (float) bcdiv($totalRevenue, (string) count($customers), 2)`

### A.527 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.651`
- Previous baseline ID (older): `B.379`
- File: `app/Services/Reports/CustomerSegmentationService.php`
- Evidence: `'total_revenue' => (float) $totalRevenue,`

### A.528 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.82`
- File: `app/Services/Reports/CustomerSegmentationService.php`
- Line (v35): `179`
- Evidence: `'revenue_at_risk' => (float) $at_risk->sum('lifetime_revenue'),`

### A.529 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.653`
- Previous baseline ID (older): `B.382`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Evidence: `'daily_sales_rate' => (float) $dailyRate,`

### A.530 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.654`
- Previous baseline ID (older): `B.381`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Evidence: `'stock_value' => (float) $stockValue,`

### A.531 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.655`
- Previous baseline ID (older): `B.384`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Evidence: `'total_potential_loss' => (float) $products->sum(function ($product) {`

### A.532 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.656`
- Previous baseline ID (older): `B.383`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Evidence: `'total_stock_value' => (float) $products->sum(function ($product) {`

### A.533 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.83`
- File: `app/Services/Reports/SlowMovingStockService.php`
- Line (v35): `102`
- Evidence: `'potential_loss' => (float) $potentialLoss,`

### A.534 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.657`
- Previous baseline ID (older): `A.278`
- File: `app/Services/SaleService.php`
- Evidence: `'amount' => (float) $refund,`

### A.535 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.658`
- Previous baseline ID (older): `A.277`
- File: `app/Services/SaleService.php`
- Evidence: `$requestedQty = (float) ($it['qty'] ?? 0);`

### A.536 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.659`
- Previous baseline ID (older): `B.386`
- File: `app/Services/SaleService.php`
- Evidence: `$availableToReturn = max(0, (float) $si->quantity - $alreadyReturned);`

### A.537 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.660`
- Previous baseline ID (older): `B.390`
- File: `app/Services/SaleService.php`
- Evidence: `$currentReturnMap[$saleItemId] = ($currentReturnMap[$saleItemId] ?? 0) + (float) $item['qty'];`

### A.538 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.662`
- Previous baseline ID (older): `B.389`
- File: `app/Services/SaleService.php`
- Evidence: `$returned[$itemId] = abs((float) $returnedQty);`

### A.539 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.663`
- Previous baseline ID (older): `B.391`
- File: `app/Services/SaleService.php`
- Evidence: `$soldQty = (float) $saleItem->quantity;`

### A.540 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.665`
- Previous baseline ID (older): `B.387`
- File: `app/Services/SaleService.php`
- Evidence: `'total_amount' => (float) $refund,`

### A.541 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.666`
- Previous baseline ID (older): `A.280`
- File: `app/Services/SalesReturnService.php`
- Evidence: `$requestedAmount = (float) ($validated['amount'] ?? $return->refund_amount);`

### A.542 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.667`
- Previous baseline ID (older): `B.394`
- File: `app/Services/SalesReturnService.php`
- Evidence: `$remainingRefundable = (float) $return->refund_amount - (float) $alreadyRefunded;`

### A.543 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.84`
- File: `app/Services/SalesReturnService.php`
- Line (v35): `92`
- Evidence: `$qtyToReturn = (float) ($itemData['qty'] ?? 0);`

### A.544 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.669`
- Previous baseline ID (older): `B.395`
- File: `app/Services/SmartNotificationsService.php`
- Evidence: `$dueTotal = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);`

### A.545 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.670`
- Previous baseline ID (older): `B.401`
- File: `app/Services/StockReorderService.php`
- Evidence: `'total_estimated_cost' => (float) bcround((string) $totalEstimatedCost, 2),`

### A.546 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.671`
- Previous baseline ID (older): `B.400`
- File: `app/Services/StockReorderService.php`
- Evidence: `return $totalSold ? ((float) $totalSold / $days) : 0;`

### A.547 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.672`
- Previous baseline ID (older): `B.398`
- File: `app/Services/StockReorderService.php`
- Evidence: `return (float) $product->maximum_order_quantity;`

### A.548 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.673`
- Previous baseline ID (older): `B.397`
- File: `app/Services/StockReorderService.php`
- Evidence: `return (float) $product->minimum_order_quantity;`

### A.549 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.674`
- Previous baseline ID (older): `B.396`
- File: `app/Services/StockReorderService.php`
- Evidence: `return (float) $product->reorder_qty;`

### A.550 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.675`
- Previous baseline ID (older): `B.399`
- File: `app/Services/StockReorderService.php`
- Evidence: `return (float) bcround((string) $optimalQty, 2);`

### A.551 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.85`
- File: `app/Services/StockReorderService.php`
- Line (v35): `111`
- Evidence: `return $product->reorder_point ? ((float) $product->reorder_point * 2) : 50;`

### A.552 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.86`
- File: `app/Services/StockReorderService.php`
- Line (v35): `168`
- Evidence: `'sales_velocity' => (float) bcround((string) $salesVelocity, 2),`

### A.553 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.676`
- Previous baseline ID (older): `B.404`
- File: `app/Services/StockService.php`
- Evidence: `$totalStock = (float) StockMovement::where('product_id', $productId)`

### A.554 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.87`
- File: `app/Services/StockService.php`
- Line (v35): `137`
- Evidence: `return (float) DB::table('stock_movements')`

### A.555 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.88`
- File: `app/Services/StockService.php`
- Line (v35): `278`
- Evidence: `$stockBefore = (float) DB::table('stock_movements')`

### A.556 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.679`
- Previous baseline ID (older): `A.283`
- File: `app/Services/StockTransferService.php`
- Evidence: `$qtyDamaged = (float) ($itemData['qty_damaged'] ?? 0);`

### A.557 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.680`
- Previous baseline ID (older): `A.285`
- File: `app/Services/StockTransferService.php`
- Evidence: `$qtyDamaged = (float) ($itemReceivingData['qty_damaged'] ?? 0);`

### A.558 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.681`
- Previous baseline ID (older): `A.282`
- File: `app/Services/StockTransferService.php`
- Evidence: `$qtyReceived = (float) ($itemData['qty_received'] ?? 0);`

### A.559 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.682`
- Previous baseline ID (older): `A.284`
- File: `app/Services/StockTransferService.php`
- Evidence: `$qtyReceived = (float) ($itemReceivingData['qty_received'] ?? $item->qty_shipped);`

### A.560 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.683`
- Previous baseline ID (older): `A.281`
- File: `app/Services/StockTransferService.php`
- Evidence: `$requestedQty = (float) ($itemData['qty'] ?? 0);`

### A.561 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.684`
- Previous baseline ID (older): `B.406`
- File: `app/Services/StockTransferService.php`
- Evidence: `$itemQuantities[(int) $itemId] = (float) $itemData['qty_shipped'];`

### A.562 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.690`
- Previous baseline ID (older): `A.287`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$discount = (float) Arr::get($item, 'discount', 0);`

### A.563 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.691`
- Previous baseline ID (older): `A.286`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$price = (float) Arr::get($item, 'price', 0);`

### A.564 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.692`
- Previous baseline ID (older): `A.291`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$discount = (float) ($order->discount_total ?? 0);`

### A.565 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.693`
- Previous baseline ID (older): `A.290`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$shipping = (float) ($order->shipping_total ?? 0);`

### A.566 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.694`
- Previous baseline ID (older): `A.289`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$tax = (float) ($order->tax_total ?? 0);`

### A.567 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.695`
- Previous baseline ID (older): `A.288`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$total = (float) ($order->total ?? 0);`

### A.568 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.699`
- Previous baseline ID (older): `B.411`
- File: `app/Services/Store/StoreOrderToSaleService.php`
- Evidence: `$qty = (float) Arr::get($item, 'qty', 0);`

### A.569 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.703`
- Previous baseline ID (older): `A.313`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'discount_amount' => (float) ($lineItem['discount'] ?? 0),`

### A.570 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.704`
- Previous baseline ID (older): `A.298`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'discount_amount' => (float) ($lineItem['total_discount'] ?? 0),`

### A.571 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.705`
- Previous baseline ID (older): `A.314`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'line_total' => (float) ($lineItem['line_total'] ?? $lineItem['total'] ?? 0),`

### A.572 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.706`
- Previous baseline ID (older): `A.299`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'line_total' => (float) ($lineItem['quantity'] ?? 1) * (float) ($lineItem['price'] ?? 0) - (float) ($lineItem['total_discount'] ?? 0),`

### A.573 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.707`
- Previous baseline ID (older): `A.304`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'line_total' => (float) ($lineItem['total'] ?? 0),`

### A.574 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.708`
- Previous baseline ID (older): `A.311`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'quantity' => (float) ($lineItem['qty'] ?? $lineItem['quantity'] ?? 1),`

### A.575 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.709`
- Previous baseline ID (older): `A.297`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'unit_price' => (float) ($lineItem['price'] ?? 0),`

### A.576 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.710`
- Previous baseline ID (older): `A.312`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'unit_price' => (float) ($lineItem['unit_price'] ?? $lineItem['price'] ?? 0),`

### A.577 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.711`
- Previous baseline ID (older): `A.306`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'cost' => (float) ($data['cost'] ?? 0),`

### A.578 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.712`
- Previous baseline ID (older): `A.305`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'default_price' => (float) ($data['default_price'] ?? $data['price'] ?? 0),`

### A.579 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.713`
- Previous baseline ID (older): `A.300`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'default_price' => (float) ($data['price'] ?? 0),`

### A.580 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.714`
- Previous baseline ID (older): `A.292`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'default_price' => (float) ($data['variants'][0]['price'] ?? 0),`

### A.581 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.715`
- Previous baseline ID (older): `A.309`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'discount_amount' => (float) ($data['discount_total'] ?? $data['discount'] ?? 0),`

### A.582 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.716`
- Previous baseline ID (older): `A.302`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'discount_amount' => (float) ($data['discount_total'] ?? 0),`

### A.583 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.717`
- Previous baseline ID (older): `A.295`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'discount_amount' => (float) ($data['total_discounts'] ?? 0),`

### A.584 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.718`
- Previous baseline ID (older): `A.307`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'subtotal' => (float) ($data['sub_total'] ?? $data['subtotal'] ?? 0),`

### A.585 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.719`
- Previous baseline ID (older): `A.293`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'subtotal' => (float) ($data['subtotal_price'] ?? 0),`

### A.586 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.720`
- Previous baseline ID (older): `A.301`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'subtotal' => (float) ($data['total'] ?? 0) - (float) ($data['total_tax'] ?? 0),`

### A.587 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.721`
- Previous baseline ID (older): `A.308`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'tax_amount' => (float) ($data['tax_total'] ?? $data['tax'] ?? 0),`

### A.588 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.722`
- Previous baseline ID (older): `A.294`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'tax_amount' => (float) ($data['total_tax'] ?? 0),`

### A.589 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.723`
- Previous baseline ID (older): `A.310`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'total_amount' => (float) ($data['grand_total'] ?? $data['total'] ?? 0),`

### A.590 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.724`
- Previous baseline ID (older): `A.303`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'total_amount' => (float) ($data['total'] ?? 0),`

### A.591 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.725`
- Previous baseline ID (older): `A.296`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `'total_amount' => (float) ($data['total_price'] ?? 0),`

### A.592 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.726`
- Previous baseline ID (older): `A.315`
- File: `app/Services/Store/StoreSyncService.php`
- Evidence: `return (float) ($product->standard_cost ?? $product->cost ?? 0);`

### A.593 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.751`
- Previous baseline ID (older): `A.324`
- File: `app/Services/TaxService.php`
- Evidence: `'total_with_tax' => (float) bcadd((string) $subtotal, (string) $taxAmount, 4),`

### A.594 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.752`
- Previous baseline ID (older): `A.323`
- File: `app/Services/TaxService.php`
- Evidence: `$subtotal = (float) ($item['subtotal'] ?? $item['line_total'] ?? 0);`

### A.595 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.753`
- Previous baseline ID (older): `A.318`
- File: `app/Services/TaxService.php`
- Evidence: `return (float) bcdiv($taxPortion, '1', 4);`

### A.596 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.754`
- Previous baseline ID (older): `A.320`
- File: `app/Services/TaxService.php`
- Evidence: `return (float) bcdiv((string) $base, '1', 4);`

### A.597 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.755`
- Previous baseline ID (older): `A.317`
- File: `app/Services/TaxService.php`
- Evidence: `$rate = (float) $tax->rate;`

### A.598 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.756`
- Previous baseline ID (older): `A.325`
- File: `app/Services/TaxService.php`
- Evidence: `$rate = (float) ($taxRateRules['rate'] ?? 0);`

### A.599 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.757`
- Previous baseline ID (older): `A.319`
- File: `app/Services/TaxService.php`
- Evidence: `return (float) bcdiv($taxAmount, '1', 4);`

### A.600 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.758`
- Previous baseline ID (older): `A.321`
- File: `app/Services/TaxService.php`
- Evidence: `return (float) bcdiv($total, '1', 4);`

### A.601 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.759`
- Previous baseline ID (older): `A.322`
- File: `app/Services/TaxService.php`
- Evidence: `defaultValue: (float) bcdiv((string) $base, '1', 4)`

### A.602 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.760`
- Previous baseline ID (older): `A.316`
- File: `app/Services/TaxService.php`
- Evidence: `return (float) ($tax?->rate ?? 0.0);`

### A.603 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.764`
- Previous baseline ID (older): `B.451`
- File: `app/Services/TaxService.php`
- Evidence: `'total_tax' => (float) bcround($totalTax, 2),`

### A.604 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.771`
- Previous baseline ID (older): `B.443`
- File: `app/Services/TaxService.php`
- Evidence: `return (float) bcround($taxAmount, 2);`

### A.605 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.772`
- Previous baseline ID (older): `A.326`
- File: `app/Services/UIHelperService.php`
- Evidence: `$value = (float) bcdiv((string) $value, '1024', $precision + 2);`

### A.606 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.774`
- Previous baseline ID (older): `A.332`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'price' => (float) $item->default_price,`

### A.607 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.775`
- Previous baseline ID (older): `A.333`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'price' => (float) $product->default_price,`

### A.608 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.776`
- Previous baseline ID (older): `A.328`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'price' => (float) $price,`

### A.609 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.777`
- Previous baseline ID (older): `A.329`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'profit_per_unit' => (float) bcsub($price, (string) $cost, 2),`

### A.610 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.778`
- Previous baseline ID (older): `A.331`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'profit_per_unit' => (float) bcsub($suggestedPrice, (string) $cost, 2),`

### A.611 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.779`
- Previous baseline ID (older): `A.336`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `? (float) bcmul(bcdiv(bcsub((string) $product->default_price, (string) $product->standard_cost, 2), (string) $product->default_price, 4), '100', 2)`

### A.612 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.780`
- Previous baseline ID (older): `A.327`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `$cost = (float) ($product->standard_cost ?? 0);`

### A.613 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.781`
- Previous baseline ID (older): `A.330`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `$currentPrice = (float) ($product->default_price ?? 0);`

### A.614 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.782`
- Previous baseline ID (older): `A.335`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `return (float) ($totalStock ?? 0);`

### A.615 — Medium — Finance/Precision — BCMath result cast to float
- Baseline ID (v35): `A.783`
- Previous baseline ID (older): `A.334`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `return (float) bcdiv((string) ($totalSold ?? 0), (string) $days, 2);`

### A.616 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.786`
- Previous baseline ID (older): `B.454`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `$suggestedQty = max((float) $eoq, (float) $product->minimum_order_quantity ?? 1);`

### A.617 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.787`
- Previous baseline ID (older): `B.464`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'avg_quantity' => (float) $item->avg_quantity,`

### A.618 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.788`
- Previous baseline ID (older): `B.465`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'individual_total' => (float) $totalPrice,`

### A.619 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.794`
- Previous baseline ID (older): `B.462`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'recommendation' => $this->generatePricingRecommendation((float) $suggestedPrice, $currentPrice, (float) $currentMargin),`

### A.620 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.795`
- Previous baseline ID (older): `B.455`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'recommendation' => $this->generateReorderRecommendation($urgency, (float) $daysOfStock, $suggestedQty),`

### A.621 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.796`
- Previous baseline ID (older): `B.466`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'suggested_bundle_price' => (float) $suggestedBundlePrice,`

### A.622 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.797`
- Previous baseline ID (older): `B.460`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Evidence: `'suggested_price' => (float) $suggestedPrice,`

### A.623 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.89`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `69`
- Evidence: `$urgency = $this->determineReorderUrgency((float) $currentStock, (float) $reorderPoint, (float) $product->min_stock ?? 0);`

### A.624 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.90`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `76`
- Evidence: `'reorder_point' => (float) $reorderPoint,`

### A.625 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.91`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `78`
- Evidence: `'sales_velocity' => (float) $salesVelocity,`

### A.626 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.92`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `79`
- Evidence: `'days_of_stock_remaining' => (float) $daysOfStock,`

### A.627 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.93`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `141`
- Evidence: `'current_margin' => (float) $currentMargin.'%',`

### A.628 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.94`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `203`
- Evidence: `'customer_savings' => (float) $savings,`

### A.629 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.95`
- File: `app/Services/UX/SmartSuggestionsService.php`
- Line (v35): `246`
- Evidence: `'margin' => (float) $margin,`

### A.630 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.801`
- Previous baseline ID (older): `A.338`
- File: `app/Services/WhatsAppService.php`
- Evidence: `'discount' => number_format((float) $sale->discount_total, 2),`

### A.631 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.802`
- Previous baseline ID (older): `A.337`
- File: `app/Services/WhatsAppService.php`
- Evidence: `'tax' => number_format((float) $sale->tax_total, 2),`

### A.632 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.803`
- Previous baseline ID (older): `A.339`
- File: `app/Services/WhatsAppService.php`
- Evidence: `'total' => number_format((float) $sale->grand_total, 2),`

### A.633 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.805`
- Previous baseline ID (older): `B.472`
- File: `app/Services/WhatsAppService.php`
- Evidence: `'subtotal' => number_format((float) $sale->sub_total, 2),`

### A.634 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.808`
- Previous baseline ID (older): `B.471`
- File: `app/Services/WhatsAppService.php`
- Evidence: `return "• {$item->product->name} x{$item->qty} = ".number_format((float) $item->line_total, 2);`

### A.635 — Medium — Finance/Precision — Float cast for totals
- Baseline ID (v35): `A.809`
- Previous baseline ID (older): `A.340`
- File: `app/Services/WoodService.php`
- Evidence: `'qty' => (float) ($payload['qty'] ?? 0),`

### A.636 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.810`
- Previous baseline ID (older): `B.477`
- File: `app/Services/WoodService.php`
- Evidence: `$eff = $this->efficiency((float) $row->input_qty, (float) $row->output_qty);`

### A.637 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.811`
- Previous baseline ID (older): `B.476`
- File: `app/Services/WoodService.php`
- Evidence: `'efficiency' => $this->efficiency((float) $payload['input_qty'], (float) $payload['output_qty']),`

### A.638 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.96`
- File: `app/Services/WoodService.php`
- Line (v35): `105`
- Evidence: `return (float) bcround($percentage, 2);`

### A.639 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.813`
- Previous baseline ID (older): `A.342`
- File: `app/ValueObjects/Money.php`
- Evidence: `return (float) $this->amount;`

### A.640 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.814`
- Previous baseline ID (older): `A.341`
- File: `app/ValueObjects/Money.php`
- Evidence: `return number_format((float) $this->amount, $decimals).' '.$this->currency;`

### A.641 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.97`
- File: `resources/views/livewire/accounting/journal-entries/form.blade.php`
- Line (v35): `8`
- Evidence: `{{ __('Create double-entry journal entries') }}`

### A.642 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.826`
- Previous baseline ID (older): `A.343`
- File: `resources/views/livewire/admin/dashboard.blade.php`
- Evidence: `'data' => $salesSeries->pluck('total')->map(fn ($v) => (float) $v)->toArray(),`

### A.643 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.98`
- File: `resources/views/livewire/hrm/employees/index.blade.php`
- Line (v35): `198`
- Evidence: `{{ number_format((float) $employee->salary, 2) }}`

### A.644 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.99`
- File: `resources/views/livewire/hrm/payroll/index.blade.php`
- Line (v35): `86`
- Evidence: `{{ number_format((float) $row->basic, 2) }}`

### A.645 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.100`
- File: `resources/views/livewire/hrm/payroll/index.blade.php`
- Line (v35): `89`
- Evidence: `{{ number_format((float) $row->allowances, 2) }}`

### A.646 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.101`
- File: `resources/views/livewire/hrm/payroll/index.blade.php`
- Line (v35): `92`
- Evidence: `{{ number_format((float) $row->deductions, 2) }}`

### A.647 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.102`
- File: `resources/views/livewire/hrm/payroll/index.blade.php`
- Line (v35): `95`
- Evidence: `{{ number_format((float) $row->net, 2) }}`

### A.648 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.829`
- Previous baseline ID (older): `B.492`
- File: `resources/views/livewire/manufacturing/bills-of-materials/index.blade.php`
- Evidence: `<td>{{ number_format((float)$bom->quantity, 2) }}</td>`

### A.649 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.830`
- Previous baseline ID (older): `B.493`
- File: `resources/views/livewire/manufacturing/production-orders/index.blade.php`
- Evidence: `<td>{{ number_format((float)$order->quantity_planned, 2) }}</td>`

### A.650 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.831`
- Previous baseline ID (older): `B.494`
- File: `resources/views/livewire/manufacturing/production-orders/index.blade.php`
- Evidence: `<td>{{ number_format((float)$order->quantity_produced, 2) }}</td>`

### A.651 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.832`
- Previous baseline ID (older): `B.495`
- File: `resources/views/livewire/manufacturing/work-centers/index.blade.php`
- Evidence: `<td>{{ number_format((float)$workCenter->cost_per_hour, 2) }}</td>`

### A.652 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.103`
- File: `resources/views/livewire/manufacturing/work-centers/index.blade.php`
- Line (v35): `147`
- Evidence: `<td>{{ number_format((float)$workCenter->capacity_per_hour, 2) }}</td>`

### A.653 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.833`
- Previous baseline ID (older): `A.344`
- File: `resources/views/livewire/purchases/returns/index.blade.php`
- Evidence: `<td class="font-mono text-orange-600">{{ number_format((float)$return->total, 2) }}</td>`

### A.654 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.834`
- Previous baseline ID (older): `A.345`
- File: `resources/views/livewire/purchases/returns/index.blade.php`
- Evidence: `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedPurchase->grand_total, 2) }}</p>`

### A.655 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.837`
- Previous baseline ID (older): `B.497`
- File: `resources/views/livewire/purchases/returns/index.blade.php`
- Evidence: `{{ number_format((float)$purchase->grand_total, 2) }}`

### A.656 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.104`
- File: `resources/views/livewire/rental/contracts/index.blade.php`
- Line (v35): `96`
- Evidence: `{{ number_format((float) $row->rent, 2) }}`

### A.657 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.105`
- File: `resources/views/livewire/rental/units/index.blade.php`
- Line (v35): `84`
- Evidence: `{{ number_format((float) $unit->rent, 2) }}`

### A.658 — Medium — Finance/Precision — Potential financial precision bug (float usage)
- Baseline ID (v35): `B.106`
- File: `resources/views/livewire/rental/units/index.blade.php`
- Line (v35): `87`
- Evidence: `{{ number_format((float) $unit->deposit, 2) }}`

### A.659 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.838`
- Previous baseline ID (older): `A.346`
- File: `resources/views/livewire/sales/returns/index.blade.php`
- Evidence: `<td class="font-mono text-red-600">{{ number_format((float)$return->total, 2) }}</td>`

### A.660 — Medium — Finance/Precision — Money/amount cast to float (rounding drift risk)
- Baseline ID (v35): `A.839`
- Previous baseline ID (older): `A.347`
- File: `resources/views/livewire/sales/returns/index.blade.php`
- Evidence: `<p class="text-sm"><strong>{{ __('Total') }}:</strong> {{ number_format((float)$selectedSale->grand_total, 2) }}</p>`

### A.661 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.842`
- Previous baseline ID (older): `B.500`
- File: `resources/views/livewire/sales/returns/index.blade.php`
- Evidence: `{{ number_format((float)$sale->grand_total, 2) }}`

### A.662 — Medium — Finance/Precision — Float cast in money/qty context (rounding drift risk)
- Baseline ID (v35): `A.844`
- Previous baseline ID (older): `B.503`
- File: `resources/views/livewire/shared/dynamic-table.blade.php`
- Evidence: `<span class="font-medium">{{ $currency }}{{ number_format((float)$value, 2) }}</span>`

### A.663 — Medium — Logic/Files — Local disk URL generation may fail
- Baseline ID (v35): `A.111`
- Previous baseline ID (older): `A.76`
- File: `app/Http/Controllers/Branch/ProductController.php`
- Evidence: `'url' => Storage::disk('local')->url($path),`

### A.664 — Medium — Perf/Security — Loads full file into memory (Storage::get / file_get_contents)
- Baseline ID (v35): `A.78`
- Previous baseline ID (older): `B.18`
- File: `app/Console/Commands/AuditViewButtons.php`
- Evidence: `$content = file_get_contents($filePath);`

### A.665 — Medium — Perf/Security — Loads entire file into memory (Storage::get)
- Baseline ID (v35): `A.138`
- Previous baseline ID (older): `A.89`
- File: `app/Http/Controllers/Files/UploadController.php`
- Evidence: `$content = $storage->get($path);`

### A.666 — Medium — Perf/Security — Loads full file into memory (Storage::get / file_get_contents)
- Baseline ID (v35): `A.348`
- Previous baseline ID (older): `B.175`
- File: `app/Livewire/Shared/DynamicForm.php`
- Evidence: `$content = file_get_contents($file->getRealPath());`

### A.667 — Medium — Perf/Security — Loads entire file into memory (Storage::get)
- Baseline ID (v35): `A.480`
- Previous baseline ID (older): `A.217`
- File: `app/Services/DiagnosticsService.php`
- Evidence: `$retrieved = Storage::disk($disk)->get($filename);`

### A.668 — Medium — Perf/Security — Loads full file into memory (Storage::get / file_get_contents)
- Baseline ID (v35): `A.523`
- Previous baseline ID (older): `B.298`
- File: `app/Services/ImageOptimizationService.php`
- Evidence: `Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));`

### A.669 — Medium — Perf/Security — Loads full file into memory (Storage::get / file_get_contents)
- Baseline ID (v35): `A.524`
- Previous baseline ID (older): `B.296`
- File: `app/Services/ImageOptimizationService.php`
- Evidence: `Storage::disk($disk)->put($path, file_get_contents($tempPath));`

### A.670 — Medium — Perf/Security — Loads full file into memory (Storage::get / file_get_contents)
- Baseline ID (v35): `A.525`
- Previous baseline ID (older): `B.297`
- File: `app/Services/ImageOptimizationService.php`
- Evidence: `Storage::disk($disk)->put($thumbnailPath, file_get_contents($tempPath));`

### A.671 — Medium — Security/Auth — Token accepted via query/body (leak risk)
- Baseline ID (v35): `A.139`
- Previous baseline ID (older): `A.90`
- File: `app/Http/Middleware/AuthenticateStoreToken.php`
- Evidence: `return $request->query('api_token') ?? $request->input('api_token');`

### A.672 — Medium — Security/Auth — api_token used in request/query flow (leak risk)
- Baseline ID (v35): `B.42`
- File: `app/Models/Traits/EnhancedAuditLogging.php`
- Line (v35): `111`
- Evidence: `'api_token',`

### A.673 — Medium — Security/XSS — Blade unescaped output ({!! !!})
- Baseline ID (v35): `A.817`
- Previous baseline ID (older): `B.481`
- File: `resources/views/components/form/input.blade.php`
- Evidence: `{!! sanitize_svg_icon($icon) !!}`

### A.674 — Medium — Security/XSS — Blade unescaped output ({!! !!})
- Baseline ID (v35): `A.819`
- Previous baseline ID (older): `B.483`
- File: `resources/views/components/ui/button.blade.php`
- Evidence: `{!! sanitize_svg_icon($icon) !!}`

### A.675 — Medium — Security/XSS — Blade unescaped output ({!! !!})
- Baseline ID (v35): `A.821`
- Previous baseline ID (older): `B.484`
- File: `resources/views/components/ui/card.blade.php`
- Evidence: `{!! sanitize_svg_icon($icon) !!}`

### A.676 — Medium — Security/XSS — Blade unescaped output ({!! !!})
- Baseline ID (v35): `A.822`
- Previous baseline ID (older): `B.486`
- File: `resources/views/components/ui/empty-state.blade.php`
- Evidence: `{!! sanitize_svg_icon($displayIcon) !!}`

### A.677 — Medium — Security/XSS — Blade unescaped output ({!! !!})
- Baseline ID (v35): `A.824`
- Previous baseline ID (older): `B.487`
- File: `resources/views/components/ui/form/input.blade.php`
- Evidence: `{!! sanitize_svg_icon($icon) !!}`

### A.678 — Medium — Security/XSS — Blade unescaped output ({!! !!})
- Baseline ID (v35): `A.825`
- Previous baseline ID (older): `B.489`
- File: `resources/views/components/ui/page-header.blade.php`
- Evidence: `{!! sanitize_svg_icon($icon) !!}`

---

## B) New bugs detected in v36

### B.1 — High — Security/SQL — DB::raw constructed from variable expression (needs whitelist/binding)
- File: `app/Services/Analytics/ProfitMarginAnalysisService.php`
- Line (v36): `167`
- Evidence: `DB::raw("{$periodExpr} as period"),`

### B.2 — High — Security/SQL — DB::raw constructed from variable expression (needs whitelist/binding)
- File: `app/Services/Analytics/ProfitMarginAnalysisService.php`
- Line (v36): `175`
- Evidence: `->groupBy(DB::raw($periodExpr))`

### B.3 — High — Security/SQL — DB::raw constructed from variable expression (needs whitelist/binding)
- File: `app/Services/Analytics/SalesForecastingService.php`
- Line (v36): `77`
- Evidence: `DB::raw("{$periodExpr} as period"),`

### B.4 — High — Security/SQL — DB::raw constructed from variable expression (needs whitelist/binding)
- File: `app/Services/Analytics/SalesForecastingService.php`
- Line (v36): `85`
- Evidence: `->groupBy(DB::raw($periodExpr))`

### B.5 — High — Security/SQL — DB::raw constructed from variable expression (needs whitelist/binding)
- File: `app/Services/Analytics/SalesForecastingService.php`
- Line (v36): `247`
- Evidence: `DB::raw("{$dateExpr} as period"),`

### B.6 — High — Security/SQL — DB::raw constructed from variable expression (needs whitelist/binding)
- File: `app/Services/Analytics/SalesForecastingService.php`
- Line (v36): `256`
- Evidence: `->groupBy(DB::raw($dateExpr))`
