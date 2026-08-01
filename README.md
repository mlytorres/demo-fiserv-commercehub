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
- **Invoices / payment links** — generates a shareable link per invoice; opening it mints a fresh Commerce Hub Hosted Checkout session (currently blocked — see [Known limitation](#known-limitation-hosted-checkout-blocked) below)
- **Transaction history** and **webhook log** viewers
- **Team login gate** — the staff-facing pages require sign-in (see [Access control](#access-control)); the customer-facing `/pay/{invoice}` links stay public
- **Dashboard stats + tabs** on the main page

**Built at the package level but not wired into this demo's UI:** Apple Pay / Google Pay wallet charges (`FiservCommerceHub::chargeWithWallet()`) and idempotent retries (`ChargeRequest::$idempotencyKey`). Both are implemented and tested in the package itself — see its README — just not exposed as demo UI yet, since neither has a natural form-based interaction to demo.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Then fill in `.env` — see [Environment variables](#environment-variables) below — and serve it with Herd (or `php artisan serve`).

### Installing the Fiserv package: two repositories, on purpose

`composer.json` declares `mlytorres/laravel-fiserv-commercehub` via **two** repositories:

```json
"repositories": [
    { "type": "path", "url": "../miamilife-crm/packages/laravel-fiserv-commercehub", "options": { "symlink": true } },
    { "type": "vcs", "url": "https://github.com/mlytorres/laravel-fiserv-commercehub" }
]
```

- **Local dev** (this repo checked out next to `miamilife-crm`, e.g. on your own Herd setup): resolves via the **path** repo, symlinked — editing the package's source directly (e.g. in an IDE opened at the miamilife-crm repo) is reflected here immediately, no reinstall needed.
- **Any environment without that sibling checkout** — production, CI, a teammate's machine, another server: the path repo finds nothing there, so Composer falls through to the **vcs** repo and pulls `mlytorres/laravel-fiserv-commercehub` straight from GitHub instead.

**`composer.lock` is deliberately not committed** (see `.gitignore`) specifically because of this. A lock file pins a dependency to *one* resolved source; if we committed a lock generated locally (path-resolved) and shipped it to production, `composer install` there would try to reproduce that exact path source, find it missing, and hard-fail with `Source path "../miamilife-crm/..." is not found` — `composer install` trusts the lock file completely and does not fall back to another declared repository the way a fresh resolve (`composer update`) does. Every environment running `composer install` for the first time (no local lock yet) resolves fresh and correctly picks whichever source actually exists there.

If you ever do want a committed lock for full reproducibility of every *other* dependency, generate it specifically in an environment without the sibling `miamilife-crm` checkout (so it locks this one package to the vcs source, not the path) — then local dev will need to run `composer update mlytorres/laravel-fiserv-commercehub` once to flip it back to the live path for editing.

**Keeping GitHub in sync:** the vcs fallback pulls whatever's pushed to `github.com/mlytorres/laravel-fiserv-commercehub` (branch `main`, since `composer.json` requires `dev-main`). Local path-repo development doesn't require pushing anything — but production/CI installs won't see package changes until they're pushed to that repo.

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

## Known limitation: Hosted Checkout blocked

The Invoices → payment link flow calls Commerce Hub's Security Credentials API (`/payments-vas/v1/security/credentials`), which is gated behind a separate "Value Added Services" (VAS) product entitlement from the core Terminal API this sandbox key already has. Right now that returns `401 ApiKey and/or Authentication supplied are invalid` — confirmed live, not a bug in this app or the package. Fiserv needs to enable VAS for this sandbox account before payment links (and Tokenization / Risk Assessment / 3-D Secure) will work. Charges, pre-auth/capture, void, and refund are all on the Terminal API and unaffected.

## Routes

| Route | Auth | Purpose |
|---|---|---|
| `GET /login`, `POST /login`, `POST /logout` | — | Team sign-in |
| `GET /` | gated | Charge / Pre-Auth / Capture / Void / Refund / Inquire harness |
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
