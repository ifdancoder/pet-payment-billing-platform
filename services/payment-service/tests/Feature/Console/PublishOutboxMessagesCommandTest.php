<?php

use App\Shared\Application\Ports\Outbound\IOutboxPort;
use Illuminate\Support\Str;
use Tests\Support\FakeIntegrationEvent;

test('it publishes every unpublished outbox message and reports how many', function () {
    $outbox = app(IOutboxPort::class);
    $outbox->add(new FakeIntegrationEvent((string) Str::uuid()));

    $this->artisan('outbox:publish')
        ->expectsOutputToContain('Published 1 outbox message(s).')
        ->assertExitCode(0);

    expect($outbox->unpublished())->toBe([]);
});

test('it reports zero when there is nothing to publish', function () {
    $this->artisan('outbox:publish')
        ->expectsOutputToContain('Published 0 outbox message(s).')
        ->assertExitCode(0);
});
