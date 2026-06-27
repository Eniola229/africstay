<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 12px; color: #212529; }
    h2 { margin-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { border-bottom: 1px solid #dee2e6; padding: 6px 8px; text-align: left; }
    th { background: #f8f9fa; }
</style>
</head>
<body>
    <h2>{{ $hotel->name }} — Outstanding Balances</h2>
    <p>Generated {{ now()->format('d M Y H:i') }}</p>
    <table>
        <thead><tr><th>Booking</th><th>Guest</th><th>Room</th><th>Total</th><th>Paid</th><th>Balance</th></tr></thead>
        <tbody>
            @foreach($bookings as $b)
            <tr>
                <td>{{ $b->booking_reference }}</td>
                <td>{{ $b->guest->name }}</td>
                <td>Room {{ $b->room->room_number }}</td>
                <td>₦{{ number_format($b->totalAmountNaira(), 2) }}</td>
                <td>₦{{ number_format($b->amountPaidNaira(), 2) }}</td>
                <td>₦{{ number_format($b->balanceNaira(), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
