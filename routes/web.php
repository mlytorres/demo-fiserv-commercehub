<?php

use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\FiservDemoController;
use App\Http\Controllers\FiservPaymentLinkController;
use Illuminate\Support\Facades\Route;

// Demo access gate — one shared team login, see config/demo_auth.php.
Route::get('/login', [DemoAuthController::class, 'showLogin'])->name('fiserv.demo.login');
Route::post('/login', [DemoAuthController::class, 'login'])->name('fiserv.demo.login.submit');
Route::post('/logout', [DemoAuthController::class, 'logout'])->name('fiserv.demo.logout');

Route::middleware('demo.auth')->group(function (): void {
    Route::get('/', [FiservDemoController::class, 'index'])->name('fiserv.demo.index');
    Route::post('/charge', [FiservDemoController::class, 'charge'])->name('fiserv.demo.charge');
    Route::post('/pre-auth', [FiservDemoController::class, 'preAuth'])->name('fiserv.demo.preauth');
    Route::post('/capture', [FiservDemoController::class, 'capture'])->name('fiserv.demo.capture');
    Route::post('/void', [FiservDemoController::class, 'void'])->name('fiserv.demo.void');
    Route::post('/refund', [FiservDemoController::class, 'refund'])->name('fiserv.demo.refund');
    Route::post('/inquire', [FiservDemoController::class, 'inquire'])->name('fiserv.demo.inquire');
    Route::post('/wallet', [FiservDemoController::class, 'wallet'])->name('fiserv.demo.wallet');
    Route::get('/webhook-logs', [FiservDemoController::class, 'webhookLogs'])->name('fiserv.demo.webhook-logs');
    Route::get('/transactions', [FiservDemoController::class, 'transactions'])->name('fiserv.demo.transactions');

    // Invoice + payment-link demo (Hosted Checkout). "Pay by Link" isn't a native
    // Commerce Hub product yet, so /invoices owns the long-lived link and /pay/{token}
    // mints a fresh Commerce Hub session on each visit.
    Route::get('/invoices', [FiservPaymentLinkController::class, 'index'])->name('fiserv.demo.invoices');
    Route::post('/invoices', [FiservPaymentLinkController::class, 'store'])->name('fiserv.demo.invoices.store');
});

// Customer-facing payment link pages stay public and OUTSIDE the demo.auth
// gate on purpose — this is what an actual patient/customer sees when they
// open an invoice's pay link, so it can't require the team's internal login.
Route::get('/pay/{invoice:public_token}', [FiservPaymentLinkController::class, 'pay'])->name('fiserv.demo.pay');
Route::get('/pay/{invoice:public_token}/complete', [FiservPaymentLinkController::class, 'complete'])->name('fiserv.demo.pay.complete');
