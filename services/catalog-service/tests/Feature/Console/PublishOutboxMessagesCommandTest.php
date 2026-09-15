<?php

use App\Application\Product\IntegrationEvents\ProductCreatedIntegrationEvent;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Shared\Application\Ports\Outbound\IOutboxPort;

test('it publishes every unpublished outbox message and reports how many', function () {
    $outbox = app(IOutboxPort::class);
    $outbox->add(ProductCreatedIntegrationEvent::fromDomainEvent(new ProductCreated(
        ProductId::generate(),
        ProductName::fromString('Pro Plan'),
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
