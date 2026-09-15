<?php

use App\Application\Product\IntegrationEvents\ProductCreatedIntegrationEvent;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Shared\Application\Outbox\PublishOutboxMessagesHandler;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use Illuminate\Support\Facades\Log;

function anIntegrationEvent(): ProductCreatedIntegrationEvent
{
    return ProductCreatedIntegrationEvent::fromDomainEvent(new ProductCreated(
        ProductId::generate(),
        ProductName::fromString('Pro Plan'),
    ));
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
    $event = anIntegrationEvent();
    $outbox->add($event);
    app(PublishOutboxMessagesHandler::class)->handle();
    Log::spy();

    $published = app(PublishOutboxMessagesHandler::class)->handle();

    expect($published)->toBe(0);
    Log::shouldNotHaveReceived('info');
});
