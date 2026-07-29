<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Yllerandi\FiservCommerceHub\DTOs\ChargeRequest;
use Yllerandi\FiservCommerceHub\Facades\FiservCommerceHub;
use Yllerandi\FiservCommerceHub\Models\WebhookLog;

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

            return redirect()->route('fiserv.demo.index')->with('result', [
                'action' => 'charge',
                'approved' => $result->isApproved(),
                'transaction_id' => $result->transactionId,
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

            return redirect()->route('fiserv.demo.index')->with('result', [
                'action' => 'refund',
                'approved' => $result->isApproved(),
                'transaction_id' => $result->transactionId,
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

            return redirect()->route('fiserv.demo.index')->with('result', [
                'action' => 'inquire',
                'status' => $result->status->label(),
                'raw' => $result->rawResponse,
            ]);
        } catch (Throwable $exception) {
            return redirect()->route('fiserv.demo.index')->with('error', $exception->getMessage());
        }
    }

    public function webhookLogs(): View
    {
        return view('fiserv.webhook-logs', [
            'logs' => WebhookLog::latest()->paginate(20),
        ]);
    }
}
