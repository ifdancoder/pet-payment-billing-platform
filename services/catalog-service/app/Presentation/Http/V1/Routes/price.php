<?php

use App\Presentation\Http\V1\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}')
    ->group(function () {
        Route::post('/products/{product}/prices', [PriceController::class, 'store']);

        Route::prefix('/prices/{price}')
            ->group(function () {
                Route::get('', [PriceController::class, 'show']);
                Route::post('/activate', [PriceController::class, 'activate']);
                Route::post('/deactivate', [PriceController::class, 'deactivate']);
            });
    });
