<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Miami Life Cosmetic Center · @yield('title', 'Fiserv Commerce Hub Sandbox')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
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
        body.narrow { max-width: 480px; }
        body.wide { max-width: 960px; }

        h1 { font-size: 20px; margin-bottom: 4px; }
        .subtitle { color: #666; font-size: 13px; margin-top: 0; margin-bottom: 20px; }
        h2 { font-size: 15px; margin: 0 0 12px; }

        a { color: var(--green); }

        /* Top nav, shared across every demo page */
        nav.top-nav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }
        nav.top-nav .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        nav.top-nav .brand img { height: 30px; width: auto; display: block; }
        nav.top-nav .brand-text { display: flex; flex-direction: column; line-height: 1.25; }
        nav.top-nav .brand-text .brand-name { font-weight: 700; font-size: 14px; color: #1a1a1a; }
        nav.top-nav .brand-text .brand-subtitle { font-size: 11px; color: #888; }
        nav.top-nav .links { display: flex; gap: 4px; flex-wrap: wrap; }
        nav.top-nav .links a {
            font-size: 13px; text-decoration: none; color: #555; padding: 5px 10px; border-radius: 6px;
        }
        nav.top-nav .links a:hover { background: #f0f0f0; }
        nav.top-nav .links a.active { background: var(--green-bg); color: var(--green-text); font-weight: 600; }
        nav.top-nav .logout-btn {
            border: none; background: none; cursor: pointer; font-size: 13px; color: #888;
            padding: 5px 10px; border-radius: 6px;
        }
        nav.top-nav .logout-btn:hover { background: #f0f0f0; color: #333; }

        /* App footer */
        footer.app-footer {
            margin-top: 48px;
            padding-top: 20px;
            padding-bottom: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #777;
        }
        footer.app-footer .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        footer.app-footer .footer-brand img {
            height: 22px;
            width: auto;
        }
        footer.app-footer .footer-brand .brand-title {
            font-weight: 600;
            color: #333;
        }
        footer.app-footer .env-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0f0f0;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            color: #555;
        }
        footer.app-footer .env-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
        }

        .status-banner { padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
        .status-banner.ok { background: var(--green-bg); color: var(--green-text); }
        .status-banner.bad { background: var(--red-bg); color: var(--red-text); }
        .status-banner.warn { background: var(--amber-bg); color: var(--amber-text); }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 16px;
        }

        label { display: block; font-size: 13px; font-weight: 500; margin: 10px 0 4px; color: #333; }
        input, select {
            width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px;
            font-size: 14px; box-sizing: border-box; background: #fff;
        }
        input:focus, select:focus { outline: 2px solid var(--green); outline-offset: 1px; border-color: var(--green); }

        button[type="submit"] {
            margin-top: 14px; padding: 9px 18px; border: none; border-radius: 6px;
            background: var(--green); color: white; cursor: pointer; font-size: 14px; font-weight: 500;
        }
        button[type="submit"]:disabled { opacity: .6; cursor: wait; }
        button[type="submit"].secondary {
            background: #fff; color: var(--green); border: 1px solid var(--green);
            margin-left: 8px;
        }
        button[type="submit"].danger { background: var(--red-text); }
        .hint { font-size: 12px; color: #888; margin-top: 4px; }

        .preset-group { margin-bottom: 12px; }
        .preset-group-label { font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 6px; }
        .preset-btn {
            display: inline-block; margin: 0 6px 6px 0; padding: 5px 10px; border-radius: 999px;
            border: 1px solid var(--border); background: #fafafa; color: #333; font-size: 12px;
            cursor: pointer;
        }
        .preset-btn:hover { border-color: var(--green); color: var(--green); }

        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }

        /* Dashboard stat strip */
        .stats-strip { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .stat-tile {
            flex: 1; min-width: 108px; background: #fff; border: 1px solid var(--border); border-radius: 10px;
            padding: 12px 14px; text-decoration: none; color: inherit; transition: border-color .15s;
        }
        .stat-tile:hover { border-color: var(--green); }
        .stat-tile .stat-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
        .stat-tile .stat-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .03em; margin-top: 2px; }
        .stat-tile.tone-success .stat-value { color: var(--green-text); }
        .stat-tile.tone-danger .stat-value { color: var(--red-text); }
        .stat-tile.tone-warning .stat-value { color: var(--amber-text); }

        /* Tabs */
        .tabs { display: flex; gap: 4px; margin-bottom: 16px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
        .tab-btn {
            padding: 9px 14px; border: none; background: none; cursor: pointer; font-size: 13px; font-weight: 500;
            color: #666; border-bottom: 2px solid transparent; margin-bottom: -1px;
        }
        .tab-btn:hover { color: var(--green); }
        .tab-btn.active { color: var(--green); border-bottom-color: var(--green); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Result summary card */
        .result-card { display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap; }

        /* Badge system: three semantic tones (success/danger/warning), plus
           domain-specific aliases so each page's existing status strings
           (approved/paid/processed, declined/failed, pending/invalid_signature)
           map onto the same three colors without rewriting page-level logic. */
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 600; letter-spacing: .02em; text-transform: uppercase;
        }
        .badge.success, .badge.approved, .badge.paid, .badge.processed { background: var(--green-bg); color: var(--green-text); }
        .badge.danger, .badge.declined, .badge.failed { background: var(--red-bg); color: var(--red-text); }
        .badge.warning, .badge.pending, .badge.invalid_signature { background: var(--amber-bg); color: var(--amber-text); }

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

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; }
        code { font-size: 12px; }

        /* Toasts: immediate glanceable feedback for an action (approved/declined/
           pending/error) on page load, separate from the full "Last result" card —
           the toast is the "did it work?" answer, the card is the detail underneath. */
        .toast-container {
            position: fixed; top: 16px; right: 16px; z-index: 1000;
            display: flex; flex-direction: column; gap: 8px;
            width: 340px; max-width: calc(100vw - 32px);
        }
        .toast {
            display: flex; align-items: flex-start; gap: 10px;
            background: #fff; border: 1px solid var(--border); border-radius: 10px;
            padding: 12px 14px; box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
            opacity: 0; transform: translateX(16px);
            transition: opacity .18s ease-out, transform .18s ease-out;
        }
        .toast.toast-visible { opacity: 1; transform: translateX(0); }
        .toast-icon {
            flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; margin-top: 1px;
        }
        .toast-success .toast-icon { background: var(--green-bg); color: var(--green-text); }
        .toast-declined .toast-icon { background: var(--red-bg); color: var(--red-text); }
        .toast-warning .toast-icon { background: var(--amber-bg); color: var(--amber-text); }
        .toast-body { flex: 1; min-width: 0; }
        .toast-title { font-size: 13px; font-weight: 600; color: #1a1a1a; }
        .toast-message { font-size: 12px; color: #666; margin-top: 2px; word-break: break-word; }
        .toast-close {
            flex-shrink: 0; border: none; background: none; cursor: pointer;
            font-size: 15px; line-height: 1; color: #aaa; padding: 2px;
        }
        .toast-close:hover { color: #444; }

        @stack('styles')
    </style>
</head>
<body class="@yield('body_class')">
    <div id="toast-container" class="toast-container" aria-live="polite"></div>

    @hasSection('nav')
        @yield('nav')
    @else
        <nav class="top-nav">
            <a class="brand" href="{{ route('fiserv.demo.index') }}">
                <img src="{{ asset('images/miami-life-icon.png') }}" alt="Miami Life Cosmetic Center">
                <span class="brand-text">
                    <span class="brand-name">Miami Life Cosmetic Center</span>
                    <span class="brand-subtitle">Fiserv Commerce Hub sandbox · Bank of America Merchant Services</span>
                </span>
            </a>
            <div class="links">
                <a href="{{ route('fiserv.demo.index') }}" class="{{ request()->routeIs('fiserv.demo.index') ? 'active' : '' }}">Charge / Capture / Void / Refund</a>
                <a href="{{ route('fiserv.demo.invoices') }}" class="{{ request()->routeIs('fiserv.demo.invoices*') ? 'active' : '' }}">Invoices / payment links</a>
                <a href="{{ route('fiserv.demo.transactions') }}" class="{{ request()->routeIs('fiserv.demo.transactions') ? 'active' : '' }}">Transaction history</a>
                <a href="{{ route('fiserv.demo.webhook-logs') }}" class="{{ request()->routeIs('fiserv.demo.webhook-logs') ? 'active' : '' }}">Webhook logs</a>
            </div>
            <form method="POST" action="{{ route('fiserv.demo.logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">Log out</button>
            </form>
        </nav>
    @endif

    @yield('content')

    <footer class="app-footer">
        <div class="footer-brand">
            <img src="{{ asset('images/miami-life-icon.png') }}" alt="Miami Life Cosmetic Center">
            <span class="brand-title">Miami Life Cosmetic Center</span>
            <span>·</span>
            <span>© {{ date('Y') }}</span>
        </div>
        <div class="env-badge">
            <span class="dot"></span> Fiserv Commerce Hub Sandbox · Bank of America Merchant Services
        </div>
    </footer>

    <script>
        // Disable + relabel the submit button while a sandbox round-trip is in
        // flight, so a slow response doesn't invite a double-click/double-charge.
        document.querySelectorAll('form[data-loading-label]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                // event.submitter is the actual button that triggered this submit —
                // matters here because some forms (e.g. Charge vs. Pre-Auth) have more
                // than one submit button with different formaction targets.
                const button = event.submitter || form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = true;
                button.dataset.originalLabel = button.textContent;
                button.textContent = button.dataset.loadingLabel || form.dataset.loadingLabel;
            });
        });

        // Copy-to-clipboard buttons (transaction ids, links, etc.).
        document.querySelectorAll('.copy-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.copy).then(() => {
                    const original = btn.textContent;
                    btn.textContent = 'Copied!';
                    setTimeout(() => { btn.textContent = original; }, 1200);
                });
            });
        });

        // Toast notifications — call window.fiservToast(title, message, tone) from any
        // page's own script block (tone: 'success' | 'declined' | 'warning'). Used to
        // surface an immediate pass/fail signal for an action on page load, since these
        // are server-rendered redirects (not AJAX) — there's no in-flight request to
        // react to, just the result already baked into the page when it loads.
        window.fiservToast = function (title, message, tone) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'toast toast-' + tone;

            const icon = document.createElement('div');
            icon.className = 'toast-icon';
            icon.textContent = tone === 'success' ? '✓' : tone === 'warning' ? '…' : '✕';
            toast.appendChild(icon);

            const body = document.createElement('div');
            body.className = 'toast-body';

            const titleEl = document.createElement('div');
            titleEl.className = 'toast-title';
            titleEl.textContent = title;
            body.appendChild(titleEl);

            if (message) {
                const messageEl = document.createElement('div');
                messageEl.className = 'toast-message';
                messageEl.textContent = message;
                body.appendChild(messageEl);
            }

            toast.appendChild(body);

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'toast-close';
            closeBtn.setAttribute('aria-label', 'Dismiss');
            closeBtn.textContent = '×';
            toast.appendChild(closeBtn);

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('toast-visible'));

            const dismiss = () => {
                toast.classList.remove('toast-visible');
                setTimeout(() => toast.remove(), 200);
            };
            closeBtn.addEventListener('click', dismiss);
            setTimeout(dismiss, 6000);
        };
    </script>

    @stack('scripts')
</body>
</html>
