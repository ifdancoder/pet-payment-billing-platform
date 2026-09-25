<?php

use App\Presentation\Http\V1\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}/notifications')
    ->group(function () {
        Route::get('', [NotificationController::class, 'index'])->middleware('role:owner,admin,developer,finance,viewer');

        Route::prefix('/{notification}')
            ->group(function () {
                Route::get('', [NotificationController::class, 'show'])->middleware('role:owner,admin,developer,finance,viewer');
            });
    });
