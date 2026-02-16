{{-- POS Receipt Template - Thermal Printer Friendly (80mm) --}}
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $textAlign = $dir === 'rtl' ? 'right' : 'left';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Receipt') }} #{{ $receipt['number'] ?? '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            width: 80mm;
            padding: 5mm;
            direction: {{ $dir }};
            text-align: {{ $textAlign }};
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .logo {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .store-info {
            font-size: 10px;
            color: #333;
        }
        
        .receipt-info {
            margin: 10px 0;
            font-size: 11px;
        }
        
        .receipt-info table {
            width: 100%;
        }
        
        .receipt-info td {
            padding: 2px 0;
        }
        
        .items {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .items th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 2px;
            font-size: 11px;
            text-align: {{ $textAlign }};
        }
        
        .items td {
            padding: 4px 2px;
            font-size: 11px;
            vertical-align: top;
        }
        
        .items .item-name {
            max-width: 120px;
        }
        
        .items .qty {
            text-align: center;
            width: 30px;
        }
        
        .items .price {
            text-align: {{ $dir === 'rtl' ? 'left' : 'right' }};
            white-space: nowrap;
        }
        
        .totals {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .totals table {
            width: 100%;
        }
        
        .totals td {
            padding: 3px 0;
            font-size: 11px;
        }
        
        .totals .label {
            text-align: {{ $textAlign }};
        }
        
        .totals .value {
            text-align: {{ $dir === 'rtl' ? 'left' : 'right' }};
            font-weight: bold;
        }
        
        .totals .grand-total {
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .payment-info {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
            font-size: 11px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', cursive;
            font-size: 28px;
        }
        
        .qr-placeholder {
            text-align: center;
            margin: 10px 0;
        }
        
        @media print {
            body {
                width: 80mm;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="logo">{{ $store['name'] ?? config('app.name') }}</div>
        <div class="store-info">
            @if (!empty($store['address']))
                {{ $store['address'] }}<br>
            @endif
            @if (!empty($store['phone']))
                {{ __('Phone') }}: {{ $store['phone'] }}<br>
            @endif
            @if (!empty($store['tax_number']))
                {{ __('Tax No') }}: {{ $store['tax_number'] }}
            @endif
        </div>
    </div>
    
    {{-- Receipt Info --}}
    <div class="receipt-info">
        <table>
            <tr>
                <td>{{ __('Receipt') }} #:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ $receipt['number'] ?? '' }}</td>
            </tr>
            <tr>
                <td>{{ __('Date') }}:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ $receipt['date'] ?? now()->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td>{{ __('Cashier') }}:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ $receipt['cashier'] ?? '' }}</td>
            </tr>
            @if (!empty($receipt['customer']))
            <tr>
                <td>{{ __('Customer') }}:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ $receipt['customer'] }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th class="item-name">{{ __('Item') }}</th>
                <th class="qty">{{ __('Qty') }}</th>
                <th class="price">{{ __('Price') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items ?? [] as $item)
            <tr>
                <td class="item-name">
                    {{ $item['name'] ?? '' }}
                    @if (!empty($item['variant']))
                        <br><small style="color: #666;">{{ $item['variant'] }}</small>
                    @endif
                </td>
                <td class="qty">{{ $item['quantity'] ?? 1 }}</td>
                <td class="price">{{ $currency ?? '$' }}{{ number_format($item['total'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    {{-- Totals --}}
    <div class="totals">
        <table>
            <tr>
                <td class="label">{{ __('Subtotal') }}:</td>
                <td class="value">{{ $currency ?? '$' }}{{ number_format($totals['subtotal'] ?? 0, 2) }}</td>
            </tr>
            @if (($totals['discount'] ?? 0) > 0)
            <tr>
                <td class="label">{{ __('Discount') }}:</td>
                <td class="value">-{{ $currency ?? '$' }}{{ number_format($totals['discount'], 2) }}</td>
            </tr>
            @endif
            @if (($totals['tax'] ?? 0) > 0)
            <tr>
                <td class="label">{{ __('Tax') }} ({{ $totals['tax_rate'] ?? 0 }}%):</td>
                <td class="value">{{ $currency ?? '$' }}{{ number_format($totals['tax'], 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label">{{ __('Total') }}:</td>
                <td class="value">{{ $currency ?? '$' }}{{ number_format($totals['total'] ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>
    
    {{-- Payment Info --}}
    <div class="payment-info">
        <table>
            <tr>
                <td>{{ __('Payment Method') }}:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ __($payment['method'] ?? 'Cash') }}</td>
            </tr>
            <tr>
                <td>{{ __('Amount Paid') }}:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ $currency ?? '$' }}{{ number_format($payment['amount'] ?? 0, 2) }}</td>
            </tr>
            @if (($payment['change'] ?? 0) > 0)
            <tr>
                <td>{{ __('Change') }}:</td>
                <td style="text-align: {{ $dir === 'rtl' ? 'left' : 'right' }}">{{ $currency ?? '$' }}{{ number_format($payment['change'], 2) }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    {{-- Barcode --}}
    @if (!empty($receipt['barcode']))
    <div class="barcode">
        *{{ $receipt['barcode'] }}*
    </div>
    @endif
    
    {{-- Footer --}}
    <div class="footer">
        <p>{{ $footer['message'] ?? __('Thank you for your purchase!') }}</p>
        @if (!empty($footer['return_policy']))
            <p style="margin-top: 5px;">{{ $footer['return_policy'] }}</p>
        @endif
        <p style="margin-top: 10px; font-size: 9px;">{{ __('Powered by') }} {{ config('app.name') }}</p>
    </div>
    
    {{-- Print Button (hidden when printing) --}}
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button type="button" onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer;">
            {{ __('Print Receipt') }}
        </button>
    </div>
</body>
</html>
