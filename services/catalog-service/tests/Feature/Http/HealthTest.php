<?php

use Illuminate\Support\Facades\DB;

test('startup reports ok without touching the database', function () {
    $this->getJson('/health/startup')->assertOk()->assertJsonPath('status', 'ok');
});

test('live reports ok without touching the database', function () {
    $this->getJson('/health/live')->assertOk()->assertJsonPath('status', 'ok');
});

test('ready reports ok when the database is reachable', function () {
    $this->getJson('/health/ready')->assertOk()->assertJsonPath('status', 'ok');
});

test('ready reports unavailable when the database is unreachable', function () {
    config(['database.connections.sqlite.database' => '/nonexistent/path/does-not-exist.sqlite']);
    DB::purge('sqlite');

    $this->getJson('/health/ready')->assertStatus(503)->assertJsonPath('status', 'unavailable');
});
