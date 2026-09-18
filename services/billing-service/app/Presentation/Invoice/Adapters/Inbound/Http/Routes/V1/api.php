<?php

use App\Presentation\Invoice\Adapters\Inbound\Http\Controllers\InvoiceController;
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
