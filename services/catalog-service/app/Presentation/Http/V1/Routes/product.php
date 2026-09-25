<?php

use App\Presentation\Http\V1\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}/products')
    ->group(function () {
        Route::get('', [ProductController::class, 'index'])->middleware('role:owner,admin,developer,finance,viewer');
        Route::post('', [ProductController::class, 'store'])->middleware('role:owner,admin,developer');

        Route::prefix('/{product}')
            ->group(function () {
                Route::get('', [ProductController::class, 'show'])->middleware('role:owner,admin,developer,finance,viewer');
                Route::patch('', [ProductController::class, 'update'])->middleware('role:owner,admin,developer');
                Route::post('/archive', [ProductController::class, 'archive'])->middleware('role:owner,admin,developer');
            });
    });
