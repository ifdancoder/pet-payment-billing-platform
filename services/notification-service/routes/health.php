<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health/startup', fn () => response()->json(['status' => 'ok']));

Route::get('/health/live', fn () => response()->json(['status' => 'ok']));

Route::get('/health/ready', function () {
    try {
        DB::connection()->getPdo();
    } catch (Throwable) {
        return response()->json(['status' => 'unavailable'], 503);
    }

    return response()->json(['status' => 'ok']);
});
