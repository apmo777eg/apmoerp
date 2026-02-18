<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('Sale Details') }} #{{ $sale->id }}</h1>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold mb-2">{{ __('Customer') }}</h3>
                <p>{{ $sale->customer?->name ?? __('Walk-in Customer') }}</p>
            </div>
            <div>
                <h3 class="font-semibold mb-2">{{ __('Date') }}</h3>
                <p>{{ $sale->created_at?->format('Y-m-d H:i') }}</p>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="font-semibold mb-4">{{ __('Items') }}</h3>
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">{{ __('Product') }}</th>
                        <th class="text-right py-2">{{ __('Quantity') }}</th>
                        <th class="text-right py-2">{{ __('Price') }}</th>
                        <th class="text-right py-2">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                    <tr wire:key="sale-item-{{ $item->id }}" class="border-b">
                        <td class="py-2">{{ $item->product?->name ?? $item->product_name ?? __('N/A') }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="3" class="text-right py-4">{{ __('Total') }}</td>
                        <td class="text-right">{{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('app.sales.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                {{ __('Back to Sales') }}
            </a>
        </div>
    </div>
</div>

@if(request()->boolean('print'))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                try { window.print(); } catch (e) {}
            }, 250);
        });
    </script>
@endif
