<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('Purchase Details') }} #{{ $purchase->id }}</h1>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold mb-2">{{ __('Supplier') }}</h3>
                <p>{{ $purchase->supplier?->name ?? __('N/A') }}</p>
            </div>
            <div>
                <h3 class="font-semibold mb-2">{{ __('Date') }}</h3>
                <p>{{ $purchase->created_at?->format('Y-m-d H:i') }}</p>
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
                    @foreach($purchase->items as $item)
                    <tr wire:key="purchase-item-{{ $item->id }}" class="border-b">
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
                        <td class="text-right">{{ number_format($purchase->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('app.purchases.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                {{ __('Back to Purchases') }}
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
