<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment {{ $success ? 'complete' : 'failed' }}</title>
    <style>
        body { font-family: -apple-system, "Segoe UI", sans-serif; max-width: 480px; margin: 80px auto; padding: 0 20px; color: #1a1a1a; text-align: center; }
        .icon { font-size: 40px; margin-bottom: 10px; }
        h1 { font-size: 20px; margin-bottom: 6px; }
        .ok { color: #1e7e34; }
        .bad { color: #b3261e; }
        .amount { font-size: 22px; font-weight: 700; margin: 14px 0; }
        code { font-size: 12px; color: #666; }
    </style>
</head>
<body>
    @if ($success)
        <div class="icon ok">✓</div>
        <h1 class="ok">{{ $alreadyPaid ?? false ? 'Already paid' : 'Payment received' }}</h1>
        <div class="amount">${{ number_format((float) $invoice->amount, 2) }}</div>
        @if (!empty($transactionId))
            <p>Transaction: <code>{{ $transactionId }}</code></p>
        @elseif ($invoice->transaction_id)
            <p>Transaction: <code>{{ $invoice->transaction_id }}</code></p>
        @endif
    @else
        <div class="icon bad">✕</div>
        <h1 class="bad">Payment not completed</h1>
        <p style="color:#555; font-size: 13px;">{{ $error ?? 'Something went wrong.' }}</p>
    @endif
</body>
</html>
