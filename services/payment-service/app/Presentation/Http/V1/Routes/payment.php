<?php

use App\Presentation\Http\V1\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}/payments')
    ->group(function () {
        Route::get('', [PaymentController::class, 'index']);

        Route::prefix('/{payment}')
            ->group(function () {
                Route::get('', [PaymentController::class, 'show']);
            });
    });
