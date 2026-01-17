<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalInvoice;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Services\Contracts\RentalServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class RentalService implements RentalServiceInterface
{
    use HandlesServiceErrors;

    public function createProperty(int $branchId, array $payload): Property
    {
        return $this->handleServiceOperation(
            callback: fn () => Property::create([
                'branch_id' => $branchId,
                'name' => $payload['name'],
                'address' => $payload['address'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ]),
            operation: 'createProperty',
            context: ['branch_id' => $branchId, 'payload' => $payload]
        );
    }

    public function createUnit(int $propertyId, array $payload): RentalUnit
    {
        return $this->handleServiceOperation(
            callback: fn () => RentalUnit::create([
                'property_id' => $propertyId,
                'code' => $payload['code'],
                'type' => $payload['type'] ?? $payload['unit_type'] ?? null,
                'status' => $payload['status'] ?? 'available',
                'rent' => $payload['rent'] ?? $payload['monthly_rent'] ?? 0,
                'deposit' => $payload['deposit'] ?? 0,
            ]),
            operation: 'createUnit',
            context: ['property_id' => $propertyId, 'payload' => $payload]
        );
    }

    public function setUnitStatus(int $unitId, string $status): RentalUnit
    {
        return $this->handleServiceOperation(
            callback: function () use ($unitId, $status) {
                $u = RentalUnit::findOrFail($unitId);
                $u->status = $status;
                $u->save();

                return $u;
            },
            operation: 'setUnitStatus',
            context: ['unit_id' => $unitId, 'status' => $status]
        );
    }

    public function createTenant(array $payload, ?int $branchId = null): Tenant
    {
        return $this->handleServiceOperation(
            callback: fn () => Tenant::create([
                'branch_id' => $branchId ?? auth()->user()?->branch_id ?? null,
                'name' => $payload['name'],
                'phone' => $payload['phone'] ?? null,
                'email' => $payload['email'] ?? null,
            ]),
            operation: 'createTenant',
            context: ['payload' => $payload, 'branch_id' => $branchId]
        );
    }

    public function archiveTenant(int $tenantId, ?int $branchId = null): Tenant
    {
        return $this->handleServiceOperation(
            callback: function () use ($tenantId, $branchId) {
                $query = Tenant::query();
                if ($branchId !== null) {
                    $query->where('branch_id', $branchId);
                }
                $t = $query->findOrFail($tenantId);
                $t->is_active = false;
                $t->save();

                return $t;
            },
            operation: 'archiveTenant',
            context: ['tenant_id' => $tenantId, 'branch_id' => $branchId]
        );
    }

    public function createContract(int $unitId, int $tenantId, array $payload, ?int $branchId = null): RentalContract
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($unitId, $tenantId, $payload, $branchId) {
                if ($branchId !== null) {
                    // Verify unit belongs to branch
                    RentalUnit::whereHas('property', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })->findOrFail($unitId);

                    // Verify tenant belongs to branch
                    Tenant::where('branch_id', $branchId)->findOrFail($tenantId);
                }

                // BUG FIX: Check unit availability with buffer time before creating contract
                $availability = $this->checkUnitAvailability(
                    $unitId,
                    $payload['start_date'],
                    $payload['end_date'],
                    null, // No contract to exclude
                    $branchId
                );

                if (! $availability['available']) {
                    throw new \Exception(
                        __('Cannot create contract: :message', ['message' => $availability['message']])
                    );
                }

                $c = RentalContract::create([
                    'branch_id' => $branchId ?? auth()->user()?->branch_id ?? null,
                    'unit_id' => $unitId,
                    'tenant_id' => $tenantId,
                    'start_date' => $payload['start_date'],
                    'end_date' => $payload['end_date'],
                    'rent' => (float) $payload['rent'],
                    'status' => 'active',
                ]);

                return $c;
            }),
            operation: 'createContract',
            context: ['unit_id' => $unitId, 'tenant_id' => $tenantId, 'payload' => $payload, 'branch_id' => $branchId]
        );
    }

    public function renewContract(int $contractId, array $payload, ?int $branchId = null): RentalContract
    {
        return $this->handleServiceOperation(
            callback: function () use ($contractId, $payload, $branchId) {
                $query = RentalContract::query();
                if ($branchId !== null) {
                    $query->where('branch_id', $branchId);
                }
                $c = $query->findOrFail($contractId);
                $c->end_date = $payload['end_date'];
                $c->rent = (float) $payload['rent'];
                $c->save();

                return $c;
            },
            operation: 'renewContract',
            context: ['contract_id' => $contractId, 'payload' => $payload, 'branch_id' => $branchId]
        );
    }

    public function terminateContract(int $contractId, ?int $branchId = null): RentalContract
    {
        return $this->handleServiceOperation(
            callback: function () use ($contractId, $branchId) {
                $query = RentalContract::query();
                if ($branchId !== null) {
                    $query->where('branch_id', $branchId);
                }
                $c = $query->findOrFail($contractId);
                $c->status = 'terminated';
                $c->save();

                return $c;
            },
            operation: 'terminateContract',
            context: ['contract_id' => $contractId, 'branch_id' => $branchId]
        );
    }

    public function runRecurring(?string $forDate = null): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($forDate) {
                $forDate = $forDate ?: now()->toDateString();
                dispatch_sync(new \App\Jobs\GenerateRecurringInvoicesJob($forDate));

                return 1;
            },
            operation: 'runRecurring',
            context: ['for_date' => $forDate]
        );
    }

    public function collectPayment(int $invoiceId, float $amount, ?string $method = 'cash', ?string $reference = null, ?int $branchId = null): RentalInvoice
    {
        return $this->handleServiceOperation(
            callback: function () use ($invoiceId, $amount, $method, $reference, $branchId) {
                $query = RentalInvoice::query();

                // Scope by branch if provided
                if ($branchId !== null) {
                    $query->whereHas('contract', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }

                $i = $query->findOrFail($invoiceId);

                // Get branch_id from contract, ensure it's set
                $invoiceBranchId = $i->contract->branch_id ?? null;
                abort_if(! $invoiceBranchId, 422, __('Branch context is required'));

                // Validate payment amount
                $remainingDue = max(0, ($i->amount ?? 0) - ($i->paid_total ?? 0));
                if ($amount <= 0) {
                    abort(422, __('Payment amount must be positive'));
                }
                if ($amount > $remainingDue) {
                    abort(422, __('Payment amount (:amount) exceeds remaining due (:due)', [
                        'amount' => number_format($amount, 2),
                        'due' => number_format($remainingDue, 2),
                    ]));
                }

                // Create payment record
                \App\Models\RentalPayment::create([
                    'invoice_id' => $i->id,
                    'contract_id' => $i->contract_id,
                    'branch_id' => $invoiceBranchId,
                    'amount' => $amount,
                    'method' => $method,
                    'reference' => $reference,
                    'paid_at' => now(),
                    // V33-CRIT-02 FIX: Use actual_user_id() for correct audit attribution during impersonation
                    'created_by' => actual_user_id(),
                ]);

                // Use bcmath for precise payment tracking
                $newPaidTotal = bcadd((string) ($i->paid_total ?? 0), (string) $amount, 2);
                $i->paid_total = (float) $newPaidTotal;
                $i->status = bccomp($newPaidTotal, (string) $i->amount, 2) >= 0 ? 'paid' : 'unpaid';
                $i->save();

                return $i;
            },
            operation: 'collectPayment',
            context: ['invoice_id' => $invoiceId, 'amount' => $amount, 'method' => $method, 'branch_id' => $branchId]
        );
    }

    public function applyPenalty(int $invoiceId, float $penalty, ?int $branchId = null): RentalInvoice
    {
        return $this->handleServiceOperation(
            callback: function () use ($invoiceId, $penalty, $branchId) {
                $query = RentalInvoice::query();

                // Scope by branch if provided
                if ($branchId !== null) {
                    $query->whereHas('contract', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }

                $i = $query->findOrFail($invoiceId);
                // Use bcmath for precise penalty addition
                $penaltyToAdd = bccomp((string) $penalty, '0', 2) > 0 ? $penalty : 0;
                $newAmount = bcadd((string) $i->amount, (string) $penaltyToAdd, 2);
                $i->amount = (float) $newAmount;
                $i->save();

                return $i;
            },
            operation: 'applyPenalty',
            context: ['invoice_id' => $invoiceId, 'penalty' => $penalty, 'branch_id' => $branchId]
        );
    }

    /**
     * Generate recurring invoices for active contracts
     */
    public function generateRecurringInvoicesForMonth(?int $branchId = null, ?\Carbon\Carbon $forMonth = null): array
    {
        $forMonth = $forMonth ?? now();
        $period = $forMonth->format('Y-m');

        $query = RentalContract::where('status', 'active')
            ->where('start_date', '<=', $forMonth->copy()->endOfMonth())
            ->where(function ($q) use ($forMonth) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $forMonth->copy()->startOfMonth());
            })
            ->with(['unit', 'tenant', 'rentalPeriod']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $contracts = $query->get();
        $generated = [];
        $skipped = [];
        $errors = [];

        foreach ($contracts as $contract) {
            try {
                // Check if invoice already exists for this period
                $existingInvoice = RentalInvoice::where('contract_id', $contract->id)
                    ->where('period', $period)
                    ->first();

                if ($existingInvoice) {
                    $skipped[] = [
                        'contract_id' => $contract->id,
                        'reason' => 'Invoice already exists for this period',
                        'invoice_id' => $existingInvoice->id,
                    ];

                    continue;
                }

                // Generate invoice code and create invoice atomically to prevent race conditions
                $invoice = DB::transaction(function () use ($contract, $period, $forMonth) {
                    $lastInvoice = RentalInvoice::lockForUpdate()->orderBy('id', 'desc')->first();
                    $nextNumber = $lastInvoice ? (intval(substr($lastInvoice->code, -6)) + 1) : 1;
                    $code = 'RI-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

                    // Calculate due date (typically start of month + grace period)
                    $dueDate = $forMonth->copy()->startOfMonth()->addDays(7);

                    // Create invoice
                    return RentalInvoice::create([
                        'contract_id' => $contract->id,
                        'code' => $code,
                        'period' => $period,
                        'due_date' => $dueDate,
                        'amount' => $contract->rent,
                        'status' => 'pending',
                    ]);
                });

                $generated[] = $invoice;
            } catch (\Exception $e) {
                $errors[] = [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_contracts' => $contracts->count(),
            'success_count' => count($generated),
            'skipped_count' => count($skipped),
            'error_count' => count($errors),
        ];
    }

    /**
     * Get occupancy statistics for a branch
     */
    public function getOccupancyStatistics(?int $branchId = null): array
    {
        $query = RentalUnit::query();

        if ($branchId) {
            $query->whereHas('property', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_units,
            COUNT(CASE WHEN status = ? THEN 1 END) as occupied_units,
            COUNT(CASE WHEN status = ? THEN 1 END) as vacant_units,
            COUNT(CASE WHEN status = ? THEN 1 END) as maintenance_units
        ', ['occupied', 'available', 'maintenance'])
            ->first();

        $totalUnits = $stats->total_units ?? 0;
        $occupiedUnits = $stats->occupied_units ?? 0;

        // Use bcmath for occupancy rate calculation
        $occupancyRate = $totalUnits > 0
            ? (float) bcmul(bcdiv((string) $occupiedUnits, (string) $totalUnits, 4), '100', 2)
            : 0;

        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $stats->vacant_units ?? 0,
            'maintenance_units' => $stats->maintenance_units ?? 0,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    /**
     * Get contracts expiring soon
     */
    public function getExpiringContracts(?int $branchId = null, int $daysAhead = 30): array
    {
        $today = now()->toDateString();
        $futureDate = now()->addDays($daysAhead)->toDateString();

        $query = RentalContract::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $futureDate])
            ->with(['unit', 'tenant', 'rentalPeriod'])
            ->orderBy('end_date');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $contracts = $query->get();

        return $contracts->map(function ($contract) {
            $daysRemaining = now()->diffInDays($contract->end_date, false);

            return [
                'contract_id' => $contract->id,
                'unit' => $contract->unit->name ?? '',
                'tenant' => $contract->tenant->name ?? '',
                'end_date' => $contract->end_date->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
                'urgency' => $this->getExpiryUrgency($daysRemaining),
                'rent' => $contract->rent,
            ];
        })->toArray();
    }

    /**
     * Get urgency level based on days remaining
     */
    private function getExpiryUrgency(int $daysRemaining): string
    {
        if ($daysRemaining <= 7) {
            return 'critical';
        } elseif ($daysRemaining <= 14) {
            return 'high';
        } elseif ($daysRemaining <= 30) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices(?int $branchId = null): array
    {
        $today = now()->toDateString();

        $query = RentalInvoice::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->with(['contract.unit', 'contract.tenant'])
            ->orderBy('due_date');

        if ($branchId) {
            $query->whereHas('contract', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $invoices = $query->get();

        return $invoices->map(function ($invoice) {
            $daysOverdue = $invoice->due_date->diffInDays(now(), false);

            return [
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->code,
                'contract_id' => $invoice->contract_id,
                'unit' => $invoice->contract->unit->name ?? '',
                'tenant' => $invoice->contract->tenant->name ?? '',
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'amount' => $invoice->amount,
                'days_overdue' => $daysOverdue,
                'period' => $invoice->period,
            ];
        })->toArray();
    }

    /**
     * Get rental revenue statistics
     */
    public function getRevenueStatistics(?int $branchId = null, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $query = RentalInvoice::whereBetween('due_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->whereHas('contract', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_invoices,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as collected_amount,
            SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as pending_amount,
            COUNT(CASE WHEN status = ? THEN 1 END) as collected_count,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending_count
        ', ['paid', 'pending', 'paid', 'pending'])
            ->first();

        $totalAmount = $stats->total_amount ?? 0;
        $collectedAmount = $stats->collected_amount ?? 0;

        // Use bcmath for collection rate calculation
        $collectionRate = $totalAmount > 0
            ? (float) bcmul(bcdiv((string) $collectedAmount, (string) $totalAmount, 4), '100', 2)
            : 0;

        return [
            'total_invoices' => $stats->total_invoices ?? 0,
            'total_amount' => $totalAmount,
            'collected_amount' => $collectedAmount,
            'pending_amount' => $stats->pending_amount ?? 0,
            'collected_count' => $stats->collected_count ?? 0,
            'pending_count' => $stats->pending_count ?? 0,
            'collection_rate' => $collectionRate,
        ];
    }

    /**
     * Check if a rental unit is available for a given date range.
     *
     * BUG FIX: Includes buffer time (turnaround hours) between rentals.
     * This addresses the issue where a unit could be booked immediately
     * after another rental ends, without time for maintenance/cleaning.
     *
     * @param int $unitId The rental unit ID
     * @param string $startDate Requested start date (Y-m-d or datetime)
     * @param string $endDate Requested end date (Y-m-d or datetime)
     * @param int|null $excludeContractId Contract ID to exclude (for extensions)
     * @param int|null $branchId Branch ID for branch-scoped queries
     * @return array ['available' => bool, 'conflicts' => array, 'message' => string|null]
     */
    public function checkUnitAvailability(
        int $unitId,
        string $startDate,
        string $endDate,
        ?int $excludeContractId = null,
        ?int $branchId = null
    ): array {
        return $this->handleServiceOperation(
            callback: function () use ($unitId, $startDate, $endDate, $excludeContractId, $branchId) {
                // Get buffer hours from settings (default 4 hours for cleaning/maintenance)
                $bufferHours = (int) config('rental.buffer_hours', setting('rental.buffer_hours', 4));

                $requestedStart = \Carbon\Carbon::parse($startDate);
                $requestedEnd = \Carbon\Carbon::parse($endDate);

                // Query for conflicting contracts
                $query = RentalContract::where('unit_id', $unitId)
                    ->where('status', 'active');

                if ($excludeContractId !== null) {
                    $query->where('id', '!=', $excludeContractId);
                }

                if ($branchId !== null) {
                    $query->where('branch_id', $branchId);
                }

                // Check for overlapping contracts with buffer time
                // A conflict exists if:
                // - Existing contract end_date + buffer overlaps with requested start
                // - Existing contract start_date - buffer overlaps with requested end
                //
                // We apply buffer by adjusting our requested dates instead of the stored dates.
                // This provides better database portability (works with SQLite, MySQL, PostgreSQL)
                // by avoiding database-specific date functions in queries.
                $requestedStartWithBuffer = $requestedStart->copy()->subHours($bufferHours);
                $requestedEndWithBuffer = $requestedEnd->copy()->addHours($bufferHours);

                $conflicts = $query->where(function ($q) use ($requestedStartWithBuffer, $requestedEndWithBuffer) {
                    $q->where(function ($inner) use ($requestedStartWithBuffer, $requestedEndWithBuffer) {
                        // Check for overlap: existing.end > requested.start - buffer AND existing.start < requested.end + buffer
                        $inner->where('end_date', '>', $requestedStartWithBuffer)
                              ->where('start_date', '<', $requestedEndWithBuffer);
                    });
                })->get(['id', 'start_date', 'end_date', 'tenant_id']);

                if ($conflicts->isEmpty()) {
                    return [
                        'available' => true,
                        'conflicts' => [],
                        'message' => null,
                        'buffer_hours' => $bufferHours,
                    ];
                }

                // Build conflict details
                $conflictDetails = $conflicts->map(function ($contract) use ($bufferHours) {
                    return [
                        'contract_id' => $contract->id,
                        'start_date' => $contract->start_date?->format('Y-m-d H:i'),
                        'end_date' => $contract->end_date?->format('Y-m-d H:i'),
                        'buffer_end' => $contract->end_date?->addHours($bufferHours)->format('Y-m-d H:i'),
                    ];
                })->toArray();

                return [
                    'available' => false,
                    'conflicts' => $conflictDetails,
                    'message' => __('Unit is not available for the requested dates. A :hours-hour buffer is required between rentals for maintenance.', [
                        'hours' => $bufferHours,
                    ]),
                    'buffer_hours' => $bufferHours,
                ];
            },
            operation: 'checkUnitAvailability',
            context: [
                'unit_id' => $unitId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'exclude_contract_id' => $excludeContractId,
            ],
            defaultValue: ['available' => false, 'conflicts' => [], 'message' => 'Error checking availability']
        );
    }

    /**
     * Extend an existing rental contract.
     *
     * BUG FIX: Validates availability before extending to prevent double-booking.
     * The previous implementation allowed extensions without checking if another
     * tenant had already booked the unit for the extension period.
     *
     * @param int $contractId Contract ID to extend
     * @param string $newEndDate New end date
     * @param int|null $branchId Branch ID for branch-scoped queries
     * @return RentalContract Extended contract
     * @throws \Exception If extension conflicts with existing bookings
     */
    public function extendContract(int $contractId, string $newEndDate, ?int $branchId = null): RentalContract
    {
        return $this->handleServiceOperation(
            callback: function () use ($contractId, $newEndDate, $branchId) {
                $query = RentalContract::query();
                if ($branchId !== null) {
                    $query->where('branch_id', $branchId);
                }
                $contract = $query->findOrFail($contractId);

                // BUG FIX: Check availability for the extension period
                // The extension period is from the current end_date to the new end_date
                $extensionStart = $contract->end_date->format('Y-m-d');
                $extensionEnd = \Carbon\Carbon::parse($newEndDate)->format('Y-m-d');

                // Validate the new end date is actually later than current
                if ($extensionEnd <= $extensionStart) {
                    throw new \InvalidArgumentException(
                        __('New end date must be after current end date (:current)', [
                            'current' => $extensionStart,
                        ])
                    );
                }

                // Check availability for the extension period, excluding current contract
                $availability = $this->checkUnitAvailability(
                    $contract->unit_id,
                    $extensionStart,
                    $extensionEnd,
                    $contract->id, // Exclude current contract from conflict check
                    $branchId
                );

                if (! $availability['available']) {
                    throw new \Exception(
                        __('Cannot extend contract: :message', ['message' => $availability['message']])
                    );
                }

                // Update the contract with the new end date
                $contract->end_date = $newEndDate;
                $contract->save();

                return $contract;
            },
            operation: 'extendContract',
            context: ['contract_id' => $contractId, 'new_end_date' => $newEndDate, 'branch_id' => $branchId]
        );
    }
}
