<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payslip') }} - {{ $employee->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .payslip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .payslip-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .payslip-title {
            font-size: 20px;
            opacity: 0.9;
        }
        .payslip-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .info-section h3 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-weight: 500;
        }
        .info-value {
            font-weight: bold;
            color: #333;
        }
        .earnings-deductions {
            padding: 30px;
        }
        .section-title {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .breakdown-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }
        .breakdown-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .breakdown-table tr:last-child td {
            border-bottom: none;
        }
        .amount {
            text-align: right;
            font-weight: 600;
        }
        .earning-amount {
            color: #10b981;
        }
        .deduction-amount {
            color: #ef4444;
        }
        .subtotal-row {
            background: #f8f9fa;
            font-weight: 600;
        }
        .total-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        .total-row td {
            padding: 15px 12px;
        }
        .payslip-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 2px solid #e0e0e0;
        }
        .footer-text {
            color: #666;
            font-size: 12px;
            margin-top: 10px;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 30px;
            margin-top: 40px;
        }
        .signature-box {
            text-align: center;
            padding-top: 60px;
            border-top: 2px solid #333;
        }
        .signature-label {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .payslip-container {
                box-shadow: none;
                max-width: 100%;
            }
        }
        @media (max-width: 768px) {
            .payslip-info {
                grid-template-columns: 1fr;
            }
            .signature-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <div class="payslip-header">
            <div class="company-name">{{ $branch->name ?? config('app.name') }}</div>
            <div class="payslip-title">{{ __('PAYSLIP') }}</div>
        </div>

        <div class="payslip-info">
            <div class="info-section">
                <h3>{{ __('Employee Information') }}</h3>
                <div class="info-row">
                    <span class="info-label">{{ __('Employee Code') }}:</span>
                    <span class="info-value">{{ $employee->code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Name') }}:</span>
                    <span class="info-value">{{ $employee->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Position') }}:</span>
                    <span class="info-value">{{ $employee->position ?? '-' }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>{{ __('Payroll Information') }}</h3>
                <div class="info-row">
                    <span class="info-label">{{ __('Period') }}:</span>
                    <span class="info-value">{{ $payroll->period }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Pay Date') }}:</span>
                    <span class="info-value">{{ $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : __('Pending') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Status') }}:</span>
                    <span class="info-value">{{ ucfirst($payroll->status) }}</span>
                </div>
            </div>
        </div>

        <div class="earnings-deductions">
            <div class="section-title">{{ __('Salary Breakdown') }}</div>
            
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th class="amount">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Basic Salary') }}</td>
                        <td class="amount earning-amount">{{ number_format($payroll->basic, 2) }}</td>
                    </tr>
                    @if($payroll->allowances > 0)
                    <tr>
                        <td>{{ __('Allowances') }}</td>
                        <td class="amount earning-amount">{{ number_format($payroll->allowances, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="subtotal-row">
                        <td><strong>{{ __('Gross Salary') }}</strong></td>
                        <td class="amount"><strong>{{ number_format($payroll->basic + $payroll->allowances, 2) }}</strong></td>
                    </tr>
                    @if($payroll->deductions > 0)
                    <tr>
                        <td>{{ __('Deductions') }}</td>
                        <td class="amount deduction-amount">{{ number_format($payroll->deductions, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>{{ __('Net Salary') }}</strong></td>
                        <td class="amount"><strong>{{ number_format($payroll->net, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">{{ __('Employee Signature') }}</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">{{ __('Authorized Signature') }}</div>
            </div>
        </div>

        <div class="payslip-footer">
            <div class="footer-text">
                {{ __('This is a computer-generated payslip and does not require a signature.') }}
            </div>
            <div class="footer-text">
                {{ __('Generated on') }}: {{ $generatedAt }}
            </div>
        </div>
    </div>
</body>
</html>
