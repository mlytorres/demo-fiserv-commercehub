@extends('fiserv.layout')

@section('title', 'Pay invoice — ' . ($invoice->description ?? 'Fiserv demo'))
@section('body_class', 'narrow')
@section('nav')<!-- customer-facing page: no internal staff nav -->@endsection

@push('styles')
    #checkout-container { margin-top: 20px; min-height: 320px; border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
    #checkout-status { font-size: 13px; color: #666; margin-top: 10px; }
    .amount { font-size: 28px; font-weight: 700; margin: 10px 0 20px; }
@endpush

@section('content')
    <h1>{{ $invoice->description ?? 'Payment' }}</h1>
    <p class="subtitle">Invoice link — pay securely below.</p>
    <div class="amount">${{ number_format((float) $invoice->amount, 2) }}</div>

    <div id="checkout-container"></div>
    <div id="checkout-status">Loading secure checkout…</div>
@endsection

@push('scripts')
    <script src="{{ $sdkUrl }}"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const statusEl = document.getElementById('checkout-status');

            if (!window.fiserv || !window.fiserv.components) {
                statusEl.textContent = 'Could not load Commerce Hub\'s checkout SDK. Check FISERV_HOSTED_SDK_URL and that this domain is whitelisted.';
                return;
            }

            try {
                window.fiserv.components.hostedCheckout({
                    credentials: @json($credentials),
                    integrationOptions: {
                        type: 'REDIRECT',
                        onCompleteUrl: '{{ route('fiserv.demo.pay.complete', $invoice) }}',
                    },
                });
                statusEl.textContent = '';
            } catch (error) {
                statusEl.textContent = 'Checkout failed to initialize: ' + error.message;
            }
        });
    </script>
@endpush
