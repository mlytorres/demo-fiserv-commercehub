<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Mlytorres\FiservCommerceHub\DTOs\ChargeRequest;
use Mlytorres\FiservCommerceHub\Facades\FiservCommerceHub;
use Mlytorres\FiservCommerceHub\Models\WebhookLog;


/**
 * Minimal hand-testing harness for the laravel-fiserv-commercehub package.
 * Not meant to be production code — just enough UI to charge/refund/inquire
 * against the real Commerce Hub sandbox and watch webhooks land.
 */
class FiservDemoController extends Controller
{
    public function index(): View
    {
        return view('fiserv.demo', [
            'configured' => FiservCommerceHub::isConfigured(),
            'result' => session('result'),
            'error' => session('error'),
            'lastTransactionId' => session('last_transaction_id'),
        ]);
    }

    public function charge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'card_number' => ['required', 'string'],
            'exp_month' => ['required', 'string', 'size:2'],
            'exp_year' => ['required', 'string', 'size:2'],
            'cvv' => ['required', 'string'],
        ]);

        try {
            $chargeRequest = new ChargeRequest(
                amount: (float) $validated['amount'],
                orderId: 'DEMO-'.now()->timestamp,
                cardNumber: $validated['card_number'],
                expMonth: $validated['exp_month'],
                expYear: $validated['exp_year'],
                securityCode: $validated['cvv'],
            );

            $result = FiservCommerceHub::charge($chargeRequest);

            return $this->rememberResult([
                'action' => 'charge',
                'approved' => $result->isApproved(),
                'status_label' => $result->status->label(),
                'transaction_id' => $result->transactionId,
                'amount' => $validated['amount'],
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return redirect()->route('fiserv.demo.index')->with('error', $exception->getMessage());
        }
    }

    public function refund(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $result = FiservCommerceHub::refund($validated['transaction_id'], (float) $validated['amount']);

            return $this->rememberResult([
                'action' => 'refund',
                'approved' => $result->isApproved(),
                'status_label' => $result->status->label(),
                'transaction_id' => $result->transactionId,
                'amount' => $validated['amount'],
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return redirect()->route('fiserv.demo.index')->with('error', $exception->getMessage());
        }
    }

    public function inquire(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
        ]);

        try {
            $result = FiservCommerceHub::inquire($validated['transaction_id']);

            return $this->rememberResult([
                'action' => 'inquire',
                'approved' => $result->isApproved(),
                'status_label' => $result->status->label(),
                'transaction_id' => $result->transactionId,
                'amount' => data_get($result->rawResponse, 'paymentReceipt.approvedAmount.total'),
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return redirect()->route('fiserv.demo.index')->with('error', $exception->getMessage());
        }
    }

    /**
     * Flash the result to the session and remember the transaction id so the
     * Refund/Status inquiry forms can auto-fill it on the next page load —
     * the natural next action after a charge is almost always to refund or
     * check the same transaction.
     *
     * @param  array<string, mixed>  $result
     */
    protected function rememberResult(array $result): RedirectResponse
    {
        $redirect = redirect()->route('fiserv.demo.index')->with('result', $result);

        if (! empty($result['transaction_id'])) {
            $redirect->with('last_transaction_id', $result['transaction_id']);
        }

        return $redirect;
    }

    public function webhookLogs(): View
    {
        return view('fiserv.webhook-logs', [
            'logs' => WebhookLog::latest()->paginate(20),
        ]);
    }
}
