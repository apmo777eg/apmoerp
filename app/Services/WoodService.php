<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\WoodServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class WoodService implements WoodServiceInterface
{
    use HandlesServiceErrors;

    public function conversions(int $branchId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                return DB::table('wood_conversions')
                    ->where('branch_id', $branchId)
                    ->orderByDesc('id')->get()->map(fn ($r) => (array) $r)->all();
            },
            operation: 'conversions',
            context: ['branch_id' => $branchId],
            defaultValue: []
        );
    }

    public function createConversion(array $payload): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($payload) {
                return (int) DB::table('wood_conversions')->insertGetId([
                    'branch_id' => request()->attributes->get('branch_id'),
                    'input_uom' => $payload['input_uom'],
                    'input_qty' => $payload['input_qty'],
                    'output_uom' => $payload['output_uom'],
                    'output_qty' => $payload['output_qty'],
                    // V52-HIGH-02 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for quantities
                    'efficiency' => $this->efficiency(decimal_float($payload['input_qty'], 4), decimal_float($payload['output_qty'], 4)),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            },
            operation: 'createConversion',
            context: []
        );
    }

    public function recalc(int $conversionId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($conversionId) {
                $row = DB::table('wood_conversions')->find($conversionId);
                if (! $row) {
                    return;
                }
                $eff = $this->efficiency(decimal_float($row->input_qty), decimal_float($row->output_qty));
                DB::table('wood_conversions')->where('id', $conversionId)->update(['efficiency' => $eff, 'updated_at' => now()]);
            },
            operation: 'recalc',
            context: ['conversion_id' => $conversionId]
        );
    }

    public function listWaste(int $branchId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                return DB::table('wood_waste')->where('branch_id', $branchId)->orderByDesc('id')->get()->map(fn ($r) => (array) $r)->all();
            },
            operation: 'listWaste',
            context: ['branch_id' => $branchId],
            defaultValue: []
        );
    }

    public function storeWaste(array $payload): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($payload) {
                return (int) DB::table('wood_waste')->insertGetId([
                    'branch_id' => request()->attributes->get('branch_id'),
                    'type' => $payload['type'] ?? 'general',
                    'qty' => decimal_float($payload['qty'] ?? 0, 4),
                    'uom' => $payload['uom'] ?? 'kg',
                    'notes' => $payload['notes'] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            },
            operation: 'storeWaste',
            context: []
        );
    }

    protected function efficiency(float $in, float $out): float
    {
        if ($in <= 0) {
            return 0.0;
        }

        // Calculate efficiency: (output / input) * 100
        $ratio = bcdiv((string) $out, (string) $in, 6);
        $percentage = bcmul($ratio, '100', 4);

        // V30-MED-08 FIX: Use bcround() instead of bcdiv truncation
        return decimal_float(bcround($percentage, 2));
    }
}
