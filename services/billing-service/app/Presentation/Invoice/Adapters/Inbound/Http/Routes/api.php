<?php

use App\Presentation\Invoice\Adapters\Inbound\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/merchants/{merchant}/invoices', [InvoiceController::class, 'index']);
Route::get('/merchants/{merchant}/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::post('/merchants/{merchant}/invoices/{invoice}/void', [InvoiceController::class, 'void']);
