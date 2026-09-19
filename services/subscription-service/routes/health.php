<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health checks
|--------------------------------------------------------------------------
|
| Deliberately outside app/Presentation — these aren't versioned business
| routes, they're an infrastructure concern the same way /up already is.
| Kubernetes probes three different questions here, not one:
|
| - startup/live: has the process booted, is it still responding at all?
|   No external dependency checks — a database outage must not make
|   Kubernetes think the process itself is dead and restart it (that
|   would just repeat the outage without fixing anything).
| - ready: can this pod currently serve traffic? This is where an
|   external dependency (the database) actually gets checked — a pod
|   that can't reach its database should stop receiving traffic, not
|   get killed.
|
*/

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
