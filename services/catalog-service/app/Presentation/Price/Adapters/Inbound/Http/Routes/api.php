<?php

use App\Presentation\Price\Adapters\Inbound\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::post('/products/{product}/prices', [PriceController::class, 'store']);
Route::get('/prices/{price}', [PriceController::class, 'show']);
