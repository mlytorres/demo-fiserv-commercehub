@extends('fiserv.layout')

@section('title', 'Fiserv Commerce Hub — Sandbox Test Harness')

@section('content')
    <h1>Fiserv Commerce Hub</h1>
    <p class="subtitle">Sandbox test harness — charge, pre-auth/capture, void, refund, and inquire against the real Commerce Hub sandbox.</p>

    <div class="status-banner {{ $configured ? 'ok' : 'bad' }}">
        {{ $configured ? 'Credentials configured — ready to test against the sandbox.' : 'FISERV_API_KEY / FISERV_API_SECRET / FISERV_MERCHANT_ID not set in .env yet.' }}
    </div>

    @if ($error)
        <div class="status-banner bad"><strong>Error:</strong> {{ $error }}</div>
    @endif

    <div class="stats-strip">
        <a href="{{ route('fiserv.demo.transactions') }}" class="stat-tile">
            <div class="stat-value">{{ $stats['transactions'] }}</div>
            <div class="stat-label">Transactions</div>
        </a>
        <a href="{{ route('fiserv.demo.transactions') }}" class="stat-tile tone-success">
            <div class="stat-value">{{ $stats['approval_rate'] !== null ? $stats['approval_rate'].'%' : '—' }}</div>
            <div class="stat-label">Approval rate</div>
        </a>
        <a href="{{ route('fiserv.demo.transactions') }}" class="stat-tile tone-warning">
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-label">Pending</div>
        </a>
        <a href="{{ route('fiserv.demo.transactions') }}" class="stat-tile tone-danger">
            <div class="stat-value">{{ $stats['declined'] }}</div>
            <div class="stat-label">Declined / failed</div>
        </a>
        <a href="{{ route('fiserv.demo.invoices') }}" class="stat-tile">
            <div class="stat-value">{{ $stats['invoices'] }}</div>
            <div class="stat-label">Invoices</div>
        </a>
        <a href="{{ route('fiserv.demo.webhook-logs') }}" class="stat-tile">
            <div class="stat-value">{{ $stats['webhook_logs'] }}</div>
            <div class="stat-label">Webhook logs</div>
        </a>
    </div>

    <div class="tabs" role="tablist">
        <button type="button" class="tab-btn" data-tab="charge">Charge / Pre-Auth</button>
        <button type="button" class="tab-btn" data-tab="capture">Capture</button>
        <button type="button" class="tab-btn" data-tab="void">Void</button>
        <button type="button" class="tab-btn" data-tab="refund">Refund</button>
        <button type="button" class="tab-btn" data-tab="inquire">Inquire</button>
    </div>

    <div class="tab-panel" data-tab-panel="charge">
        <div class="card">
            <h2>Test card presets</h2>
            <p class="hint" style="margin-top: -6px; margin-bottom: 12px;">
                Real Fiserv sandbox simulator data (developer.fiserv.com) — click one to fill the form below, then hit
                Charge or Pre-Auth. There is no self-service "decline" simulator in this sandbox tier (Fiserv's own docs
                say end-to-end decline/certification scripts come from your implementation specialist) — these presets
                cover what the sandbox actually documents: approvals per network, and CVV/AVS match-response simulation.
            </p>

            <div class="preset-group">
                <div class="preset-group-label">Approval — any amount under $5,000</div>
                <button type="button" class="preset-btn" data-preset='{"amount":"10.00","card":"4012001928706700","month":"12","year":"30","cvv":"777"}'>Visa</button>
                <button type="button" class="preset-btn" data-preset='{"amount":"10.00","card":"5500007287297385","month":"12","year":"30","cvv":"777"}'>Mastercard</button>
                <button type="button" class="preset-btn" data-preset='{"amount":"10.00","card":"341111227713933","month":"12","year":"30","cvv":"7777"}'>Amex</button>
                <button type="button" class="preset-btn" data-preset='{"amount":"10.00","card":"6011114305944707","month":"12","year":"30","cvv":"777"}'>Discover</button>
            </div>

            <div class="preset-group">
                <div class="preset-group-label">CVV (security code) response simulation</div>
                <button type="button" class="preset-btn" data-preset='{"amount":"5070.00","card":"4012001472472642","month":"12","year":"30","cvv":"777"}'>Visa — CVV MATCHED</button>
                <button type="button" class="preset-btn" data-preset='{"amount":"5071.00","card":"4012001472472642","month":"12","year":"30","cvv":"777"}'>Visa — CVV NOT_MATCHED</button>
            </div>

            <div class="preset-group" style="margin-bottom: 0;">
                <div class="preset-group-label">AVS (address) response simulation</div>
                <button type="button" class="preset-btn" data-preset='{"amount":"5080.00","card":"4012008565774776","month":"12","year":"30","cvv":"777"}'>Visa — AVS street+zip MATCHED</button>
                <button type="button" class="preset-btn" data-preset='{"amount":"5081.00","card":"4012008565774776","month":"12","year":"30","cvv":"777"}'>Visa — AVS street NOT_MATCHED</button>
            </div>
        </div>

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
                <button type="submit">Charge (sale)</button>
                <button type="submit" class="secondary" formaction="{{ route('fiserv.demo.preauth') }}" data-loading-label="Authorizing…">Pre-Auth only (hold, don't capture)</button>
                <p class="hint">Sale captures immediately. Pre-Auth just holds the funds — capture it on the Capture tab once you have a transaction ID.</p>
            </form>
        </div>
    </div>

    <div class="tab-panel" data-tab-panel="capture">
        <div class="card">
            <h2>Capture a Pre-Auth</h2>
            <form method="POST" action="{{ route('fiserv.demo.capture') }}" data-loading-label="Capturing…">
                @csrf
                <label>Pre-Auth transaction ID</label>
                <input type="text" name="transaction_id" value="{{ old('transaction_id', $lastTransactionId) }}" placeholder="from a Pre-Auth result below" required>
                <label>Amount (USD)</label>
                <input type="text" name="amount" value="{{ old('amount', '10.00') }}" required>
                <button type="submit">Capture</button>
            </form>
        </div>
    </div>

    <div class="tab-panel" data-tab-panel="void">
        <div class="card">
            <h2>Void (same-day cancel)</h2>
            <form method="POST" action="{{ route('fiserv.demo.void') }}" data-loading-label="Voiding…">
                @csrf
                <label>Transaction ID</label>
                <input type="text" name="transaction_id" value="{{ old('transaction_id', $lastTransactionId) }}" placeholder="an un-settled auth or sale" required>
                <label>Amount (USD) — leave blank for a full void</label>
                <input type="text" name="amount" value="{{ old('amount') }}" placeholder="optional, for a partial void">
                <label>Reason</label>
                <select name="reason">
                    <option value="VOID" selected>Void (standard cancellation)</option>
                    <option value="SUSPECTED_FRAUD">Suspected fraud</option>
                    <option value="TIMEOUT">Timeout</option>
                    <option value="CARD_OVERRIDE">Card override</option>
                    <option value="EDIT_ERROR">Edit error</option>
                    <option value="MAC_VERIFICATION_ERROR">MAC verification error</option>
                </select>
                <p class="hint">Only works against an authorization or sale that hasn't settled/batched yet — Commerce Hub rejects a void against a settled transaction (use Refund for that).</p>
                <button type="submit" class="danger">Void</button>
            </form>
        </div>
    </div>

    <div class="tab-panel" data-tab-panel="refund">
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
    </div>

    <div class="tab-panel" data-tab-panel="inquire">
        <div class="card">
            <h2>Status inquiry</h2>
            <form method="POST" action="{{ route('fiserv.demo.inquire') }}" data-loading-label="Checking…">
                @csrf
                <label>Transaction ID</label>
                <input type="text" name="transaction_id" value="{{ old('transaction_id', $lastTransactionId) }}" placeholder="from a charge result below" required>
                <button type="submit">Check status</button>
            </form>
        </div>
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
                <h2 style="margin-bottom: 6px;">Last result — {{ ucwords(str_replace('_', ' ', $result['action'])) }}</h2>
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
@endsection

@push('scripts')
    <script>
        // Tabs: default to the operation matching the just-submitted action
        // (server-provided via {{ $activeTab }}), so landing back here after
        // e.g. a Void shows the Void tab and its result without an extra click.
        (function () {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const panels = document.querySelectorAll('.tab-panel');

            function activate(tab) {
                tabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.tab === tab));
                panels.forEach((panel) => panel.classList.toggle('active', panel.dataset.tabPanel === tab));
            }

            tabButtons.forEach((btn) => btn.addEventListener('click', () => activate(btn.dataset.tab)));

            activate(@json($activeTab));
        })();

        // Test card presets: fill the charge form (amount/card/exp/cvv) without submitting,
        // so the tester can still choose Charge vs. Pre-Auth themselves.
        document.querySelectorAll('.preset-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const preset = JSON.parse(btn.dataset.preset);
                document.querySelector('input[name="amount"]').value = preset.amount;
                document.querySelector('input[name="card_number"]').value = preset.card;
                document.querySelector('input[name="exp_month"]').value = preset.month;
                document.querySelector('input[name="exp_year"]').value = preset.year;
                document.querySelector('input[name="cvv"]').value = preset.cvv;
            });
        });
    </script>

    @if ($result || $error)
        @php
            if ($error) {
                // rememberFailure() (e.g. a connection timeout) never reaches Commerce
                // Hub, so there's no $result — just the exception message.
                $toastTone = 'declined';
                $toastTitle = 'Error';
                $toastMessage = $error;
            } else {
                $toastTone = match (true) {
                    ($result['approved'] ?? false) === true => 'success',
                    in_array($result['status_label'] ?? null, ['Authorized', 'Waiting'], true) => 'warning',
                    default => 'declined',
                };
                $toastTitle = ucwords(str_replace('_', ' ', $result['action']))
                    .' — '.($result['status_label'] ?? ($toastTone === 'success' ? 'Approved' : 'Declined'));
                $toastMessage = $result['failure_reason']
                    ?? ($result['transaction_id'] ? 'Transaction '.$result['transaction_id'] : null);
            }
        @endphp
        <script>
            window.fiservToast(@json($toastTitle), @json($toastMessage), @json($toastTone));
        </script>
    @endif
@endpush
