<?php

use App\Presentation\Http\V1\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}/customers')
    ->group(function () {
        Route::get('', [CustomerController::class, 'index'])->middleware('role:owner,admin,developer,finance,viewer');
        Route::post('', [CustomerController::class, 'store'])->middleware('role:owner,admin,developer');

        Route::prefix('/{customer}')
            ->group(function () {
                Route::get('', [CustomerController::class, 'show'])->middleware('role:owner,admin,developer,finance,viewer');
                Route::put('', [CustomerController::class, 'update'])->middleware('role:owner,admin,developer');
                Route::delete('', [CustomerController::class, 'destroy'])->middleware('role:owner,admin,developer');
            });
    });
