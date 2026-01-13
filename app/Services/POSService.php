<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PosSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Tax;
use App\Models\User;
use App\Rules\ValidPriceOverride;
use App\Services\Contracts\POSServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class POSService implements POSServiceInterface
{
    use HandlesServiceErrors;

    public function __construct(
        protected DiscountService $discounts
    ) {}

    public function checkout(array $payload): Sale
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($payload) {
                $items = $payload['items'] ?? [];
                abort_if(empty($items), 422, 'No items');

                $user = auth()->user();
                $branchId = $payload['branch_id'] ?? request()->attributes->get('branch_id');
                // Primary: client_uuid, Fallback: client_sale_uuid for backward compatibility
                $clientUuid = $payload['client_uuid'] ?? $payload['client_sale_uuid'] ?? null;

                // Validate branch ID is present
                abort_if(! $branchId, 422, __('Branch context is required'));

                // V6-CRITICAL-02 FIX: Require warehouse_id for all stock-moving operations
                $warehouseId = $payload['warehouse_id'] ?? null;
                abort_if(! $warehouseId, 422, __('Warehouse is required for POS checkout'));

                // Idempotency check: If client_uuid provided and sale exists, return existing sale
                // FIX U-03: Scope by branch to prevent cross-branch data collision
                if ($clientUuid) {
                    $existingSale = Sale::where('client_uuid', $clientUuid)
                        ->where('branch_id', $branchId)
                        ->first();
                    if ($existingSale) {
                        return $existingSale->load(['items.product', 'payments', 'customer']);
                    }
                }

                // Validate POS session exists and is open
                if (($payload['channel'] ?? 'pos') === 'pos') {
                    $activeSession = PosSession::where('branch_id', $branchId)
                        ->where('user_id', $user?->id)
                        ->where('status', PosSession::STATUS_OPEN)
                        ->first();

                    if (! $activeSession && config('pos.require_session', true)) {
                        abort(422, __('No active POS session. Please open a session first.'));
                    }
                }

                $sale = Sale::create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $payload['warehouse_id'] ?? null,
                    'customer_id' => $payload['customer_id'] ?? null,
                    'client_uuid' => $clientUuid,
                    'sale_date' => now()->toDateString(),
                    'status' => 'completed',
                    'channel' => $payload['channel'] ?? 'pos',
                    'currency' => $payload['currency'] ?? 'EGP',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'notes' => $payload['notes'] ?? null,
                    'created_by' => $user?->id,
                ]);

                // Use strings for bcmath precision
                $subtotal = '0';
                $discountTotal = '0';
                $taxTotal = '0';

                $previousDailyDiscount = 0.0;
                if ($user && $user->daily_discount_limit !== null) {
                    $previousDailyDiscount = (float) Sale::where('created_by', $user->id)
                        ->whereDate('created_at', now()->toDateString())
                        ->sum('discount_amount');
                }

                // Lock all products at once to prevent performance issues and deadlocks
                $productIds = array_unique(array_column($items, 'product_id'));
                $products = Product::withTrashed()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $it) {
                    // Validate quantity is positive (prevent negative quantity exploit)
                    $qty = (float) ($it['qty'] ?? 1);
                    if ($qty <= 0) {
                        abort(422, __('Quantity must be positive. Received: :qty', ['qty' => $qty]));
                    }

                    // Check if product exists and is not soft-deleted (prevent zombie products in cart)
                    $product = $products->get($it['product_id']);

                    if (! $product) {
                        abort(422, __('Product not found.'));
                    }

                    if ($product->trashed()) {
                        abort(422, __('Product ":product" is no longer available for sale.', ['product' => $product->name]));
                    }

                    $price = isset($it['price']) ? (float) $it['price'] : (float) ($product->default_price ?? 0);

                    // Check stock availability for physical products (not services)
                    // Respect the allow_negative_stock setting from system configuration
                    $allowNegativeStock = (bool) setting('pos.allow_negative_stock', false);
                    if (! $allowNegativeStock && $product->type !== 'service' && $product->product_type !== 'service') {
                        $warehouseId = $payload['warehouse_id'] ?? null;
                        $availableStock = StockService::getCurrentStock($product->getKey(), $warehouseId);
                        if ($availableStock < $qty) {
                            abort(422, __('Insufficient stock for :product. Available: :available, Requested: :requested', [
                                'product' => $product->name,
                                'available' => number_format($availableStock, 2),
                                'requested' => number_format($qty, 2),
                            ]));
                        }
                    }

                    if ($user && ! $user->can_modify_price && abs($price - (float) ($product->default_price ?? 0)) > 0.001) {
                        abort(422, __('You are not allowed to modify prices'));
                    }

                    (new ValidPriceOverride((float) $product->cost, 0.0))->validate('price', $price, function ($m) {
                        abort(422, $m);
                    });

                    $itemDiscountPercent = (float) ($it['discount'] ?? 0);

                    // Check system-wide max discount setting first
                    $systemMaxDiscount = (float) setting('pos.max_discount_percent', 100);
                    if ($itemDiscountPercent > $systemMaxDiscount) {
                        abort(422, __('Discount exceeds the system maximum of :max%', ['max' => $systemMaxDiscount]));
                    }

                    // Then check user-specific limit (can be more restrictive)
                    if ($user && $user->max_discount_percent !== null && $itemDiscountPercent > $user->max_discount_percent) {
                        abort(422, __('Discount exceeds your maximum allowed discount of :max%', ['max' => $user->max_discount_percent]));
                    }

                    $lineDisc = $this->discounts->lineTotal($qty, $price, $itemDiscountPercent, (bool) ($it['percent'] ?? true));

                    if ($user && $user->daily_discount_limit !== null && $lineDisc > 0) {
                        $totalUsedWithThisLine = $previousDailyDiscount + $discountTotal + $lineDisc;
                        if ($totalUsedWithThisLine > $user->daily_discount_limit) {
                            abort(422, __('Daily discount limit of :limit EGP exceeded. Already used: :used EGP, this transaction adds: :add EGP', [
                                'limit' => number_format($user->daily_discount_limit, 2),
                                'used' => number_format($previousDailyDiscount, 2),
                                'add' => number_format($discountTotal + $lineDisc, 2),
                            ]));
                        }
                    }
                    // Use bcmath for precise line calculations
                    $lineSub = bcmul((string) $qty, (string) $price, 4);
                    $lineTax = '0';

                    // BUG FIX #5: Apply line-level tax rounding for compliance with e-invoicing regulations
                    // V7-MEDIUM-U10 FIX: Use bcround for proper half-up rounding instead of truncation
                    if (! empty($it['tax_id']) && class_exists(Tax::class)) {
                        $tax = Tax::find($it['tax_id']);
                        if ($tax) {
                            $taxRate = bcdiv((string) $tax->rate, '100', 6);
                            $taxableAmount = bcsub($lineSub, (string) $lineDisc, 4);
                            // Round tax at line level using proper half-up rounding
                            $lineTax = bcround(bcmul($taxableAmount, $taxRate, 4), 2);
                        }
                    }

                    $subtotal = bcadd((string) $subtotal, $lineSub, 4);
                    $discountTotal = bcadd((string) $discountTotal, (string) $lineDisc, 4);
                    // Sum line-level rounded taxes
                    $taxTotal = bcadd((string) $taxTotal, $lineTax, 2);

                    // Calculate line total with bcmath
                    $lineTotal = bcadd(bcsub($lineSub, (string) $lineDisc, 4), $lineTax, 4);

                    SaleItem::create([
                        'sale_id' => $sale->getKey(),
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $payload['warehouse_id'] ?? null,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'cost_price' => $product->cost ?? 0,
                        'discount_amount' => $lineDisc,
                        'tax_amount' => $lineTax,
                        'line_total' => (float) bcdiv($lineTotal, '1', 2),
                    ]);
                }

                // Use bcmath for grand total calculation
                $grandTotal = bcadd(bcsub((string) $subtotal, (string) $discountTotal, 4), (string) $taxTotal, 4);

                $sale->subtotal = (float) bcdiv((string) $subtotal, '1', 2);
                $sale->discount_amount = (float) bcdiv((string) $discountTotal, '1', 2);
                $sale->tax_amount = (float) bcdiv((string) $taxTotal, '1', 2);
                $sale->total_amount = (float) bcdiv($grandTotal, '1', 2);

                $payments = $payload['payments'] ?? [];
                $paidTotal = '0';

                if (! empty($payments)) {
                    foreach ($payments as $payment) {
                        $amount = (float) ($payment['amount'] ?? 0);
                        if ($amount <= 0) {
                            continue;
                        }

                        SalePayment::create([
                            'sale_id' => $sale->getKey(),
                            'payment_method' => $payment['method'] ?? 'cash',
                            'amount' => $amount,
                            'payment_date' => now()->toDateString(),
                            'currency' => $payment['currency'] ?? 'EGP',
                            'reference_number' => $payment['reference_no'] ?? null,
                            'card_last_four' => $payment['card_last_four'] ?? null,
                            'bank_name' => $payment['bank_name'] ?? null,
                            'cheque_number' => $payment['cheque_number'] ?? null,
                            'cheque_date' => $payment['cheque_date'] ?? null,
                            'notes' => $payment['notes'] ?? null,
                            'status' => 'completed',
                        ]);

                        $paidTotal = bcadd($paidTotal, (string) $amount, 2);
                    }
                } else {
                    SalePayment::create([
                        'sale_id' => $sale->getKey(),
                        'payment_method' => 'cash',
                        'amount' => (float) bcdiv($grandTotal, '1', 2),
                        'payment_date' => now()->toDateString(),
                        'currency' => 'EGP',
                        'status' => 'completed',
                    ]);
                    $paidTotal = $grandTotal;
                }

                // Use bcmath for payment calculations
                $sale->paid_amount = (float) bcdiv($paidTotal, '1', 2);
                $sale->payment_status = bccomp($paidTotal, $grandTotal, 2) >= 0 ? 'paid' : 'partial';
                $sale->save();

                // Generate accounting journal entry for the sale
                // This ensures sales are "on the books" per requirement D
                try {
                    $accountingService = app(AccountingService::class);
                    $accountingService->generateSaleJournalEntry($sale);
                } catch (\Throwable $e) {
                    // Log but don't fail the sale if accounting entry fails
                    // This can happen if accounts are not configured yet
                    \Illuminate\Support\Facades\Log::warning('Failed to generate journal entry for sale', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                event(new \App\Events\SaleCompleted($sale));

                return $sale->load(['items.product', 'payments', 'customer']);
            }),
            operation: 'checkout',
            context: ['payload' => $payload]
        );
    }

    public function openSession(int $branchId, int $userId, float $openingCash = 0): PosSession
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $userId, $openingCash) {
                $existingSession = PosSession::where('branch_id', $branchId)
                    ->where('user_id', $userId)
                    ->where('status', PosSession::STATUS_OPEN)
                    ->first();

                if ($existingSession) {
                    return $existingSession;
                }

                // Generate session number: POS-{YYYYMMDD}-{sequence}
                $date = now()->format('Ymd');
                $lastSession = PosSession::where('session_number', 'like', "POS-{$date}-%")
                    ->orderByDesc('id')
                    ->first();
                $sequence = $lastSession ? ((int) substr($lastSession->session_number, -4)) + 1 : 1;
                $sessionNumber = sprintf('POS-%s-%04d', $date, $sequence);

                return PosSession::create([
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'session_number' => $sessionNumber,
                    'opening_cash' => $openingCash,
                    'status' => PosSession::STATUS_OPEN,
                    'opened_at' => now(),
                ]);
            },
            operation: 'openSession',
            context: ['branch_id' => $branchId, 'user_id' => $userId, 'opening_cash' => $openingCash]
        );
    }

    public function closeSession(int $sessionId, float $closingCash, ?string $notes = null): PosSession
    {
        return $this->handleServiceOperation(
            callback: function () use ($sessionId, $closingCash, $notes) {
                $session = PosSession::findOrFail($sessionId);

                if (! $session->isOpen()) {
                    abort(422, __('Session is already closed'));
                }

                $salesQuery = Sale::where('branch_id', $session->branch_id)
                    ->where('created_by', $session->user_id)
                    ->where('created_at', '>=', $session->opened_at)
                    ->where('status', '!=', 'cancelled');

                $totalSales = (float) $salesQuery->sum('total_amount');
                $totalTransactions = $salesQuery->count();

                $paymentSummary = SalePayment::whereIn('sale_id', $salesQuery->pluck('id'))
                    ->selectRaw('payment_method, SUM(amount) as total')
                    ->groupBy('payment_method')
                    ->pluck('total', 'payment_method')
                    ->toArray();

                $expectedCash = $session->opening_cash + ($paymentSummary['cash'] ?? 0);
                $cashDifference = $closingCash - $expectedCash;

                $session->update([
                    'closing_cash' => $closingCash,
                    'expected_cash' => $expectedCash,
                    'cash_difference' => $cashDifference,
                    'payment_summary' => $paymentSummary,
                    'total_transactions' => $totalTransactions,
                    'total_sales' => $totalSales,
                    'total_refunds' => 0,
                    'status' => PosSession::STATUS_CLOSED,
                    'closed_at' => now(),
                    'closing_notes' => $notes,
                    'closed_by' => auth()->id(),
                ]);

                return $session->fresh();
            },
            operation: 'closeSession',
            context: ['session_id' => $sessionId, 'closing_cash' => $closingCash]
        );
    }

    public function getCurrentSession(int $branchId, int $userId): ?PosSession
    {
        return PosSession::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
    }

    public function getSessionReport(int $sessionId): array
    {
        $session = PosSession::with(['user', 'branch', 'closedBy'])->findOrFail($sessionId);

        $sales = Sale::where('branch_id', $session->branch_id)
            ->where('created_by', $session->user_id)
            ->whereBetween('created_at', [$session->opened_at, $session->closed_at ?? now()])
            ->with(['items', 'payments', 'customer'])
            ->get();

        return [
            'session' => $session,
            'sales' => $sales,
            'summary' => [
                'total_transactions' => $sales->count(),
                'total_sales' => $sales->sum('total_amount'),
                'total_discount' => $sales->sum('discount_amount'),
                'total_tax' => $sales->sum('tax_amount'),
                'payment_breakdown' => $session->payment_summary ?? [],
                'opening_cash' => $session->opening_cash,
                'closing_cash' => $session->closing_cash,
                'expected_cash' => $session->expected_cash,
                'cash_difference' => $session->cash_difference,
            ],
        ];
    }

    public function validateDiscount(User $user, float $discountPercent): bool
    {
        return $discountPercent <= ($user->max_discount_percent ?? 100);
    }
}
