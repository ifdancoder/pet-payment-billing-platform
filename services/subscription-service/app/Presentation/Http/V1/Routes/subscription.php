<?php

use App\Presentation\Http\V1\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}/subscriptions')
    ->group(function () {
        Route::get('', [SubscriptionController::class, 'index'])->middleware('role:owner,admin,developer,finance,viewer');
        Route::post('', [SubscriptionController::class, 'store'])->middleware('role:owner,admin,developer');

        Route::prefix('/{subscription}')
            ->group(function () {
                Route::get('', [SubscriptionController::class, 'show'])->middleware('role:owner,admin,developer,finance,viewer');
                Route::post('/activate', [SubscriptionController::class, 'activate'])->middleware('role:owner,admin');
                Route::post('/mark-past-due', [SubscriptionController::class, 'markPastDue'])->middleware('role:owner,admin');
                Route::post('/cancel', [SubscriptionController::class, 'cancel'])->middleware('role:owner,admin,developer');
            });
    });
