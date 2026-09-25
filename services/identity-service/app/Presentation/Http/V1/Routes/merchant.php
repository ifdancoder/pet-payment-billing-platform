<?php

use App\Presentation\Http\V1\Controllers\MerchantController;
use Illuminate\Support\Facades\Route;

// Account bootstrap happens through /auth/register. Creating an additional
// merchant is an authenticated owner-only administrative operation.
Route::post('/merchants', [MerchantController::class, 'store'])
    ->middleware(['access-token', 'role:owner']);
