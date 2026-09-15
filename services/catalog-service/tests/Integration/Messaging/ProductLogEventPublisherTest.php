<?php

use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Infrastructure\Product\Adapters\Messaging\LogEventPublisher;
use Illuminate\Support\Facades\Log;

test('publish logs the event class name', function () {
    Log::spy();
    $event = new ProductCreated(ProductId::generate(), ProductName::fromString('Pro Plan'));

    (new LogEventPublisher)->publish($event);

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, ProductCreated::class));
});
