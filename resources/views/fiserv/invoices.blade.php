<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fiserv Invoices — Payment Links</title>
    <style>
        :root {
            --green: #0a5c36; --green-bg: #e6f4ea; --green-text: #1e7e34;
            --red-bg: #fdecea; --red-text: #b3261e;
            --amber-bg: #fff4e0; --amber-text: #8a5a00;
            --border: #e3e3e3;
        }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, "Segoe UI", sans-serif; max-width: 820px; margin: 40px auto; padding: 0 20px; color: #1a1a1a; background: #fafafa; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .subtitle { color: #666; font-size: 13px; margin-top: 0; margin-bottom: 20px; }
        nav.top-links { text-align: right; margin-bottom: 8px; font-size: 13px; }
        a { color: var(--green); }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 18px 20px; margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 500; margin: 10px 0 4px; color: #333; }
        input { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        button[type="submit"] { margin-top: 14px; padding: 9px 18px; border: none; border-radius: 6px; background: var(--green); color: white; cursor: pointer; font-size: 14px; font-weight: 500; }
        .status-banner { padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
        .status-banner.ok { background: var(--green-bg); color: var(--green-text); }
        .status-banner.bad { background: var(--red-bg); color: var(--red-text); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; }
        .badge.paid { background: var(--green-bg); color: var(--green-text); }
        .badge.failed { background: var(--red-bg); color: var(--red-text); }
        .badge.pending { background: var(--amber-bg); color: var(--amber-text); }
        code { font-size: 12px; }
        .copy-btn { border: 1px solid #ccc; background: #fff; border-radius: 5px; font-size: 11px; padding: 1px 7px; cursor: pointer; margin-left: 6px; color: #444; }
    </style>
</head>
<body>
    <nav class="top-links">
        <a href="{{ route('fiserv.demo.index') }}">← Charge / refund / inquire</a>
        &nbsp;·&nbsp;
        <a href="{{ route('fiserv.demo.transactions') }}">Transaction history →</a>
    </nav>

    <h1>Invoices — Payment Links</h1>
    <p class="subtitle">Create an invoice, get a link, open it (as the patient would) to pay via Commerce Hub's Hosted Checkout. Commerce Hub has no native "Pay by Link" yet — the link is our own; the Commerce Hub session behind it is minted fresh on each visit.</p>

    <div class="status-banner {{ $configured ? 'ok' : 'bad' }}">
        {{ $configured ? 'Credentials configured.' : 'FISERV_API_KEY / FISERV_API_SECRET / FISERV_MERCHANT_ID not set in .env yet.' }}
    </div>

    @if (session('created_link'))
        <div class="card">
            <h2 style="margin-top:0; font-size: 15px;">Invoice created</h2>
            <p style="font-size: 13px; color: #555;">Share this link with the customer — it stays valid until the invoice is paid:</p>
            <code id="new-link">{{ session('created_link') }}</code>
            <button type="button" class="copy-btn" data-copy="{{ session('created_link') }}">Copy</button>
            <p style="margin-top: 10px;"><a href="{{ session('created_link') }}" target="_blank">Open it now →</a></p>
        </div>
    @endif

    <div class="card">
        <h2 style="margin-top:0; font-size: 15px;">New invoice</h2>
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
        <h2 style="margin-top:0; font-size: 15px;">All invoices</h2>
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

    <script>
        document.querySelectorAll('.copy-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.copy).then(() => {
                    const original = btn.textContent;
                    btn.textContent = 'Copied!';
                    setTimeout(() => { btn.textContent = original; }, 1200);
                });
            });
        });
    </script>
</body>
</html>
