<?php

use App\Presentation\Subscription\Adapters\Inbound\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/merchants/{merchant}/subscriptions', [SubscriptionController::class, 'index']);
Route::post('/merchants/{merchant}/subscriptions', [SubscriptionController::class, 'store']);
Route::get('/merchants/{merchant}/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
