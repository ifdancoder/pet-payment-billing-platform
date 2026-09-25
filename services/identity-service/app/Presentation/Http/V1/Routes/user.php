<?php

use App\Presentation\Http\V1\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// A User is global, while Membership grants tenant access. Only tenant
// administrators may pre-create a user before adding a Membership.
Route::post('/users', [UserController::class, 'store'])
    ->middleware(['access-token', 'role:owner,admin']);
