<?php

use App\Presentation\Http\V1\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/users', [UserController::class, 'store'])
    ->middleware(['access-token', 'role:owner,admin']);
