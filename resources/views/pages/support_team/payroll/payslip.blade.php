<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Salary Slip - {{ $payroll->staff->name ?? 'Staff' }} ({{ $payroll->salary_month_bs }})</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #0f172a; }
        .slip { max-width: 700px; margin: 0 auto; border: 2px solid #1e293b; padding: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
        th { background: #f1f5f9; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="max-width:700px;margin:0 auto 10px;text-align:right;">
        <button onclick="window.print()" style="padding:8px 14px;cursor:pointer;">Print Payslip</button>
    </div>
    <div class="slip">
        <h2 style="margin:0;">{{ $school->name ?? Qs::getSystemName() }}</h2>
        <p style="margin:4px 0 16px;color:#475569;">Salary Slip — Month (BS): <strong>{{ $payroll->salary_month_bs }}</strong></p>
        <p><strong>Staff Name:</strong> {{ $payroll->staff->name ?? '-' }} | <strong>Role:</strong> {{ ucwords(str_replace('_', ' ', $payroll->staff->user_type ?? '')) }} | <strong>Status:</strong> {{ strtoupper($payroll->payment_status) }}</p>
        <table>
            <thead><tr><th>Earnings</th><th>Amount (NPR)</th><th>Deductions</th><th>Amount (NPR)</th></tr></thead>
            <tbody>
                <tr><td>Basic Salary</td><td>{{ number_format((float)$payroll->basic_salary, 2) }}</td><td>Provident Fund</td><td>{{ number_format((float)$payroll->provident_fund, 2) }}</td></tr>
                <tr><td>Allowances</td><td>{{ number_format((float)$payroll->allowances, 2) }}</td><td>Tax / TDS</td><td>{{ number_format((float)$payroll->tax_amount, 2) }}</td></tr>
                <tr><td></td><td></td><td>Other Deductions</td><td>{{ number_format((float)$payroll->deductions, 2) }}</td></tr>
                <tr><th colspan="2">Net Payable Salary</th><th colspan="2">{{ \App\Helpers\NepaliHelper::formatNpr($payroll->net_salary) }}</th></tr>
            </tbody>
        </table>
        <p style="margin-top:20px;font-size:13px;color:#475569;">Paid Date: {{ $payroll->paid_date_bs ?: '-' }} | Method: {{ $payroll->payment_method }}</p>
    </div>
</body>
</html>
