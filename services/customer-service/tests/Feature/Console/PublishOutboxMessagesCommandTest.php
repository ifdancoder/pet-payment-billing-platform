<?php

use App\Application\Customer\IntegrationEvents\CustomerCreatedIntegrationEvent;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Shared\Application\Ports\Outbound\IOutboxPort;

test('it publishes every unpublished outbox message and reports how many', function () {
    $outbox = app(IOutboxPort::class);
    $outbox->add(CustomerCreatedIntegrationEvent::fromDomainEvent(new CustomerCreated(
        CustomerId::generate(),
        Email::fromString('jane@example.com'),
        CustomerName::fromString('Jane Doe'),
    )));

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
