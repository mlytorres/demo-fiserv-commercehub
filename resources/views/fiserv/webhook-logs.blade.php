@extends('fiserv.layout')

@section('title', 'Fiserv Webhook Logs')
@section('body_class', 'wide')

@section('content')
    <h1>Fiserv Webhook Logs</h1>
    <p class="subtitle">Point an ngrok tunnel at this app and set <code>https://your-tunnel.ngrok.io/fiserv/webhook</code> as the webhook URL in your Commerce Hub sandbox subscription to see notifications land here.</p>

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
@endsection
