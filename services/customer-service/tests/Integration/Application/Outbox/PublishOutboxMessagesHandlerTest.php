<?php

use App\Application\Customer\IntegrationEvents\CustomerCreatedIntegrationEvent;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Shared\Application\Outbox\PublishOutboxMessagesHandler;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use Illuminate\Support\Facades\Log;

function anIntegrationEvent(): CustomerCreatedIntegrationEvent
{
    return CustomerCreatedIntegrationEvent::fromDomainEvent(new CustomerCreated(
        CustomerId::generate(),
        aMerchantId(),
        Email::fromString('jane@example.com'),
        CustomerName::fromString('Jane Doe'),
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
