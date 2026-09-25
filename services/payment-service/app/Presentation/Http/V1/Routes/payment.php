<?php

use App\Presentation\Http\V1\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}/payments')
    ->group(function () {
        Route::get('', [PaymentController::class, 'index'])->middleware('role:owner,admin,finance,viewer');

        Route::prefix('/{payment}')
            ->group(function () {
                Route::get('', [PaymentController::class, 'show'])->middleware('role:owner,admin,finance,viewer');
            });
    });
