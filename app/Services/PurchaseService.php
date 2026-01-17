<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Services\Contracts\PurchaseServiceInterface;
use App\Traits\HandlesServiceErrors;
use App\Traits\HasRequestContext;
use Illuminate\Support\Facades\DB;

class PurchaseService implements PurchaseServiceInterface
{
    use HandlesServiceErrors;
    use HasRequestContext;

    protected function branchIdOrFail(): int
    {
        $branchId = $this->currentBranchId();

        if ($branchId === null) {
            throw new \InvalidArgumentException('Branch context is required for purchase operations.');
        }

        return $branchId;
    }

    protected function findBranchPurchaseOrFail(int $id): Purchase
    {
        $branchId = $this->branchIdOrFail();

        return Purchase::where('branch_id', $branchId)->findOrFail($id);
    }

    public function create(array $payload): Purchase
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($payload) {
                // Controller provides branch_id in payload after validation
                // Service validates it exists as defense-in-depth
                if (! isset($payload['branch_id'])) {
                    $branchId = $this->branchIdOrFail();
                } else {
                    $branchId = (int) $payload['branch_id'];
                }

                // V6-CRITICAL-02 FIX: Require warehouse_id for all stock-moving operations
                if (! isset($payload['warehouse_id']) || $payload['warehouse_id'] === null) {
                    throw new \InvalidArgumentException('Warehouse is required for purchase operations.');
                }
                $warehouseId = (int) $payload['warehouse_id'];

                $p = Purchase::create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'supplier_id' => $payload['supplier_id'] ?? null,
                    'status' => 'draft',
                    'purchase_date' => now()->toDateString(),
                    // Use correct migration column names
                    'subtotal' => 0, 'tax_amount' => 0, 'discount_amount' => 0, 'total_amount' => 0,
                    'paid_amount' => 0,
                ]);

                $subtotal = '0';
                $totalTax = '0';
                $totalDiscount = '0';

                foreach ($payload['items'] ?? [] as $it) {
                    // Skip invalid items without required fields
                    if (! isset($it['product_id']) || ! isset($it['qty'])) {
                        continue;
                    }

                    $qty = (float) $it['qty'];
                    // Accept price from multiple possible field names for API compatibility
                    $unitPrice = (float) ($it['unit_price'] ?? $it['price'] ?? 0);

                    // Critical ERP: Validate positive quantities and prices
                    if ($qty <= 0) {
                        throw new \InvalidArgumentException("Quantity must be positive for product {$it['product_id']}");
                    }

                    if ($unitPrice < 0) {
                        throw new \InvalidArgumentException("Unit price cannot be negative for product {$it['product_id']}");
                    }

                    // Use bcmath for precise calculation
                    $lineSub = bcmul((string) $qty, (string) $unitPrice, 4);

                    // Calculate line discount
                    $discountPercent = (float) ($it['discount_percent'] ?? 0);
                    $lineDiscount = '0';
                    if ($discountPercent > 0) {
                        $lineDiscount = bcmul($lineSub, bcdiv((string) $discountPercent, '100', 6), 4);
                    }

                    // Calculate line tax (on discounted amount)
                    $taxPercent = (float) ($it['tax_percent'] ?? 0);
                    $lineTax = (float) ($it['tax_amount'] ?? 0);
                    if ($lineTax <= 0 && $taxPercent > 0) {
                        $taxableAmount = bcsub($lineSub, $lineDiscount, 4);
                        $lineTax = (float) bcmul($taxableAmount, bcdiv((string) $taxPercent, '100', 6), 2);
                    }

                    // Calculate line total: (qty * price) - discount + tax
                    $lineTotal = bcadd(bcsub($lineSub, $lineDiscount, 4), (string) $lineTax, 2);

                    $subtotal = bcadd($subtotal, $lineSub, 4);
                    $totalTax = bcadd($totalTax, (string) $lineTax, 2);
                    $totalDiscount = bcadd($totalDiscount, $lineDiscount, 4);

                    // Get product name and SKU
                    $product = \App\Models\Product::find($it['product_id']);

                    PurchaseItem::create([
                        'purchase_id' => $p->getKey(),
                        'product_id' => $it['product_id'],
                        'product_name' => $product?->name ?? '',
                        'sku' => $product?->sku ?? null,
                        'quantity' => $qty,
                        'received_quantity' => 0,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $discountPercent,
                        'tax_percent' => $taxPercent,
                        'tax_amount' => $lineTax,
                        'line_total' => (float) $lineTotal,
                    ]);
                }

                // FIX U-04: Compute total_amount correctly with tax/shipping/discount
                // Get header-level shipping if provided
                $shippingAmount = (float) ($payload['shipping_amount'] ?? 0);

                // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
                $p->subtotal = (float) bcround($subtotal, 2);
                $p->tax_amount = (float) bcround($totalTax, 2);
                $p->discount_amount = (float) bcround($totalDiscount, 2);
                // total_amount = subtotal + tax + shipping - discount
                $p->total_amount = (float) bcround(
                    bcadd(
                        bcsub(bcadd($subtotal, $totalTax, 4), $totalDiscount, 4),
                        (string) $shippingAmount,
                        4
                    ),
                    2
                );

                // Critical ERP: Validate supplier minimum order value
                if ($p->supplier_id) {
                    $supplier = \App\Models\Supplier::find($p->supplier_id);
                    if ($supplier && $supplier->minimum_order_amount > 0) {
                        if ($p->total_amount < $supplier->minimum_order_amount) {
                            throw new \InvalidArgumentException(
                                "Order total ({$p->total_amount}) is below supplier minimum order value ({$supplier->minimum_order_amount})"
                            );
                        }
                    }
                }

                $p->save();

                return $p;
            }),
            operation: 'create',
            context: ['payload' => $payload]
        );
    }

    public function approve(int $id): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $p = $this->findBranchPurchaseOrFail($id);
                $p->status = 'confirmed';
                $p->approved_at = now();
                $p->save();

                return $p;
            },
            operation: 'approve',
            context: ['purchase_id' => $id]
        );
    }

    public function receive(int $id): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $p = $this->findBranchPurchaseOrFail($id);
                $p->status = 'received';
                $p->received_at = now();
                $p->save();
                event(new \App\Events\PurchaseReceived($p));

                return $p;
            },
            operation: 'receive',
            context: ['purchase_id' => $id]
        );
    }

    /**
     * STILL-V7-HIGH-U08 FIX: Pay method with proper payment entity creation
     */
    public function pay(int $id, float $amount, string $paymentMethod = 'cash', ?string $notes = null): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id, $amount, $paymentMethod, $notes) {
                return DB::transaction(function () use ($id, $amount, $paymentMethod, $notes) {
                    $p = $this->findBranchPurchaseOrFail($id);

                    // Validate payment amount
                    if ($amount <= 0) {
                        throw new \InvalidArgumentException('Payment amount must be positive');
                    }

                    // Use correct migration column names
                    $remainingDue = max(0, (float) $p->total_amount - (float) $p->paid_amount);
                    if ($amount > $remainingDue) {
                        throw new \InvalidArgumentException(sprintf(
                            'Payment amount (%.2f) exceeds remaining due (%.2f)',
                            $amount,
                            $remainingDue
                        ));
                    }

                    // STILL-V7-HIGH-U08 FIX: Create PurchasePayment record for audit trail
                    $lastPayment = PurchasePayment::where('purchase_id', $p->getKey())
                        ->lockForUpdate()
                        ->orderBy('id', 'desc')
                        ->first();

                    $paymentSeq = $lastPayment ? ($lastPayment->id % 100000) + 1 : 1;
                    $refNumber = 'PP-'.date('Ymd').'-'.str_pad((string) $paymentSeq, 5, '0', STR_PAD_LEFT);

                    PurchasePayment::create([
                        'purchase_id' => $p->getKey(),
                        'reference_number' => $refNumber,
                        'amount' => $amount,
                        'payment_method' => $paymentMethod,
                        'status' => 'completed',
                        'payment_date' => now()->toDateString(),
                        'currency' => setting('general.default_currency', 'EGP'),
                        'notes' => $notes,
                        // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                        'paid_by' => actual_user_id(),
                    ]);

                    // Critical ERP: Use bcmath for precise money calculations
                    $newPaidAmount = bcadd((string) $p->paid_amount, (string) $amount, 2);
                    $p->paid_amount = (float) $newPaidAmount;

                    // Update payment status
                    if ((float) $p->paid_amount >= (float) $p->total_amount) {
                        $p->payment_status = 'paid';
                    } elseif ((float) $p->paid_amount > 0) {
                        $p->payment_status = 'partial';
                    } else {
                        $p->payment_status = 'unpaid';
                    }
                    $p->save();

                    return $p;
                });
            },
            operation: 'pay',
            context: ['purchase_id' => $id, 'amount' => $amount, 'payment_method' => $paymentMethod]
        );
    }

    public function cancel(int $id): Purchase
    {
        return $this->handleServiceOperation(
            callback: function () use ($id) {
                $p = $this->findBranchPurchaseOrFail($id);

                // Prevent cancelling if already received or paid
                if ($p->status === 'received' || $p->status === 'completed') {
                    throw new \InvalidArgumentException('Cannot cancel a received purchase. Please create a return instead.');
                }
                if ($p->payment_status === 'paid' || (float) $p->paid_amount > 0) {
                    throw new \InvalidArgumentException('Cannot cancel a paid purchase. Please refund first.');
                }
                if ($p->status === 'cancelled') {
                    throw new \InvalidArgumentException('Purchase is already cancelled.');
                }

                $p->status = 'cancelled';
                $p->save();

                return $p;
            },
            operation: 'cancel',
            context: ['purchase_id' => $id]
        );
    }
}
