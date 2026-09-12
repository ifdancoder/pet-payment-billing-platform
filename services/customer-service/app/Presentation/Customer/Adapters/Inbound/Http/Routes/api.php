<?php

use App\Presentation\Customer\Adapters\Inbound\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::post('/customers', [CustomerController::class, 'store']);
