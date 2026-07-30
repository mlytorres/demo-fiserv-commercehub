<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fiserv Commerce Hub — Sandbox Test Harness</title>
    <style>
        :root {
            --green: #0a5c36;
            --green-bg: #e6f4ea;
            --green-text: #1e7e34;
            --red-bg: #fdecea;
            --red-text: #b3261e;
            --amber-bg: #fff4e0;
            --amber-text: #8a5a00;
            --border: #e3e3e3;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, "Segoe UI", sans-serif;
            max-width: 780px;
            margin: 40px auto;
            padding: 0 20px;
            color: #1a1a1a;
            background: #fafafa;
        }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .subtitle { color: #666; font-size: 13px; margin-top: 0; margin-bottom: 20px; }
        h2 { font-size: 15px; margin: 0 0 12px; }
        .status-banner { padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
        .status-banner.ok { background: var(--green-bg); color: var(--green-text); }
        .status-banner.bad { background: var(--red-bg); color: var(--red-text); }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 16px;
        }

        label { display: block; font-size: 13px; font-weight: 500; margin: 10px 0 4px; color: #333; }
        input {
            width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px;
            font-size: 14px; box-sizing: border-box;
        }
        input:focus { outline: 2px solid var(--green); outline-offset: 1px; border-color: var(--green); }

        button[type="submit"] {
            margin-top: 14px; padding: 9px 18px; border: none; border-radius: 6px;
            background: var(--green); color: white; cursor: pointer; font-size: 14px; font-weight: 500;
        }
        button[type="submit"]:disabled { opacity: .6; cursor: wait; }

        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }

        a { color: var(--green); }

        /* Result summary card */
        .result-card { display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 600; letter-spacing: .02em; text-transform: uppercase;
        }
        .badge.approved { background: var(--green-bg); color: var(--green-text); }
        .badge.declined { background: var(--red-bg); color: var(--red-text); }
        .badge.pending  { background: var(--amber-bg); color: var(--amber-text); }

        .result-fields { display: grid; grid-template-columns: auto 1fr; gap: 4px 12px; font-size: 13px; margin-top: 10px; }
        .result-fields dt { color: #666; }
        .result-fields dd { margin: 0; font-family: ui-monospace, Menlo, monospace; }

        .copy-btn {
            border: 1px solid #ccc; background: #fff; border-radius: 5px; font-size: 11px;
            padding: 1px 7px; cursor: pointer; margin-left: 6px; color: #444;
        }
        .copy-btn:active { background: #eee; }

        details { margin-top: 14px; }
        summary { cursor: pointer; font-size: 13px; color: #555; user-select: none; }
        pre { background: #f5f5f5; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 12px; margin-top: 8px; }

        nav.top-links { text-align: right; margin-bottom: 8px; font-size: 13px; }
    </style>
</head>
<body>
    <nav class="top-links">
        <a href="{{ route('fiserv.demo.transactions') }}">View transaction history →</a>
        &nbsp;·&nbsp;
        <a href="{{ route('fiserv.demo.webhook-logs') }}">View webhook logs →</a>
    </nav>

    <h1>Fiserv Commerce Hub</h1>
    <p class="subtitle">Sandbox test harness — charge, refund, and inquire against the real Commerce Hub sandbox.</p>

    <div class="status-banner {{ $configured ? 'ok' : 'bad' }}">
        {{ $configured ? 'Credentials configured — ready to test against the sandbox.' : 'FISERV_API_KEY / FISERV_API_SECRET / FISERV_MERCHANT_ID not set in .env yet.' }}
    </div>

    @if ($error)
        <div class="status-banner bad"><strong>Error:</strong> {{ $error }}</div>
    @endif

    <div class="card">
        <h2>Charge a test card</h2>
        <form method="POST" action="{{ route('fiserv.demo.charge') }}" data-loading-label="Charging…">
            @csrf
            <label>Amount (USD)</label>
            <input type="text" name="amount" value="{{ old('amount', '10.00') }}" required>
            <label>Card number</label>
            <input type="text" name="card_number" value="{{ old('card_number', '4111111111111111') }}" required>
            <div class="row">
                <div>
                    <label>Exp month (MM)</label>
                    <input type="text" name="exp_month" value="{{ old('exp_month', '12') }}" required>
                </div>
                <div>
                    <label>Exp year (YY)</label>
                    <input type="text" name="exp_year" value="{{ old('exp_year', '28') }}" required>
                </div>
                <div>
                    <label>CVV</label>
                    <input type="text" name="cvv" value="{{ old('cvv', '123') }}" required>
                </div>
            </div>
            <button type="submit">Charge</button>
        </form>
    </div>

    <div class="card">
        <h2>Refund</h2>
        <form method="POST" action="{{ route('fiserv.demo.refund') }}" data-loading-label="Refunding…">
            @csrf
            <label>Original transaction ID</label>
            <input type="text" name="transaction_id" value="{{ old('transaction_id', $lastTransactionId) }}" placeholder="from a charge result below" required>
            <label>Amount (USD)</label>
            <input type="text" name="amount" value="{{ old('amount', '5.00') }}" required>
            <button type="submit">Refund</button>
        </form>
    </div>

    <div class="card">
        <h2>Status inquiry</h2>
        <form method="POST" action="{{ route('fiserv.demo.inquire') }}" data-loading-label="Checking…">
            @csrf
            <label>Transaction ID</label>
            <input type="text" name="transaction_id" value="{{ old('transaction_id', $lastTransactionId) }}" placeholder="from a charge result below" required>
            <button type="submit">Check status</button>
        </form>
    </div>

    @if ($result)
        @php
            $badgeClass = match (true) {
                ($result['approved'] ?? false) === true => 'approved',
                in_array($result['status_label'] ?? null, ['Authorized', 'Waiting'], true) => 'pending',
                default => 'declined',
            };
        @endphp
        <div class="card result-card">
            <div>
                <h2 style="margin-bottom: 6px;">Last result — {{ ucfirst($result['action']) }}</h2>
                <span class="badge {{ $badgeClass }}">
                    {{ $result['status_label'] ?? (($result['approved'] ?? false) ? 'Approved' : 'Declined') }}
                </span>

                <dl class="result-fields">
                    @if (!empty($result['transaction_id']))
                        <dt>Transaction ID</dt>
                        <dd>
                            {{ $result['transaction_id'] }}
                            <button type="button" class="copy-btn" data-copy="{{ $result['transaction_id'] }}">Copy</button>
                        </dd>
                    @endif
                    @if (isset($result['amount']) && $result['amount'] !== null)
                        <dt>Amount</dt>
                        <dd>${{ number_format((float) $result['amount'], 2) }}</dd>
                    @endif
                    @if (!empty($result['failure_reason']))
                        <dt>Reason</dt>
                        <dd style="color: var(--red-text);">{{ $result['failure_reason'] }}</dd>
                    @endif
                </dl>

                <details>
                    <summary>View raw response</summary>
                    <pre>{{ json_encode($result['raw'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                </details>
            </div>
        </div>
    @endif

    <script>
        // Disable + relabel the submit button while the sandbox round-trip is in flight,
        // so a slow response doesn't invite a double-click/double-charge.
        document.querySelectorAll('form[data-loading-label]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = true;
                button.dataset.originalLabel = button.textContent;
                button.textContent = form.dataset.loadingLabel;
            });
        });

        // Copy transaction id to clipboard.
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
