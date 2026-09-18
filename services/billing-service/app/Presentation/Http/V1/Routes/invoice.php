<?php

use App\Presentation\Http\V1\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('/merchants/{merchant}/invoices')
    ->group(function () {
        Route::get('', [InvoiceController::class, 'index']);

        Route::prefix('/{invoice}')
            ->group(function () {
                Route::get('', [InvoiceController::class, 'show']);
                Route::post('/void', [InvoiceController::class, 'void']);
            });
    });
