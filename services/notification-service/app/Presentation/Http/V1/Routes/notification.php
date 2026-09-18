<?php

use App\Presentation\Http\V1\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}/notifications')
    ->group(function () {
        Route::get('', [NotificationController::class, 'index']);

        Route::prefix('/{notification}')
            ->group(function () {
                Route::get('', [NotificationController::class, 'show']);
            });
    });
