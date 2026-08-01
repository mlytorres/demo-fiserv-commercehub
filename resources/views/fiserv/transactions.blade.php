@extends('fiserv.layout')

@section('title', 'Fiserv Transaction History')
@section('body_class', 'wide')

@section('content')
    <h1>Fiserv Transaction History</h1>
    <p class="subtitle">Every charge, pre-auth, capture, void, refund, and status inquiry attempted through this demo's UI — including ones that failed before reaching Commerce Hub.</p>

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
                <td>{{ ucwords(str_replace('_', ' ', $transaction->action)) }}</td>
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
@endsection
