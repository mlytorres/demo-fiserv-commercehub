<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DemoInvoice;
use App\Models\DemoTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Mlytorres\FiservCommerceHub\DTOs\ChargeRequest;
use Mlytorres\FiservCommerceHub\Enums\ReversalReasonCode;
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
        $result = session('result');

        return view('fiserv.demo', [
            'configured' => FiservCommerceHub::isConfigured(),
            'result' => $result,
            'error' => session('error'),
            'lastTransactionId' => session('last_transaction_id'),
            'stats' => $this->dashboardStats(),
            'activeTab' => $this->tabForAction($result['action'] ?? null),
        ]);
    }

    /**
     * Quick counts for the dashboard strip at the top of the index page —
     * enough to see at a glance whether the harness has been exercised and
     * roughly how it's going, without a trip to the full transaction history.
     *
     * @return array<string, int|float|null>
     */
    protected function dashboardStats(): array
    {
        $total = DemoTransaction::count();
        $approved = DemoTransaction::where('approved', true)->count();
        $pending = DemoTransaction::whereIn('status_label', ['Authorized', 'Waiting'])->count();

        return [
            'transactions' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'declined' => max($total - $approved - $pending, 0),
            'approval_rate' => $total > 0 ? round($approved / $total * 100) : null,
            'invoices' => DemoInvoice::count(),
            'webhook_logs' => WebhookLog::count(),
        ];
    }

    /**
     * Which operations tab should be active on load — defaults to Charge, but
     * follows the just-completed action so landing back on the index page
     * after e.g. a Void shows the Void tab (and its result) without an extra click.
     */
    protected function tabForAction(?string $action): string
    {
        return match ($action) {
            'capture' => 'capture',
            'void' => 'void',
            'refund' => 'refund',
            'inquire' => 'inquire',
            default => 'charge', // covers charge, pre_auth, and no result yet
        };
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

    /**
     * Authorize a hold on a card without capturing it — same fields as
     * charge(), just routed to preAuth() instead so the demo can show the
     * hold-now/capture-later flow (e.g. a deposit hold).
     */
    public function preAuth(Request $request): RedirectResponse
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
                orderId: 'DEMO-PREAUTH-'.now()->timestamp,
                cardNumber: $validated['card_number'],
                expMonth: $validated['exp_month'],
                expYear: $validated['exp_year'],
                securityCode: $validated['cvv'],
            );

            $result = FiservCommerceHub::preAuth($chargeRequest);

            return $this->rememberResult([
                'action' => 'pre_auth',
                'approved' => $result->isApproved(),
                'status_label' => $result->status->label(),
                'transaction_id' => $result->transactionId,
                'order_id' => $chargeRequest->orderId,
                'amount' => $validated['amount'],
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return $this->rememberFailure('pre_auth', $exception);
        }
    }

    /**
     * Capture funds against an earlier Pre-Auth's transaction id.
     */
    public function capture(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $result = FiservCommerceHub::capture($validated['transaction_id'], (float) $validated['amount']);

            return $this->rememberResult([
                'action' => 'capture',
                'approved' => $result->isApproved(),
                'status_label' => $result->status->label(),
                'transaction_id' => $result->transactionId,
                'order_id' => data_get($result->rawResponse, 'gatewayResponse.transactionProcessingDetails.orderId'),
                'amount' => $validated['amount'],
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return $this->rememberFailure('capture', $exception);
        }
    }

    /**
     * Void (cancel) an authorization or unsettled sale — full or partial.
     * Not the same as refund(): Commerce Hub rejects a void against an
     * already-settled transaction, and rejects a refund against one that
     * hasn't settled yet.
     */
    public function void(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $reason = ReversalReasonCode::tryFrom((string) ($validated['reason'] ?? '')) ?? ReversalReasonCode::Void;

            $result = FiservCommerceHub::void(
                originalTransactionId: $validated['transaction_id'],
                reason: $reason,
                amount: isset($validated['amount']) ? (float) $validated['amount'] : null,
            );

            return $this->rememberResult([
                'action' => 'void',
                'approved' => $result->isApproved(),
                'status_label' => $result->status->label(),
                'transaction_id' => $result->transactionId,
                'amount' => $validated['amount'] ?? null,
                'failure_reason' => $result->failureReason,
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return $this->rememberFailure('void', $exception);
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
