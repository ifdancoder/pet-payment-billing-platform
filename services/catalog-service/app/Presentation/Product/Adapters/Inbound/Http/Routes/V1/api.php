<?php

use App\Presentation\Product\Adapters\Inbound\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}/products')
    ->group(function () {
        Route::get('', [ProductController::class, 'index']);
        Route::post('', [ProductController::class, 'store']);

        Route::prefix('/{product}')
            ->group(function () {
                Route::get('', [ProductController::class, 'show']);
                Route::patch('', [ProductController::class, 'update']);
                Route::post('/archive', [ProductController::class, 'archive']);
            });
    });
