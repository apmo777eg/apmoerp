{{-- Rental Contract Template - Professional A4 PDF --}}
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $textAlign = $dir === 'rtl' ? 'right' : 'left';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Rental Contract') }} #{{ $contract['number'] ?? '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4;
            margin: 20mm;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            direction: {{ $dir }};
            text-align: {{ $textAlign }};
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #10b981;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
        }
        
        .contract-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
        }
        
        .contract-badge h2 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .contract-badge .number {
            font-size: 20px;
            font-weight: bold;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .info-box h4 {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .info-box p {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .parties {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .party-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        
        .party-box h3 {
            font-size: 12px;
            color: #10b981;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .party-box .name {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .party-box .details {
            font-size: 11px;
            color: #4b5563;
        }
        
        .party-box .details span {
            display: block;
            margin-bottom: 3px;
        }
        
        .property-details {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
        }
        
        .property-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .property-table td {
            padding: 8px 0;
            border-bottom: 1px solid #d1fae5;
        }
        
        .property-table td:first-child {
            color: #6b7280;
            width: 40%;
        }
        
        .property-table td:last-child {
            font-weight: 600;
            color: #1f2937;
        }
        
        .financial-summary {
            background: #1f2937;
            color: white;
            padding: 20px;
            border-radius: 10px;
        }
        
        .financial-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            text-align: center;
        }
        
        .financial-item h4 {
            font-size: 10px;
            color: #9ca3af;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .financial-item .amount {
            font-size: 18px;
            font-weight: bold;
            color: #10b981;
        }
        
        .terms {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        
        .terms ol {
            padding-{{ $dir === 'rtl' ? 'right' : 'left' }}: 20px;
        }
        
        .terms li {
            margin-bottom: 8px;
            font-size: 11px;
            color: #4b5563;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
        }
        
        .signature-box .title {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .signature-box .name {
            font-weight: 600;
            font-size: 13px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
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
    {{-- Header --}}
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $company['name'] ?? config('app.name') }}</div>
            <div class="company-details">
                @if (!empty($company['address'])) {{ $company['address'] }}<br> @endif
                @if (!empty($company['phone'])) {{ __('Phone') }}: {{ $company['phone'] }}<br> @endif
                @if (!empty($company['email'])) {{ __('Email') }}: {{ $company['email'] }}<br> @endif
                @if (!empty($company['license'])) {{ __('License') }}: {{ $company['license'] }} @endif
            </div>
        </div>
        <div class="contract-badge">
            <h2>{{ __('Rental Contract') }}</h2>
            <div class="number">#{{ $contract['number'] ?? '' }}</div>
        </div>
    </div>
    
    {{-- Contract Info --}}
    <div class="section">
        <div class="info-grid">
            <div class="info-box">
                <h4>{{ __('Contract Date') }}</h4>
                <p>{{ $contract['date'] ?? now()->format('Y-m-d') }}</p>
            </div>
            <div class="info-box">
                <h4>{{ __('Start Date') }}</h4>
                <p>{{ $contract['start_date'] ?? '' }}</p>
            </div>
            <div class="info-box">
                <h4>{{ __('End Date') }}</h4>
                <p>{{ $contract['end_date'] ?? '' }}</p>
            </div>
            <div class="info-box">
                <h4>{{ __('Duration') }}</h4>
                <p>{{ $contract['duration'] ?? '' }} {{ __('months') }}</p>
            </div>
        </div>
    </div>
    
    {{-- Parties --}}
    <div class="section">
        <div class="section-title">{{ __('Contract Parties') }}</div>
        <div class="parties">
            <div class="party-box">
                <h3>{{ __('First Party (Landlord)') }}</h3>
                <div class="name">{{ $landlord['name'] ?? '' }}</div>
                <div class="details">
                    @if (!empty($landlord['id_number']))
                        <span>{{ __('ID Number') }}: {{ $landlord['id_number'] }}</span>
                    @endif
                    @if (!empty($landlord['phone']))
                        <span>{{ __('Phone') }}: {{ $landlord['phone'] }}</span>
                    @endif
                    @if (!empty($landlord['address']))
                        <span>{{ __('Address') }}: {{ $landlord['address'] }}</span>
                    @endif
                </div>
            </div>
            <div class="party-box">
                <h3>{{ __('Second Party (Tenant)') }}</h3>
                <div class="name">{{ $tenant['name'] ?? '' }}</div>
                <div class="details">
                    @if (!empty($tenant['id_number']))
                        <span>{{ __('ID Number') }}: {{ $tenant['id_number'] }}</span>
                    @endif
                    @if (!empty($tenant['phone']))
                        <span>{{ __('Phone') }}: {{ $tenant['phone'] }}</span>
                    @endif
                    @if (!empty($tenant['address']))
                        <span>{{ __('Address') }}: {{ $tenant['address'] }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    {{-- Property Details --}}
    <div class="section">
        <div class="section-title">{{ __('Property Details') }}</div>
        <div class="property-details">
            <table class="property-table">
                <tr>
                    <td>{{ __('Property Type') }}</td>
                    <td>{{ $property['type'] ?? '' }}</td>
                </tr>
                <tr>
                    <td>{{ __('Unit Number') }}</td>
                    <td>{{ $property['unit_number'] ?? '' }}</td>
                </tr>
                <tr>
                    <td>{{ __('Building') }}</td>
                    <td>{{ $property['building'] ?? '' }}</td>
                </tr>
                <tr>
                    <td>{{ __('Address') }}</td>
                    <td>{{ $property['address'] ?? '' }}</td>
                </tr>
                <tr>
                    <td>{{ __('Area') }}</td>
                    <td>{{ $property['area'] ?? '' }} {{ __('sqm') }}</td>
                </tr>
                <tr>
                    <td>{{ __('Furnishing') }}</td>
                    <td>{{ __($property['furnishing'] ?? 'Unfurnished') }}</td>
                </tr>
            </table>
        </div>
    </div>
    
    {{-- Financial Summary --}}
    <div class="section">
        <div class="section-title">{{ __('Financial Summary') }}</div>
        <div class="financial-summary">
            <div class="financial-grid">
                <div class="financial-item">
                    <h4>{{ __('Monthly Rent') }}</h4>
                    <div class="amount">{{ $currency ?? '$' }}{{ number_format($financial['monthly_rent'] ?? 0, 2) }}</div>
                </div>
                <div class="financial-item">
                    <h4>{{ __('Security Deposit') }}</h4>
                    <div class="amount">{{ $currency ?? '$' }}{{ number_format($financial['deposit'] ?? 0, 2) }}</div>
                </div>
                <div class="financial-item">
                    <h4>{{ __('Annual Total') }}</h4>
                    <div class="amount">{{ $currency ?? '$' }}{{ number_format($financial['annual_total'] ?? 0, 2) }}</div>
                </div>
                <div class="financial-item">
                    <h4>{{ __('Payment Due') }}</h4>
                    <div class="amount">{{ $financial['payment_due'] ?? __('Monthly') }}</div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Terms and Conditions --}}
    <div class="section">
        <div class="section-title">{{ __('Terms and Conditions') }}</div>
        <div class="terms">
            <ol>
                @forelse ($terms ?? [] as $term)
                    <li>{{ $term }}</li>
                @empty
                    <li>{{ __('The tenant agrees to pay the rent on or before the due date.') }}</li>
                    <li>{{ __('The tenant shall maintain the property in good condition.') }}</li>
                    <li>{{ __('The security deposit will be refunded upon termination, subject to deductions for damages.') }}</li>
                    <li>{{ __('Either party must provide written notice before terminating the contract.') }}</li>
                    <li>{{ __('The tenant shall not sublet the property without written consent from the landlord.') }}</li>
                    <li>{{ __('The tenant is responsible for utility bills during the rental period.') }}</li>
                @endforelse
            </ol>
        </div>
    </div>
    
    {{-- Signatures --}}
    <div class="signatures">
        <div class="signature-box">
            <div class="title">{{ __('First Party Signature') }}</div>
            <div class="signature-line">
                <div class="name">{{ $landlord['name'] ?? '' }}</div>
            </div>
        </div>
        <div class="signature-box">
            <div class="title">{{ __('Second Party Signature') }}</div>
            <div class="signature-line">
                <div class="name">{{ $tenant['name'] ?? '' }}</div>
            </div>
        </div>
    </div>
    
    {{-- Footer --}}
    <div class="footer">
        <p>{{ __('This contract was generated on') }} {{ now()->format('Y-m-d H:i') }} | {{ config('app.name') }}</p>
        <p>{{ __('Contract Number') }}: {{ $contract['number'] ?? '' }}</p>
    </div>
    
    {{-- Print Button --}}
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button type="button" onclick="window.print()" style="padding: 12px 40px; font-size: 14px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 8px;">
            {{ __('Print Contract') }}
        </button>
    </div>
</body>
</html>
