<?php

use App\Presentation\Http\V1\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/api-key', [ApiKeyController::class, 'exchange'])->middleware('throttle:20,1');

Route::prefix('/merchants/{merchant}/api-keys')
    ->middleware(['access-token', 'merchant-tenant', 'role:owner,admin'])
    ->group(function (): void {
        Route::get('/', [ApiKeyController::class, 'index']);
        Route::post('/', [ApiKeyController::class, 'store']);
        Route::delete('/{apiKey}', [ApiKeyController::class, 'destroy']);
    });
