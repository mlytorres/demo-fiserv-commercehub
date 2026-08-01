@extends('fiserv.layout')

@section('title', 'Fiserv Invoices — Payment Links')

@section('content')
    <h1>Invoices — Payment Links</h1>
    <p class="subtitle">Create an invoice, get a link, open it (as the patient would) to pay via Commerce Hub's Hosted Checkout. Commerce Hub has no native "Pay by Link" yet — the link is our own; the Commerce Hub session behind it is minted fresh on each visit.</p>

    <div class="status-banner {{ $configured ? 'ok' : 'bad' }}">
        {{ $configured ? 'Credentials configured.' : 'FISERV_API_KEY / FISERV_API_SECRET / FISERV_MERCHANT_ID not set in .env yet.' }}
    </div>

    <div class="status-banner warn">
        Hosted Checkout calls <code>/payments-vas/v1/security/credentials</code> — a separate "Value Added Services" product
        from the core Terminal API (charges/refunds/cancels/inquiry). If opening a payment link fails with
        <em>"ApiKey and/or Authentication supplied are invalid"</em>, this sandbox app isn't entitled to VAS products yet —
        that's enabled per-app in Fiserv Developer Studio, not something fixable in this codebase.
    </div>

    @if (session('created_link'))
        <div class="card">
            <h2 style="margin-top:0;">Invoice created</h2>
            <p style="font-size: 13px; color: #555;">Share this link with the customer — it stays valid until the invoice is paid:</p>
            <code id="new-link">{{ session('created_link') }}</code>
            <button type="button" class="copy-btn" data-copy="{{ session('created_link') }}">Copy</button>
            <p style="margin-top: 10px;"><a href="{{ session('created_link') }}" target="_blank">Open it now →</a></p>
        </div>
    @endif

    <div class="card">
        <h2 style="margin-top:0;">New invoice</h2>
        <form method="POST" action="{{ route('fiserv.demo.invoices.store') }}">
            @csrf
            <label>Description</label>
            <input type="text" name="description" value="{{ old('description', 'Consultation deposit') }}">
            <label>Amount (USD)</label>
            <input type="text" name="amount" value="{{ old('amount', '25.00') }}" required>
            <button type="submit">Create invoice + link</button>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">All invoices</h2>
        <table>
            <thead>
                <tr><th>Created</th><th>Description</th><th>Amount</th><th>Status</th><th>Transaction ID</th><th>Link</th></tr>
            </thead>
            <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->created_at }}</td>
                    <td>{{ $invoice->description ?? '—' }}</td>
                    <td>${{ number_format((float) $invoice->amount, 2) }}</td>
                    <td><span class="badge {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td>
                    <td><code>{{ $invoice->transaction_id ?? '—' }}</code></td>
                    <td><a href="{{ route('fiserv.demo.pay', $invoice) }}" target="_blank">Open link →</a></td>
                </tr>
            @empty
                <tr><td colspan="6">No invoices yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $invoices->links() }}
    </div>
@endsection
