<?php

use App\Presentation\Subscription\Adapters\Inbound\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/merchants/{merchant}/subscriptions', [SubscriptionController::class, 'store']);
