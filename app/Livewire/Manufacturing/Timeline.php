<?php

declare(strict_types=1);

namespace App\Livewire\Manufacturing;

use App\Models\ProductionOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Production Timeline Component
 *
 * Visual timeline view of production orders showing:
 * - Order progress over time
 * - Status indicators
 * - Duration visualization
 * - Quick status updates
 */
#[Layout('layouts.app')]
class Timeline extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $viewMode = 'week'; // week, month

    #[Url]
    public string $status = '';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->authorize('manufacturing.view');

        $this->setDateRange();
    }

    /**
     * Set date range based on view mode
     */
    protected function setDateRange(): void
    {
        $now = now();

        if ($this->viewMode === 'week') {
            $this->startDate = $now->startOfWeek()->toDateString();
            $this->endDate = $now->endOfWeek()->toDateString();
        } else {
            $this->startDate = $now->startOfMonth()->toDateString();
            $this->endDate = $now->endOfMonth()->toDateString();
        }
    }

    /**
     * Navigate to previous period
     */
    public function previousPeriod(): void
    {
        $start = \Carbon\Carbon::parse($this->startDate);

        if ($this->viewMode === 'week') {
            $this->startDate = $start->subWeek()->startOfWeek()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfWeek()->toDateString();
        } else {
            $this->startDate = $start->subMonth()->startOfMonth()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfMonth()->toDateString();
        }
    }

    /**
     * Navigate to next period
     */
    public function nextPeriod(): void
    {
        $start = \Carbon\Carbon::parse($this->startDate);

        if ($this->viewMode === 'week') {
            $this->startDate = $start->addWeek()->startOfWeek()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfWeek()->toDateString();
        } else {
            $this->startDate = $start->addMonth()->startOfMonth()->toDateString();
            $this->endDate = \Carbon\Carbon::parse($this->startDate)->endOfMonth()->toDateString();
        }
    }

    /**
     * Go to today
     */
    public function goToToday(): void
    {
        $this->setDateRange();
    }

    /**
     * Update view mode
     */
    public function updatedViewMode(): void
    {
        $this->setDateRange();
    }

    /**
     * Get production orders for timeline
     */
    public function getOrdersProperty()
    {
        $user = auth()->user();

        return ProductionOrder::query()
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->where(function ($query) {
                $query->whereBetween('planned_start_date', [$this->startDate, $this->endDate])
                    ->orWhereBetween('planned_end_date', [$this->startDate, $this->endDate])
                    ->orWhere(function ($q) {
                        $q->where('planned_start_date', '<=', $this->startDate)
                            ->where('planned_end_date', '>=', $this->endDate);
                    });
            })
            ->with(['product'])
            ->orderBy('planned_start_date')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->reference_number,
                    'product_name' => $order->product?->name ?? __('N/A'),
                    'work_center' => __('N/A'),
                    'status' => $order->status,
                    'priority' => $order->priority,
                    'quantity_planned' => (float) $order->planned_quantity,
                    'quantity_produced' => (float) $order->produced_quantity,
                    'progress' => $order->planned_quantity > 0
                        ? round(($order->produced_quantity / $order->planned_quantity) * 100)
                        : 0,
                    'start_date' => $order->planned_start_date?->format('Y-m-d'),
                    'due_date' => $order->planned_end_date?->format('Y-m-d'),
                    'days_remaining' => $order->planned_end_date ? now()->diffInDays($order->planned_end_date, false) : null,
                    'is_overdue' => $order->planned_end_date && $order->planned_end_date < now() && $order->status !== 'completed',
                ];
            });
    }

    /**
     * Get days for header
     */
    public function getDaysProperty(): array
    {
        $days = [];
        $current = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);

        while ($current <= $end) {
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'day' => $current->format('D'),
                'day_num' => $current->format('d'),
                'is_today' => $current->isToday(),
                'is_weekend' => $current->isWeekend(),
            ];
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get status color
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'bg-gray-400',
            'pending' => 'bg-blue-400',
            'in_progress' => 'bg-amber-400',
            'completed' => 'bg-green-400',
            'cancelled' => 'bg-red-400',
            default => 'bg-gray-400',
        };
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'bg-red-500 text-white',
            'high' => 'bg-orange-500 text-white',
            'normal' => 'bg-blue-500 text-white',
            'low' => 'bg-gray-500 text-white',
            default => 'bg-gray-500 text-white',
        };
    }

    /**
     * Calculate position and width for timeline bar
     */
    public function getTimelinePosition(array $order): array
    {
        $startDate = \Carbon\Carbon::parse($this->startDate);
        $endDate = \Carbon\Carbon::parse($this->endDate);
        $totalDays = max(1, $startDate->diffInDays($endDate) + 1);

        $orderStart = $order['start_date'] ? \Carbon\Carbon::parse($order['start_date']) : $startDate;
        $orderEnd = $order['due_date'] ? \Carbon\Carbon::parse($order['due_date']) : $orderStart;

        // Clamp to visible range
        if ($orderStart < $startDate) {
            $orderStart = $startDate;
        }
        if ($orderEnd > $endDate) {
            $orderEnd = $endDate;
        }

        $leftDays = $startDate->diffInDays($orderStart);
        $widthDays = max(1, $orderStart->diffInDays($orderEnd) + 1);

        return [
            'left' => ($leftDays / $totalDays) * 100,
            'width' => ($widthDays / $totalDays) * 100,
        ];
    }

    public function render()
    {
        return view('livewire.manufacturing.timeline')
            ->title(__('Production Timeline'));
    }
}
