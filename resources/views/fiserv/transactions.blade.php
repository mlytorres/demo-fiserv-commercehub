<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fiserv Transaction History</title>
    <style>
        body { font-family: -apple-system, Segoe UI, sans-serif; max-width: 960px; margin: 40px auto; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; }
        .badge.approved { background: #e6f4ea; color: #1e7e34; }
        .badge.declined { background: #fdecea; color: #b3261e; }
        .badge.pending  { background: #fff4e0; color: #8a5a00; }
        .action { text-transform: capitalize; }
        code { font-size: 12px; }
        a { color: #0a5c36; }
    </style>
</head>
<body>
    <p><a href="{{ route('fiserv.demo.index') }}">← Back</a></p>
    <h1>Fiserv Transaction History</h1>
    <p>Every charge, refund, and status inquiry attempted through this demo's UI — including ones that failed before reaching Commerce Hub.</p>

    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Action</th>
                <th>Status</th>
                <th>Transaction ID</th>
                <th>Order ID</th>
                <th>Amount</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($transactions as $transaction)
            @php
                $badgeClass = match (true) {
                    $transaction->approved => 'approved',
                    in_array($transaction->status_label, ['Authorized', 'Waiting'], true) => 'pending',
                    default => 'declined',
                };
            @endphp
            <tr>
                <td>{{ $transaction->created_at }}</td>
                <td class="action">{{ $transaction->action }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ $transaction->status_label ?? '—' }}</span></td>
                <td><code>{{ $transaction->transaction_id ?? '—' }}</code></td>
                <td><code>{{ $transaction->order_id ?? '—' }}</code></td>
                <td>{{ $transaction->amount !== null ? '$'.number_format((float) $transaction->amount, 2) : '—' }}</td>
                <td>{{ $transaction->failure_reason ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No transactions yet — charge, refund, or inquire something to see it here.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $transactions->links() }}
</body>
</html>
