<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DemoInvoice;
use App\Models\DemoTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mlytorres\FiservCommerceHub\Facades\FiservCommerceHub;
use Throwable;

/**
 * Demonstrates the "invoice with a payment link" pattern on top of Commerce
 * Hub's Hosted Checkout SDK. Commerce Hub has no native "Pay by Link" yet
 * (Fiserv's own docs list it as "Coming soon"), so this app owns the
 * long-lived link (an invoice's public_token); a fresh, short-lived Commerce
 * Hub session is minted every time the link is opened (see pay()).
 */
class FiservPaymentLinkController extends Controller
{
    public function index(): View
    {
        return view('fiserv.invoices', [
            'invoices' => DemoInvoice::latest()->paginate(20),
            'configured' => FiservCommerceHub::isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $invoice = DemoInvoice::create($validated);

        return redirect()
            ->route('fiserv.demo.invoices')
            ->with('created_link', route('fiserv.demo.pay', $invoice));
    }

    /**
     * The link page. Mints a fresh Hosted Checkout session every time it's
     * opened — the session expires in 30 minutes, so it can never be baked
     * into the link itself the way the link's own URL is.
     */
    public function pay(DemoInvoice $invoice): View
    {
        if ($invoice->isPaid()) {
            return view('fiserv.pay-result', [
                'invoice' => $invoice,
                'success' => true,
                'alreadyPaid' => true,
            ]);
        }

        try {
            $session = FiservCommerceHub::createHostedCheckoutSession(
                amount: (float) $invoice->amount,
                orderId: $invoice->public_token,
            );
        } catch (Throwable $exception) {
            return view('fiserv.pay-result', [
                'invoice' => $invoice,
                'success' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        return view('fiserv.pay', [
            'invoice' => $invoice,
            'credentials' => $session->toSdkCredentials(
                apiKey: (string) config('fiserv.credentials.api_key'),
                merchantId: (string) config('fiserv.credentials.merchant_id'),
                terminalId: (string) config('fiserv.credentials.terminal_id'),
                pageId: (string) config('fiserv.hosted_checkout.page_id'),
                pageVersion: (string) config('fiserv.hosted_checkout.page_version'),
                environment: (string) config('fiserv.hosted_checkout.environment'),
            ),
            'sdkUrl' => config('fiserv.hosted_checkout.sdk_url')
                ?: 'https://commercehub-checkout.fiservapps.com/sdk/'.config('fiserv.hosted_checkout.sdk_version').'/checkout.js',
        ]);
    }

    /**
     * Commerce Hub redirects here after the customer completes (or abandons)
     * the Hosted Checkout form, with `cardCaptureResult` and the session's
     * `sessionId` passed back as query parameters.
     */
    public function complete(Request $request, DemoInvoice $invoice): View
    {
        $captureResult = $request->query('cardCaptureResult');
        $sessionId = $request->query('sessionId');

        if ($captureResult !== 'SUCCESS' || ! $sessionId) {
            DemoTransaction::create([
                'action' => 'payment_link',
                'order_id' => $invoice->public_token,
                'amount' => $invoice->amount,
                'approved' => false,
                'status_label' => 'Failed',
                'failure_reason' => $captureResult === 'FAILED'
                    ? 'Customer capture failed at Hosted Checkout.'
                    : 'No successful capture result returned.',
            ]);

            return view('fiserv.pay-result', [
                'invoice' => $invoice,
                'success' => false,
                'error' => 'Payment was not completed.',
            ]);
        }

        try {
            $result = FiservCommerceHub::finalizeHostedCheckoutCharge(
                sessionId: $sessionId,
                orderId: $invoice->public_token,
            );
        } catch (Throwable $exception) {
            DemoTransaction::create([
                'action' => 'payment_link',
                'order_id' => $invoice->public_token,
                'amount' => $invoice->amount,
                'approved' => false,
                'status_label' => 'Error',
                'failure_reason' => $exception->getMessage(),
            ]);

            return view('fiserv.pay-result', [
                'invoice' => $invoice,
                'success' => false,
                'error' => $exception->getMessage(),
            ]);
        }

        DemoTransaction::create([
            'action' => 'payment_link',
            'transaction_id' => $result->transactionId,
            'order_id' => $invoice->public_token,
            'amount' => $invoice->amount,
            'approved' => $result->isApproved(),
            'status_label' => $result->status->label(),
            'failure_reason' => $result->failureReason,
            'raw' => $result->rawResponse,
        ]);

        if ($result->isApproved()) {
            $invoice->update([
                'status' => 'paid',
                'transaction_id' => $result->transactionId,
                'paid_at' => now(),
            ]);
        } else {
            $invoice->update(['status' => 'failed']);
        }

        return view('fiserv.pay-result', [
            'invoice' => $invoice,
            'success' => $result->isApproved(),
            'error' => $result->failureReason,
            'transactionId' => $result->transactionId,
        ]);
    }
}
