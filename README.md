# Fiserv Commerce Hub — Sandbox Demo

A small Laravel app for hand-testing [`mlytorres/laravel-fiserv-commercehub`](../miamilife-crm/packages/laravel-fiserv-commercehub) against the real Fiserv Commerce Hub sandbox before that package gets wired into [MiamiLife CRM](../miamilife-crm). This is a test harness, not production code — no real patients, no real money, built for **Miami Life Cosmetic Center**'s team to click through every payment operation and see exactly what Commerce Hub sends back.

Everything here runs against Fiserv's live cert sandbox (not mocks) unless simulation mode is on — see [Simulation mode](#simulation-mode) below.

## What's implemented

- **Charge (sale)** — auth + capture in one step
- **Pre-Auth + Capture** — hold funds now, capture later
- **Void** — full or partial, with a reversal-reason picker, same-day/pre-settlement only
- **Refund** — against an already-settled transaction
- **Status inquiry** — look up any transaction id
- **Test-card presets** — real Fiserv-documented sandbox cards for approvals per network, plus CVV/AVS match-response simulation (there's no self-service decline simulator in this sandbox tier — see the note on the Charge tab)
- **Invoices / payment links** — generates a shareable link per invoice; opening it mints a fresh Commerce Hub Hosted Checkout session (currently blocked — see [Known limitation](#known-limitation-hosted-checkout-blocked) below). In simulation mode, the pay page swaps the real SDK for a stand-in panel (pick success/declined/abandoned) so the whole invoice → pay → complete loop is testable today — see [Simulation mode](#simulation-mode).
- **Wallets tab** — simulate-only Apple Pay / Google Pay charges via `FiservCommerceHub::chargeWithWallet()`. Real wallet tokens can't be produced from this app (Apple Pay JS only runs in Safari and needs a real public domain; Google Pay needs live client-side wiring this demo doesn't have), so the buttons always build a structurally-correct-but-fake token and only work with simulation mode on. Worth knowing: wallet charges use the same Terminal API credentials as a regular charge — confirmed live, no separate VAS entitlement needed here, unlike Hosted Checkout.
- **Transaction history** and **webhook log** viewers
- **Team login gate** — the staff-facing pages require sign-in (see [Access control](#access-control)); the customer-facing `/pay/{invoice}` links stay public
- **Dashboard stats + tabs** on the main page
- **Toast notifications** — every action (Charge, Capture, Void, Refund, Inquire, Wallets, payment links) surfaces a dismissable success/warning/declined toast in addition to the inline result card.

**Built at the package level but not wired into this demo's UI:** idempotent retries (`ChargeRequest::$idempotencyKey`) — implemented and tested in the package itself (see its README), just not exposed as demo UI yet since it doesn't have a natural form-based interaction to demo (it's a "resubmit the exact same request" guarantee, not a distinct operation).

See [`MONDAY-VAS-CHECKLIST.md`](MONDAY-VAS-CHECKLIST.md) for what to verify once Fiserv enables VAS/Hosted Checkout on this sandbox account.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Then fill in `.env` — see [Environment variables](#environment-variables) below — and serve it with Herd (or `php artisan serve`).

### Installing the Fiserv package

`composer.json` (the tracked, default file — used by `composer install` everywhere, including production/CI) declares `mlytorres/laravel-fiserv-commercehub` via **only** a `vcs` repository:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/mlytorres/laravel-fiserv-commercehub" }
]
```

This is deliberate. An earlier version of this setup also declared a local `path` repository (pointing at the sibling `../miamilife-crm/packages/laravel-fiserv-commercehub` checkout) so editing the package locally was immediately reflected here. That broke production with `Source path "../miamilife-crm/..." is not found` — Composer's path repository throws immediately if the path doesn't exist, *before* the solver ever gets to consider a fallback repository, so simply adding a `vcs` repository alongside it didn't help. The tracked `composer.json` now never references a path that might not exist, so a plain `composer install` works identically everywhere: it pulls the package straight from GitHub (branch `main`, matching the `dev-main` constraint).

**For live-editable local development** (this repo checked out next to `miamilife-crm`, e.g. your own Herd setup), use `composer.local.json` instead — a copy of `composer.json` with the path repository added back in, listed first:

```bash
COMPOSER=composer.local.json composer install
```

```powershell
# Windows PowerShell
$env:COMPOSER = 'composer.local.json'; composer install
```

This resolves the package from the live symlinked path instead — editing the package's source (e.g. in an IDE opened at the miamilife-crm repo) is reflected here immediately, no reinstall needed. It generates its own `composer.local.lock` (gitignored, machine-specific — see `.gitignore`), so it never conflicts with the tracked `composer.lock`. `composer.local.json` is tracked in git as a shared template — if you add/remove a dependency or script in `composer.json`, mirror the change there too (only the `repositories` section is meant to differ).

**Keeping GitHub in sync:** production and CI only ever see whatever's pushed to `github.com/mlytorres/laravel-fiserv-commercehub` (branch `main`). Local path-repo development doesn't require pushing anything — but a change to the package won't reach production until it's pushed there.

## Environment variables

```env
# Fiserv Commerce Hub — Terminal API (charges, refunds, cancels, inquiry)
FISERV_MODE=sandbox
FISERV_API_KEY=
FISERV_API_SECRET=
FISERV_MERCHANT_ID=
FISERV_TERMINAL_ID=10000001

# Hosted Checkout (payment links) — requires separate "VAS" product access, see below
FISERV_HOSTED_PAGE_ID=
FISERV_HOSTED_PAGE_VERSION=1
FISERV_HOSTED_SDK_VERSION=v1
FISERV_HOSTED_SDK_URL=
FISERV_HOSTED_ENV=CERT

# Demo access gate — one shared team login, not a real user system
DEMO_AUTH_USERNAME=
DEMO_AUTH_PASSWORD=
```

Get `FISERV_API_KEY` / `FISERV_API_SECRET` / `FISERV_MERCHANT_ID` by registering at [developer.fiserv.com](https://developer.fiserv.com) (product: **CommerceHub**), which issues a pre-generated Test Sandbox Key — or through your Fiserv/Bank of America merchant rep. Full config reference lives in the package's `config/fiserv.php`.

## Access control

The staff-facing pages (charge/void/refund harness, invoices admin, transaction history, webhook logs) require signing in with the single shared team login from `DEMO_AUTH_USERNAME` / `DEMO_AUTH_PASSWORD`. This is **not** a real user system — one shared password, no registration, no per-person accounts — just enough to keep a sandbox with a real (if fake-money) payment integration off the open internet. Change the password any time by editing `.env`; it takes effect on the next login attempt, no migration needed.

The customer-facing `/pay/{invoice}` payment link pages are deliberately **outside** this gate (see `routes/web.php` — they're the one route group not wrapped in the `demo.auth` middleware), so a link you share with an actual test "customer" doesn't require your team's internal login.

See `app/Http/Middleware/EnsureDemoAuthenticated.php` and `app/Http/Controllers/DemoAuthController.php` for the implementation.

## Simulation mode

Set `FISERV_SIMULATE_SUCCESS=true` to get instant mocked-approved responses without hitting the network at all — useful if the sandbox is down or you just want to click through the UI. The package's `CommerceHubService` checks this flag at the top of every method and short-circuits before building a request. Leave it `false` (the default) to actually exercise the sandbox, which is what this demo is for.

Two parts of the UI change behavior specifically because of this flag, not just the response speed:

- **Invoices → pay page**: normally loads Commerce Hub's real Hosted Checkout SDK. In simulation mode it can't (a real SDK against a fake session would just fail), so it shows a stand-in panel instead — pick success/declined/abandoned and it jumps straight to `complete()` with the same query params Commerce Hub's real redirect would send.
- **Wallets tab**: the Apple Pay / Google Pay buttons are only enabled in simulation mode, for the same reason — a real wallet token can't be produced here, and sending a fake one to the real sandbox would just fail signature validation rather than prove anything.

## Known limitation: Hosted Checkout blocked

The Invoices → payment link flow calls Commerce Hub's Security Credentials API (`/payments-vas/v1/security/credentials`), which is gated behind a separate "Value Added Services" (VAS) product entitlement from the core Terminal API this sandbox key already has. Right now that returns `401 ApiKey and/or Authentication supplied are invalid` — confirmed live, not a bug in this app or the package. Fiserv needs to enable VAS for this sandbox account before payment links (and Tokenization / Risk Assessment / 3-D Secure) will work. Charges, pre-auth/capture, void, and refund are all on the Terminal API and unaffected.

## Routes

| Route | Auth | Purpose |
|---|---|---|
| `GET /login`, `POST /login`, `POST /logout` | — | Team sign-in |
| `GET /` | gated | Charge / Pre-Auth / Capture / Void / Refund / Inquire / Wallets harness |
| `POST /wallet` | gated | Simulate-only Apple Pay / Google Pay charge |
| `GET /invoices`, `POST /invoices` | gated | Create payment-link invoices |
| `GET /transactions` | gated | Every attempt made through this app |
| `GET /webhook-logs` | gated | Every webhook Commerce Hub has sent this app |
| `GET /pay/{invoice}`, `GET /pay/{invoice}/complete` | **public** | The actual customer-facing payment link |

## Project structure

```
app/Http/Controllers/
  FiservDemoController.php        Charge/Pre-Auth/Capture/Void/Refund/Inquire + dashboard stats
  FiservPaymentLinkController.php Invoices + Hosted Checkout payment links
  DemoAuthController.php          Team login gate
app/Http/Middleware/
  EnsureDemoAuthenticated.php     Gates staff routes behind the session flag DemoAuthController sets
app/Models/
  DemoTransaction.php             Every attempt made through this app (successes and failures)
  DemoInvoice.php                 Payment-link invoices
resources/views/fiserv/
  layout.blade.php                Shared chrome: nav, branding, badges, tabs/stat-tile CSS, JS helpers
  demo.blade.php                  Main harness (tabs + dashboard strip)
  invoices.blade.php, transactions.blade.php, webhook-logs.blade.php
  login.blade.php                 Team sign-in
  pay.blade.php, pay-result.blade.php   Customer-facing, public, no staff nav
```

## Testing

This app doesn't carry its own test suite beyond the Laravel default — the payment logic itself is tested in the package (`composer test` from `packages/laravel-fiserv-commercehub`). Verification of this app's own behavior (auth gate, dashboard stats, tabs) has been done by hand against a running instance rather than automated feature tests.
