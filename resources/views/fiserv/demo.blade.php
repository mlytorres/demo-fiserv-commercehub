<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fiserv Commerce Hub — Sandbox Test Harness</title>
    <style>
        body { font-family: -apple-system, Segoe UI, sans-serif; max-width: 760px; margin: 40px auto; padding: 0 20px; color: #1a1a1a; }
        h1 { font-size: 20px; }
        h2 { font-size: 16px; margin-top: 36px; }
        .status { padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
        .status.ok { background: #e6f4ea; color: #1e7e34; }
        .status.bad { background: #fdecea; color: #b3261e; }
        form { border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        label { display: block; font-size: 13px; margin: 8px 0 4px; }
        input { width: 100%; padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 12px; padding: 8px 16px; border: none; border-radius: 6px; background: #0a5c36; color: white; cursor: pointer; }
        pre { background: #f5f5f5; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
        a { color: #0a5c36; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
    </style>
</head>
<body>
    <h1>Fiserv Commerce Hub — Sandbox Test Harness</h1>

    <div class="status {{ $configured ? 'ok' : 'bad' }}">
        {{ $configured ? 'Credentials configured — ready to test against the sandbox.' : 'FISERV_API_KEY / FISERV_API_SECRET / FISERV_MERCHANT_ID not set in .env yet.' }}
    </div>

    @if ($error)
        <div class="status bad"><strong>Error:</strong> {{ $error }}</div>
    @endif

    @if ($result)
        <h2>Last result — {{ $result['action'] }}</h2>
        <pre>{{ json_encode($result, JSON_PRETTY_PRINT) }}</pre>
    @endif

    <h2>Charge a test card</h2>
    <form method="POST" action="{{ route('fiserv.demo.charge') }}">
        @csrf
        <label>Amount (USD)</label>
        <input type="text" name="amount" value="10.00" required>
        <label>Card number</label>
        <input type="text" name="card_number" value="4111111111111111" required>
        <div class="row">
            <div>
                <label>Exp month (MM)</label>
                <input type="text" name="exp_month" value="12" required>
            </div>
            <div>
                <label>Exp year (YY)</label>
                <input type="text" name="exp_year" value="28" required>
            </div>
            <div>
                <label>CVV</label>
                <input type="text" name="cvv" value="123" required>
            </div>
        </div>
        <button type="submit">Charge</button>
    </form>

    <h2>Refund</h2>
    <form method="POST" action="{{ route('fiserv.demo.refund') }}">
        @csrf
        <label>Original transaction ID</label>
        <input type="text" name="transaction_id" placeholder="from a charge result above" required>
        <label>Amount (USD)</label>
        <input type="text" name="amount" value="10.00" required>
        <button type="submit">Refund</button>
    </form>

    <h2>Status inquiry</h2>
    <form method="POST" action="{{ route('fiserv.demo.inquire') }}">
        @csrf
        <label>Transaction ID</label>
        <input type="text" name="transaction_id" placeholder="from a charge result above" required>
        <button type="submit">Check status</button>
    </form>

    <p><a href="{{ route('fiserv.demo.webhook-logs') }}">View webhook logs →</a></p>
</body>
</html>
