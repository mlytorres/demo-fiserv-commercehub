@extends('fiserv.layout')

@section('title', 'Payment ' . ($success ? 'complete' : 'failed'))
@section('body_class', 'narrow')
@section('nav')<!-- customer-facing page: no internal staff nav -->@endsection

@push('styles')
    body.narrow { margin-top: 80px; text-align: center; }
    .icon { font-size: 40px; margin-bottom: 10px; }
    .ok { color: var(--green-text); }
    .bad { color: var(--red-text); }
    .amount { font-size: 22px; font-weight: 700; margin: 14px 0; }
@endpush

@section('content')
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
@endsection
