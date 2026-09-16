<?php

use App\Presentation\Price\Adapters\Inbound\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::post('/merchants/{merchant}/products/{product}/prices', [PriceController::class, 'store']);
Route::get('/merchants/{merchant}/prices/{price}', [PriceController::class, 'show']);
Route::post('/merchants/{merchant}/prices/{price}/activate', [PriceController::class, 'activate']);
Route::post('/merchants/{merchant}/prices/{price}/deactivate', [PriceController::class, 'deactivate']);
