<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Store;

use App\Models\StoreOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersDashboard extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public ?string $statusFilter = null;

    public ?string $sourceFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $selectedOrderId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $selectedOrderDetails = null;

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('store.reports.dashboard')) {
            abort(403);
        }

        $query = StoreOrder::query()->with('sale');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->sourceFilter) {
            $query->where('payload->meta->source', $this->sourceFilter);
        }

        $orders = $query->orderByDesc('created_at')->paginate(25);

        $ordersForStats = (clone $query)->get();

        $totalOrders = $ordersForStats->count();
        $totalRevenue = decimal_float($ordersForStats->sum('total'));
        $totalDiscount = decimal_float($ordersForStats->sum('discount_total'));
        $totalShipping = decimal_float($ordersForStats->sum('shipping_total'));
        $totalTax = decimal_float($ordersForStats->sum('tax_total'));

        $sources = [];
        foreach ($ordersForStats as $order) {
            $source = $order->source ?? 'unknown';

            if (! isset($sources[$source])) {
                $sources[$source] = [
                    'count' => 0,
                    'revenue' => 0.0,
                ];
            }

            $sources[$source]['count']++;
            $sources[$source]['revenue'] += decimal_float($order->total);
        }

        $sources = collect($sources)
            ->map(function (array $v, string $k): array {
                $v['source'] = $k;

                return $v;
            })
            ->sortByDesc('revenue')
            ->values()
            ->all();

        $allStatuses = StoreOrder::query()
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->filter()
            ->values()
            ->all();

        $allSources = StoreOrder::query()
            ->get()
            ->map(static function (StoreOrder $order): ?string {
                return $order->source;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Chart data: revenue by source
        $chartRevenueBySource = [
            'labels' => array_map(static function (array $s): string {
                return ucfirst($s['source']);
            }, $sources),
            'values' => array_map(static function (array $s): float {
                return decimal_float($s['revenue']);
            }, $sources),
        ];

        // Chart data: revenue by day
        $groupedByDay = $ordersForStats->groupBy(function (StoreOrder $order): string {
            return optional($order->created_at)->toDateString() ?? '';
        });

        $dayLabels = [];
        $dayValues = [];

        foreach ($groupedByDay as $date => $items) {
            if (! $date) {
                continue;
            }

            $dayLabels[] = $date;
            $dayValues[] = decimal_float($items->sum('total'));
        }

        $chartOrdersByDay = [
            'labels' => $dayLabels,
            'values' => $dayValues,
        ];

        $this->dispatch('store-orders-charts-update', chartData: [
            'revenueBySource' => $chartRevenueBySource,
            'ordersByDay' => $chartOrdersByDay,
        ]);

        return view('livewire.admin.store.orders-dashboard', [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'totalDiscount' => $totalDiscount,
            'totalShipping' => $totalShipping,
            'totalTax' => $totalTax,
            'sources' => $sources,
            'allStatuses' => $allStatuses,
            'allSources' => $allSources,
        ]);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function viewOrder(int $id): void
    {
        $order = StoreOrder::query()->with('sale')->findOrFail($id);

        $payload = $order->payload ?? [];

        $this->selectedOrderId = $order->id;
        $this->selectedOrderDetails = [
            'external_order_id' => $order->external_order_id,
            'status' => $order->status,
            'branch_id' => $order->branch_id,
            'currency' => $order->currency,
            'total' => $order->total,
            'discount_total' => $order->discount_total,
            'shipping_total' => $order->shipping_total,
            'tax_total' => $order->tax_total,
            'source' => $order->source ?? 'unknown',
            'customer' => Arr::get($payload, 'customer', null),
            'items' => Arr::get($payload, 'items', []),
            'meta' => Arr::get($payload, 'meta', []),
            'sale_id' => $order->sale?->id,
            'created_at' => optional($order->created_at)->toDateTimeString(),
        ];
    }
}
