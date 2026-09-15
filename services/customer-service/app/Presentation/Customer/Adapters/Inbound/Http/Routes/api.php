<?php

use App\Presentation\Customer\Adapters\Inbound\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('/customers')
    ->group(function () {
        Route::get('', [CustomerController::class, 'index']);
        Route::post('', [CustomerController::class, 'store']);

        Route::prefix('/{id}')
            ->group(function () {
                Route::get('', [CustomerController::class, 'show']);
                Route::put('', [CustomerController::class, 'update']);
                Route::delete('', [CustomerController::class, 'destroy']);
            });
    });

