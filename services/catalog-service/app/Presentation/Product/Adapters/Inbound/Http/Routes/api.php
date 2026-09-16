<?php

use App\Presentation\Product\Adapters\Inbound\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/merchants/{merchant}/products', [ProductController::class, 'index']);
Route::post('/merchants/{merchant}/products', [ProductController::class, 'store']);
Route::get('/merchants/{merchant}/products/{product}', [ProductController::class, 'show']);
Route::patch('/merchants/{merchant}/products/{product}', [ProductController::class, 'update']);
Route::post('/merchants/{merchant}/products/{product}/archive', [ProductController::class, 'archive']);
