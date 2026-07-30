<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DemoTransaction;
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
                'order_id' => $chargeRequest->orderId,
                'amount' => $validated['amount'],
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return $this->rememberFailure('charge', $exception);
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
                'order_id' => data_get($result->rawResponse, 'gatewayResponse.transactionProcessingDetails.orderId'),
                'amount' => $validated['amount'],
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return $this->rememberFailure('refund', $exception);
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
                'order_id' => data_get($result->rawResponse, 'gatewayResponse.transactionProcessingDetails.orderId'),
                'amount' => data_get($result->rawResponse, 'paymentReceipt.approvedAmount.total'),
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return $this->rememberFailure('inquire', $exception);
        }
    }

    /**
     * Flash the result to the session, remember the transaction id so the
     * Refund/Status inquiry forms can auto-fill it on the next page load,
     * and persist it to the transaction history table.
     *
     * @param  array<string, mixed>  $result
     */
    protected function rememberResult(array $result): RedirectResponse
    {
        DemoTransaction::create([
            'action' => $result['action'],
            'transaction_id' => $result['transaction_id'] ?? null,
            'order_id' => $result['order_id'] ?? null,
            'amount' => $result['amount'] ?? null,
            'approved' => $result['approved'] ?? false,
            'status_label' => $result['status_label'] ?? null,
            'failure_reason' => $result['failure_reason'] ?? null,
            'raw' => $result['raw'] ?? [],
        ]);

        $redirect = redirect()->route('fiserv.demo.index')->with('result', $result);

        if (! empty($result['transaction_id'])) {
            $redirect->with('last_transaction_id', $result['transaction_id']);
        }

        return $redirect;
    }

    /**
     * Record an exception (connection failure, config error, etc.) in the
     * transaction history too, so the log reflects every attempt — not just
     * the ones that got far enough to reach Commerce Hub.
     */
    protected function rememberFailure(string $action, Throwable $exception): RedirectResponse
    {
        DemoTransaction::create([
            'action' => $action,
            'approved' => false,
            'status_label' => 'Error',
            'failure_reason' => $exception->getMessage(),
        ]);

        return redirect()->route('fiserv.demo.index')->with('error', $exception->getMessage());
    }

    public function webhookLogs(): View
    {
        return view('fiserv.webhook-logs', [
            'logs' => WebhookLog::latest()->paginate(20),
        ]);
    }

    public function transactions(): View
    {
        return view('fiserv.transactions', [
            'transactions' => DemoTransaction::latest()->paginate(20),
        ]);
    }
}
