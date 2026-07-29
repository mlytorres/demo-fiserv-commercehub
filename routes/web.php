<?php

use App\Http\Controllers\FiservDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FiservDemoController::class, 'index'])->name('fiserv.demo.index');
Route::post('/charge', [FiservDemoController::class, 'charge'])->name('fiserv.demo.charge');
Route::post('/refund', [FiservDemoController::class, 'refund'])->name('fiserv.demo.refund');
Route::post('/inquire', [FiservDemoController::class, 'inquire'])->name('fiserv.demo.inquire');
Route::get('/webhook-logs', [FiservDemoController::class, 'webhookLogs'])->name('fiserv.demo.webhook-logs');
