<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 13px; color: #212529; }
    h2 { margin-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    td { padding: 8px; border-bottom: 1px solid #dee2e6; }
    .label { color: #6c757d; }
    .amount { text-align: right; font-weight: bold; }
    .total-row td { border-top: 2px solid #212529; font-weight: bold; }
</style>
</head>
<body>
    <h2>{{ $hotel->name }} — Monthly P&amp;L Summary</h2>
    <p>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</p>
    <table>
        <tr><td class="label">Total revenue</td><td class="amount">₦{{ number_format($totalRevenue / 100, 2) }}</td></tr>
        <tr><td class="label">Fees deducted</td><td class="amount">-₦{{ number_format($feesDeducted / 100, 2) }}</td></tr>
        <tr><td class="label">Withdrawals (completed)</td><td class="amount">-₦{{ number_format($withdrawalsTotal / 100, 2) }}</td></tr>
        <tr class="total-row"><td>Closing wallet balance</td><td class="amount">₦{{ number_format($closingBalance / 100, 2) }}</td></tr>
    </table>
</body>
</html>
