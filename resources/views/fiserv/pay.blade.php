@extends('fiserv.layout')

@section('title', 'Pay invoice — ' . ($invoice->description ?? 'Fiserv demo'))
@section('body_class', 'narrow')
@section('nav')<!-- customer-facing page: no internal staff nav -->@endsection

@push('styles')
    #checkout-container { margin-top: 20px; min-height: 320px; border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
    #checkout-status { font-size: 13px; color: #666; margin-top: 10px; }
    .amount { font-size: 28px; font-weight: 700; margin: 10px 0 20px; }
    .sim-banner { background: var(--amber-bg, #fff8e6); color: var(--amber-text, #92650a); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; font-size: 12px; margin-bottom: 16px; }
    .sim-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
    .sim-actions a { display: inline-block; padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border); text-decoration: none; font-size: 13px; font-weight: 600; }
    .sim-actions a.sim-success { background: var(--green-bg, #eaf7ee); color: var(--green-text, #157a3d); border-color: var(--green-text, #157a3d); }
    .sim-actions a.sim-failed { background: var(--red-bg, #fdecec); color: var(--red-text, #b3261e); border-color: var(--red-text, #b3261e); }
    .sim-actions a.sim-abandon { color: #666; }
@endpush

@section('content')
    <h1>{{ $invoice->description ?? 'Payment' }}</h1>
    <p class="subtitle">Invoice link — pay securely below.</p>
    <div class="amount">${{ number_format((float) $invoice->amount, 2) }}</div>

    @if ($simulated)
        <div class="sim-banner">
            Simulation mode (<code>FISERV_SIMULATE_SUCCESS=true</code>) — Commerce Hub's real
            Hosted Checkout SDK only runs on Fiserv's own servers, so it can't render here with
            a fake session. Pick an outcome below to exercise the rest of the flow
            (<code>complete()</code>, transaction recording, invoice status) exactly as Commerce
            Hub's real redirect would trigger it.
        </div>
        <div id="checkout-container">
            <p style="margin: 0 0 4px; font-size: 13px; color: #666;">Simulated session: <code>{{ $sessionId }}</code></p>
            <div class="sim-actions">
                <a class="sim-success" href="{{ route('fiserv.demo.pay.complete', ['invoice' => $invoice, 'cardCaptureResult' => 'SUCCESS', 'sessionId' => $sessionId]) }}">Simulate successful payment</a>
                <a class="sim-failed" href="{{ route('fiserv.demo.pay.complete', ['invoice' => $invoice, 'cardCaptureResult' => 'FAILED', 'sessionId' => $sessionId]) }}">Simulate declined payment</a>
                <a class="sim-abandon" href="{{ route('fiserv.demo.pay.complete', ['invoice' => $invoice]) }}">Simulate customer abandonment</a>
            </div>
        </div>
    @else
        <div id="checkout-container"></div>
        <div id="checkout-status">Loading secure checkout…</div>
    @endif
@endsection

@push('scripts')
    @unless ($simulated)
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
    @endunless
@endpush
