<?php

use App\Http\Controllers\FiservDemoController;
use App\Http\Controllers\FiservPaymentLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FiservDemoController::class, 'index'])->name('fiserv.demo.index');
Route::post('/charge', [FiservDemoController::class, 'charge'])->name('fiserv.demo.charge');
Route::post('/refund', [FiservDemoController::class, 'refund'])->name('fiserv.demo.refund');
Route::post('/inquire', [FiservDemoController::class, 'inquire'])->name('fiserv.demo.inquire');
Route::get('/webhook-logs', [FiservDemoController::class, 'webhookLogs'])->name('fiserv.demo.webhook-logs');
Route::get('/transactions', [FiservDemoController::class, 'transactions'])->name('fiserv.demo.transactions');

// Invoice + payment-link demo (Hosted Checkout). "Pay by Link" isn't a native
// Commerce Hub product yet, so /invoices owns the long-lived link and /pay/{token}
// mints a fresh Commerce Hub session on each visit.
Route::get('/invoices', [FiservPaymentLinkController::class, 'index'])->name('fiserv.demo.invoices');
Route::post('/invoices', [FiservPaymentLinkController::class, 'store'])->name('fiserv.demo.invoices.store');
Route::get('/pay/{invoice:public_token}', [FiservPaymentLinkController::class, 'pay'])->name('fiserv.demo.pay');
Route::get('/pay/{invoice:public_token}/complete', [FiservPaymentLinkController::class, 'complete'])->name('fiserv.demo.pay.complete');
