<?php

use App\Presentation\Http\V1\Controllers\MembershipController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}/memberships')
    ->middleware(['access-token', 'merchant-tenant'])
    ->group(function (): void {
        Route::get('/', [MembershipController::class, 'index'])->middleware('role:owner,admin');
        Route::post('/', [MembershipController::class, 'store'])->middleware('role:owner,admin');
        Route::patch('/{membership}', [MembershipController::class, 'update'])->middleware('role:owner,admin');
        Route::delete('/{membership}', [MembershipController::class, 'destroy'])->middleware('role:owner,admin');
    });
