<?php

use App\Presentation\Http\V1\Controllers\MerchantController;
use Illuminate\Support\Facades\Route;

Route::post('/merchants', [MerchantController::class, 'store'])
    ->middleware(['access-token', 'role:owner']);
