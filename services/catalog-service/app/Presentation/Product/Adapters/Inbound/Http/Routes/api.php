<?php

use App\Presentation\Product\Adapters\Inbound\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::post('/products/{product}/archive', [ProductController::class, 'archive']);
