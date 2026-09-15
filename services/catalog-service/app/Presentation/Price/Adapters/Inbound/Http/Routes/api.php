<?php

use App\Presentation\Price\Adapters\Inbound\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::post('/products/{product}/prices', [PriceController::class, 'store']);
Route::get('/prices/{price}', [PriceController::class, 'show']);
Route::post('/prices/{price}/activate', [PriceController::class, 'activate']);
Route::post('/prices/{price}/deactivate', [PriceController::class, 'deactivate']);
