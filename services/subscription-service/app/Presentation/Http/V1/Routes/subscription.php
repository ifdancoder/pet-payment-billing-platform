<?php

use App\Presentation\Http\V1\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}/subscriptions')
    ->group(function () {
        Route::get('', [SubscriptionController::class, 'index']);
        Route::post('', [SubscriptionController::class, 'store']);

        Route::prefix('/{subscription}')
            ->group(function () {
                Route::get('', [SubscriptionController::class, 'show']);
                Route::post('/activate', [SubscriptionController::class, 'activate']);
                Route::post('/mark-past-due', [SubscriptionController::class, 'markPastDue']);
                Route::post('/cancel', [SubscriptionController::class, 'cancel']);
            });
    });
