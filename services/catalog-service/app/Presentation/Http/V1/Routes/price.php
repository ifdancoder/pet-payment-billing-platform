<?php

use App\Presentation\Http\V1\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}')
    ->group(function () {
        Route::post('/products/{product}/prices', [PriceController::class, 'store'])->middleware('role:owner,admin,developer');

        Route::prefix('/prices/{price}')
            ->group(function () {
                Route::get('', [PriceController::class, 'show'])->middleware('role:owner,admin,developer,finance,viewer');
                Route::post('/activate', [PriceController::class, 'activate'])->middleware('role:owner,admin,developer');
                Route::post('/deactivate', [PriceController::class, 'deactivate'])->middleware('role:owner,admin,developer');
            });
    });
