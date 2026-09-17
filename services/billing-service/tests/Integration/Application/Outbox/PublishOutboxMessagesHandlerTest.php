<?php

use App\Shared\Application\Outbox\PublishOutboxMessagesHandler;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Support\FakeIntegrationEvent;

function anIntegrationEvent(): FakeIntegrationEvent
{
    return new FakeIntegrationEvent((string) Str::uuid());
}

test('handle publishes every unpublished message and marks it published', function () {
    $outbox = app(IOutboxPort::class);
    $outbox->add(anIntegrationEvent());
    $outbox->add(anIntegrationEvent());
    Log::spy();

    $published = app(PublishOutboxMessagesHandler::class)->handle();

    expect($published)->toBe(2)
        ->and($outbox->unpublished())->toBe([]);
    Log::shouldHaveReceived('info')->twice();
});

test('handle does nothing when there are no unpublished messages', function () {
    $published = app(PublishOutboxMessagesHandler::class)->handle();

    expect($published)->toBe(0);
});

test('handle does not republish an already-published message', function () {
    $outbox = app(IOutboxPort::class);
    $outbox->add(anIntegrationEvent());
    app(PublishOutboxMessagesHandler::class)->handle();
    Log::spy();

    $published = app(PublishOutboxMessagesHandler::class)->handle();

    expect($published)->toBe(0);
    Log::shouldNotHaveReceived('info');
});
