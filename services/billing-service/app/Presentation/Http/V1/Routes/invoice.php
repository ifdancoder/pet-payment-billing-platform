<?php

use App\Presentation\Http\V1\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['access-token', 'merchant-tenant'])
    ->prefix('/merchants/{merchant}/invoices')
    ->group(function () {
        Route::get('', [InvoiceController::class, 'index'])->middleware('role:owner,admin,finance,viewer');

        Route::prefix('/{invoice}')
            ->group(function () {
                Route::get('', [InvoiceController::class, 'show'])->middleware('role:owner,admin,finance,viewer');
                Route::post('/void', [InvoiceController::class, 'void'])->middleware('role:owner,admin,finance');
            });
    });
