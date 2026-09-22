<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\ShurjopayController;
use Illuminate\Support\Facades\Route;

/*
 | shurjoPay callbacks.
 |
 | None of these trust their query string. Each one takes the gateway's order id and asks
 | shurjoPay directly what happened; the answer is what settles the payable.
 */

Route::prefix('payments/shurjopay')->name('payments.shurjopay.')->group(function (): void {
    Route::get('/return', [ShurjopayController::class, 'return'])->name('return');
    Route::get('/cancel', [ShurjopayController::class, 'cancel'])->name('cancel');

    // shurjoPay posts here when the customer's browser never made it back. Excluded from
    // CSRF (it is a server-to-server call) and rate limited.
    Route::match(['get', 'post'], '/ipn', [ShurjopayController::class, 'ipn'])
        ->middleware('throttle:60,1')
        ->name('ipn');

    Route::post('/start/{type}/{id}', [ShurjopayController::class, 'start'])
        ->middleware(['auth', 'throttle:10,1'])
        ->where('type', 'order|deposit|invoice')
        ->name('start');
});
