<?php

use App\Presentation\Http\V1\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Also not nested under /merchants/{merchant}/... — a User has no
// merchant of its own; Membership is what links a User to a Merchant.
Route::post('/users', [UserController::class, 'store']);
