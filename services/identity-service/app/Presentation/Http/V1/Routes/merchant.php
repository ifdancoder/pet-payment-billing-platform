<?php

use App\Presentation\Http\V1\Controllers\MerchantController;
use Illuminate\Support\Facades\Route;

// Deliberately not nested under /merchants/{merchant}/... like every other
// module's routes: creating a Merchant is the one action in the platform
// with no existing tenant context to scope it by.
Route::post('/merchants', [MerchantController::class, 'store']);
