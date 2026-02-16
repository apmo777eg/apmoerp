{{-- Invoice Template - Professional A4 PDF --}}
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $textAlign = $dir === 'rtl' ? 'right' : 'left';
    $textAlignOpposite = $dir === 'rtl' ? 'left' : 'right';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Invoice') }} #{{ $invoice['number'] ?? '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            direction: {{ $dir }};
            text-align: {{ $textAlign }};
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .company-section {
            flex: 1;
        }
        
        .company-logo {
            font-size: 28px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 8px;
        }
        
        .company-info {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        .invoice-title-section {
            text-align: {{ $textAlignOpposite }};
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .invoice-meta {
            font-size: 11px;
            color: #6b7280;
        }
        
        .invoice-meta strong {
            color: #1f2937;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .parties-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
        }
        
        .party-box h3 {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .party-box .name {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .party-box .details {
            font-size: 11px;
            color: #6b7280;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table thead {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .items-table th {
            padding: 12px 15px;
            text-align: {{ $textAlign }};
            color: white;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .items-table th:last-child {
            text-align: {{ $textAlignOpposite }};
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .items-table td {
            padding: 12px 15px;
            font-size: 12px;
        }
        
        .items-table td:last-child {
            text-align: {{ $textAlignOpposite }};
            font-weight: 600;
        }
        
        .items-table .item-desc {
            color: #6b7280;
            font-size: 10px;
            margin-top: 3px;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .totals-box {
            width: 300px;
            background: #f9fafb;
            border-radius: 10px;
            padding: 15px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 12px;
        }
        
        .totals-row.subtotal {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .totals-row.grand-total {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            margin: 10px -15px -15px;
            padding: 15px;
            border-radius: 0 0 10px 10px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .payment-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .payment-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
        }
        
        .payment-box h4 {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .payment-box p {
            font-size: 12px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .bank-details {
            background: #1f2937;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .bank-details h4 {
            font-size: 12px;
            margin-bottom: 15px;
            color: #10b981;
        }
        
        .bank-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .bank-details-grid p {
            font-size: 11px;
        }
        
        .bank-details-grid span {
            color: #9ca3af;
        }
        
        .notes-section {
            background: #fef3c7;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 30px;
        }
        
        .notes-section h4 {
            font-size: 11px;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .notes-section p {
            font-size: 11px;
            color: #78350f;
        }
        
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #9ca3af;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
        
        .qr-code {
            margin-top: 15px;
        }
        
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- Header --}}
        <div class="header">
            <div class="company-section">
                <div class="company-logo">{{ $company['name'] ?? config('app.name') }}</div>
                <div class="company-info">
                    @if (!empty($company['address'])) {{ $company['address'] }}<br> @endif
                    @if (!empty($company['phone'])) {{ __('Phone') }}: {{ $company['phone'] }}<br> @endif
                    @if (!empty($company['email'])) {{ __('Email') }}: {{ $company['email'] }}<br> @endif
                    @if (!empty($company['tax_number'])) {{ __('Tax Number') }}: {{ $company['tax_number'] }} @endif
                </div>
            </div>
            <div class="invoice-title-section">
                <div class="invoice-title">{{ __('INVOICE') }}</div>
                <div class="invoice-meta">
                    <strong>{{ __('Invoice Number') }}:</strong> {{ $invoice['number'] ?? '' }}<br>
                    <strong>{{ __('Invoice Date') }}:</strong> {{ $invoice['date'] ?? now()->format('Y-m-d') }}<br>
                    <strong>{{ __('Due Date') }}:</strong> {{ $invoice['due_date'] ?? '' }}
                </div>
                @php
                    $statusClass = match($invoice['status'] ?? 'pending') {
                        'paid' => 'status-paid',
                        'overdue' => 'status-overdue',
                        default => 'status-pending'
                    };
                @endphp
                <span class="status-badge {{ $statusClass }}">
                    {{ __(ucfirst($invoice['status'] ?? 'Pending')) }}
                </span>
            </div>
        </div>
        
        {{-- Parties Section --}}
        <div class="parties-section">
            <div class="party-box">
                <h3>{{ __('Bill From') }}</h3>
                <div class="name">{{ $company['name'] ?? config('app.name') }}</div>
                <div class="details">
                    @if (!empty($company['address'])) {{ $company['address'] }}<br> @endif
                    @if (!empty($company['phone'])) {{ $company['phone'] }}<br> @endif
                    @if (!empty($company['email'])) {{ $company['email'] }} @endif
                </div>
            </div>
            <div class="party-box">
                <h3>{{ __('Bill To') }}</h3>
                <div class="name">{{ $customer['name'] ?? '' }}</div>
                <div class="details">
                    @if (!empty($customer['company'])) {{ $customer['company'] }}<br> @endif
                    @if (!empty($customer['address'])) {{ $customer['address'] }}<br> @endif
                    @if (!empty($customer['phone'])) {{ $customer['phone'] }}<br> @endif
                    @if (!empty($customer['email'])) {{ $customer['email'] }}<br> @endif
                    @if (!empty($customer['tax_number'])) {{ __('Tax Number') }}: {{ $customer['tax_number'] }} @endif
                </div>
            </div>
        </div>
        
        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">{{ __('Description') }}</th>
                    <th style="width: 15%;">{{ __('Quantity') }}</th>
                    <th style="width: 15%;">{{ __('Unit Price') }}</th>
                    <th style="width: 15%;">{{ __('Tax') }}</th>
                    <th style="width: 15%;">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items ?? [] as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item['name'] ?? '' }}</strong>
                        @if (!empty($item['description']))
                            <div class="item-desc">{{ $item['description'] }}</div>
                        @endif
                    </td>
                    <td>{{ $item['quantity'] ?? 1 }} {{ $item['unit'] ?? '' }}</td>
                    <td>{{ $currency ?? '$' }}{{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                    <td>{{ $item['tax_rate'] ?? 0 }}%</td>
                    <td>{{ $currency ?? '$' }}{{ number_format($item['total'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        {{-- Totals --}}
        <div class="totals-section">
            <div class="totals-box">
                <div class="totals-row subtotal">
                    <span>{{ __('Subtotal') }}</span>
                    <span>{{ $currency ?? '$' }}{{ number_format($totals['subtotal'] ?? 0, 2) }}</span>
                </div>
                @if (($totals['discount'] ?? 0) > 0)
                <div class="totals-row">
                    <span>{{ __('Discount') }} ({{ $totals['discount_rate'] ?? 0 }}%)</span>
                    <span>-{{ $currency ?? '$' }}{{ number_format($totals['discount'], 2) }}</span>
                </div>
                @endif
                <div class="totals-row">
                    <span>{{ __('Tax') }} ({{ $totals['tax_rate'] ?? 0 }}%)</span>
                    <span>{{ $currency ?? '$' }}{{ number_format($totals['tax'] ?? 0, 2) }}</span>
                </div>
                @if (($totals['shipping'] ?? 0) > 0)
                <div class="totals-row">
                    <span>{{ __('Shipping') }}</span>
                    <span>{{ $currency ?? '$' }}{{ number_format($totals['shipping'], 2) }}</span>
                </div>
                @endif
                <div class="totals-row grand-total">
                    <span>{{ __('Total Due') }}</span>
                    <span>{{ $currency ?? '$' }}{{ number_format($totals['total'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        
        {{-- Payment Info --}}
        <div class="payment-section">
            <div class="payment-box">
                <h4>{{ __('Payment Terms') }}</h4>
                <p><strong>{{ __('Due Date') }}:</strong> {{ $invoice['due_date'] ?? '' }}</p>
                <p><strong>{{ __('Payment Method') }}:</strong> {{ __($payment['method'] ?? 'Bank Transfer') }}</p>
                @if (!empty($payment['reference']))
                    <p><strong>{{ __('Reference') }}:</strong> {{ $payment['reference'] }}</p>
                @endif
            </div>
            <div class="payment-box">
                <h4>{{ __('Amount Summary') }}</h4>
                <p><strong>{{ __('Total Amount') }}:</strong> {{ $currency ?? '$' }}{{ number_format($totals['total'] ?? 0, 2) }}</p>
                <p><strong>{{ __('Amount Paid') }}:</strong> {{ $currency ?? '$' }}{{ number_format($payment['amount_paid'] ?? 0, 2) }}</p>
                <p><strong>{{ __('Balance Due') }}:</strong> {{ $currency ?? '$' }}{{ number_format(($totals['total'] ?? 0) - ($payment['amount_paid'] ?? 0), 2) }}</p>
            </div>
        </div>
        
        {{-- Bank Details --}}
        @if (!empty($bank))
        <div class="bank-details">
            <h4>{{ __('Bank Account Details') }}</h4>
            <div class="bank-details-grid">
                <p><span>{{ __('Bank Name') }}:</span> {{ $bank['name'] ?? '' }}</p>
                <p><span>{{ __('Account Name') }}:</span> {{ $bank['account_name'] ?? '' }}</p>
                <p><span>{{ __('Account Number') }}:</span> {{ $bank['account_number'] ?? '' }}</p>
                <p><span>{{ __('IBAN') }}:</span> {{ $bank['iban'] ?? '' }}</p>
                <p><span>{{ __('SWIFT/BIC') }}:</span> {{ $bank['swift'] ?? '' }}</p>
            </div>
        </div>
        @endif
        
        {{-- Notes --}}
        @if (!empty($notes))
        <div class="notes-section">
            <h4>{{ __('Notes') }}</h4>
            <p>{{ $notes }}</p>
        </div>
        @endif
        
        {{-- Footer --}}
        <div class="footer">
            <p>{{ __('Thank you for your business!') }}</p>
            <p>{{ __('Questions? Contact us at') }} {{ $company['email'] ?? '' }} | {{ $company['phone'] ?? '' }}</p>
            <p style="margin-top: 10px;">{{ config('app.name') }} | {{ __('Invoice') }} #{{ $invoice['number'] ?? '' }}</p>
        </div>
    </div>
    
    {{-- Print Button --}}
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button type="button" onclick="window.print()" style="padding: 12px 40px; font-size: 14px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 8px; margin-right: 10px;">
            {{ __('Print Invoice') }}
        </button>
        <button type="button" onclick="window.close()" style="padding: 12px 40px; font-size: 14px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 8px;">
            {{ __('Close') }}
        </button>
    </div>
</body>
</html>
