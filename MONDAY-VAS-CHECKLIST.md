# Monday VAS test checklist

Once Fiserv enables Value Added Services (Tokenization / Hosted Checkout / Risk
Assessment / 3-D Secure) on this sandbox account, run through this list. Everything
below has already been exercised in [simulation mode](README.md#simulation-mode)
(`FISERV_SIMULATE_SUCCESS=true`) — the goal Monday is only to confirm real Commerce
Hub responses match what was simulated, not to discover whether the code paths work
at all.

## 0. Before you start

- [ ] Set `FISERV_SIMULATE_SUCCESS=false` in `.env` (should already be the default).
- [ ] Confirm `FISERV_API_KEY` / `FISERV_API_SECRET` / `FISERV_MERCHANT_ID` are the
      sandbox credentials VAS was enabled on — not a different key.
- [ ] Confirm `FISERV_HOSTED_PAGE_ID` / `FISERV_HOSTED_PAGE_VERSION` are set — these
      come from the Checkout Configurator tool in the Commerce Hub Workspace, not
      issued automatically. If VAS was *just* turned on, this step may still be
      pending on Fiserv's side.

## 0.5. Open questions for the Fiserv/BofA rep (found 2026-08-01)

Signed into Developer Studio and checked the **MiamiLife CRM - Sandbox** workspace
directly. Three things didn't work and aren't something to debug on our end first —
raise these with Fiserv/BofA before assuming they're app bugs:

- [ ] **Notification Hub (webhook config) is stuck.** It requires picking a "Client
      ID" from a dropdown before showing any notification preferences, but the
      dropdown is empty — even though Credentials clearly shows a Client ID of
      `ccci` on both the MID (`100008000003683`) and the API key
      (`comhub-cert-ccci-miamilife_crm_sandbox_key`). "Select MID(s) to bulk edit"
      hits the same "Please select a Client ID first" wall. Ask: why is the Client
      ID dropdown in Notification Hub empty despite `ccci` existing on our
      credentials, and where do we actually register the webhook URL?
- [ ] **Hosted Pages tab never finishes loading** (infinite spinner, no error) —
      consistent with VAS not being enabled yet. Re-check after VAS lands Monday;
      if still stuck, that's the same rep conversation.
- [ ] **No merchant back-office / transaction management portal found.**
      Developer Studio (Workspace) is credentials + developer tooling only — no
      way to view live transactions, issue a refund by hand, or reconcile
      settlement from a UI. Ask: what's the actual merchant-facing portal for
      that, separate from Developer Studio, for a Bank of America Merchant
      Services-settled Commerce Hub account?

## 1. Mint a real Hosted Checkout session

- [ ] Go to **Invoices / payment links** → create a test invoice → open its link.
- [ ] Confirm the page loads the **real** Commerce Hub SDK panel (not the simulation
      stand-in panel — that only appears when `FISERV_SIMULATE_SUCCESS=true`).
- [ ] If it fails immediately with `ApiKey and/or Authentication supplied are
      invalid`, VAS isn't actually active yet for this key — that's the same 401
      that's been blocking this all along; don't debug the app first, confirm with
      Fiserv/Developer Studio.

## 2. Complete a real card entry

- [ ] Enter a Fiserv-documented sandbox test card (see the presets in the Charge tab
      for known-good numbers) directly into Commerce Hub's hosted form.
- [ ] Confirm redirect back to `/pay/{invoice}/complete` with `cardCaptureResult=SUCCESS`
      and a real `sessionId`.
- [ ] Confirm the invoice flips to **Paid** with a real transaction id, and the
      transaction shows up on **Transaction history** with `action = payment_link`.
- [ ] Confirm the success toast fires with the real transaction id (this part is
      already proven working — just re-confirming under real VAS data).

## 3. Trigger a decline through the hosted form

- [ ] Repeat with a card/flow Fiserv's docs describe as producing a decline inside
      the hosted page (not a client-side abandon — an actual processor decline).
- [ ] Confirm `cardCaptureResult` reflects the failure, the invoice stays **Pending**
      (matches today's design — a declined attempt shouldn't burn the link), and a
      `Failed` transaction is recorded with a real `failure_reason`.

## 4. 3-D Secure

- [ ] Confirm whether this sandbox tier actually triggers a 3DS challenge (Fiserv's
      docs suggest it depends on the test card / risk parameters used — this has
      never been exercised, so don't assume it will happen automatically).
- [ ] If a challenge appears: confirm it renders inside Commerce Hub's hosted flow
      (not something this app needs to build), and confirm the liability-shift
      result comes back correctly on `complete()`.
- [ ] If no challenge appears with the standard test cards: note that in the repo's
      docs and ask Fiserv/BofA rep what triggers it in this environment, rather than
      concluding 3DS "works" from an untested path.

## 5. Webhook delivery — needs a reachable URL

Commerce Hub's servers must be able to reach this app's `POST /fiserv/webhook` route
over the public internet. A local `*.test` Herd domain is **not** reachable from
Fiserv — this step will silently produce nothing (no error, just no webhook log
entry) unless the app is exposed via a tunnel (e.g. `ngrok http` pointed at the
Herd site) or actually deployed somewhere public first.

- [ ] Expose the app publicly (tunnel or deployment) before expecting any webhook.
- [ ] Confirm the webhook subscription in Commerce Hub's Workspace points at the
      exposed URL.
- [ ] Trigger a charge, then check **Webhook logs** for a row with a verified HMAC
      signature.
- [ ] Cross-reference the payload shape against
      `packages/laravel-fiserv-commercehub`'s `WebhookController` — the package's
      docs flag that the inbound webhook header names (`Client-Request-Id` /
      `Timestamp` / `Authorization`) were mirrored from the outbound signing scheme
      but never independently confirmed against a real webhook delivery. This is
      the first real chance to confirm that.

## 6. If all of the above pass

- [ ] Update `demo-fiserv-commercehub/README.md`'s "Known limitation: Hosted
      Checkout blocked" section — either remove it or replace with what was
      actually confirmed.
- [ ] Update `miamilife-crm/docs/modules/fiserv-commerce-hub.md` to note Hosted
      Checkout / VAS is confirmed working, since the CRM-side adapter
      (`FiservCommerceHubProvider`) doesn't call `createHostedCheckoutSession()` /
      `finalizeHostedCheckoutCharge()` yet — only Terminal API `charge()` /
      `refund()` / `inquire()`. Wiring Hosted Checkout into the CRM (for a
      card-on-file / no-PCI-scope patient portal flow) is a separate follow-up,
      not something this checklist covers.
- [ ] Run `composer update mlytorres/laravel-fiserv-commercehub` in the CRM first if
      picking up that follow-up — its vendored copy is currently pinned to an older
      commit missing preAuth/capture/void/wallet/idempotency (see this repo's
      package README and the CRM's `docs/modules/fiserv-commerce-hub.md` changelog).
