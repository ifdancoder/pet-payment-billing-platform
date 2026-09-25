<?php

use App\Presentation\Http\V1\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->middleware('throttle:20,1')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
