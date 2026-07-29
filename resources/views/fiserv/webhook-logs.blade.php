<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fiserv Webhook Logs</title>
    <style>
        body { font-family: -apple-system, Segoe UI, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .processed { background: #e6f4ea; color: #1e7e34; }
        .declined { background: #fdecea; color: #b3261e; }
        .invalid_signature { background: #fff4e5; color: #a35a00; }
        a { color: #0a5c36; }
    </style>
</head>
<body>
    <p><a href="{{ route('fiserv.demo.index') }}">← Back</a></p>
    <h1>Fiserv Webhook Logs</h1>
    <p>Point an ngrok tunnel at this app and set <code>https://your-tunnel.ngrok.io/fiserv/webhook</code> as the webhook URL in your Commerce Hub sandbox subscription to see notifications land here.</p>

    <table>
        <thead>
            <tr><th>Received</th><th>Order ID</th><th>Transaction ID</th><th>State</th><th>Status</th></tr>
        </thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->order_id }}</td>
                <td>{{ $log->transaction_id }}</td>
                <td>{{ $log->transaction_state }}</td>
                <td><span class="badge {{ $log->status }}">{{ $log->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">No webhooks received yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $logs->links() }}
</body>
</html>
