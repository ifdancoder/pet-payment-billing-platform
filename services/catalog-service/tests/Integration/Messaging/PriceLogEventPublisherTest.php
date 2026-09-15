<?php

use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Price\Adapters\Messaging\LogEventPublisher;
use Illuminate\Support\Facades\Log;

test('publish logs the event class name', function () {
    Log::spy();
    $event = new PriceCreated(PriceId::generate(), ProductId::generate(), Money::of(1999, Currency::USD), PriceType::OneTime, null);

    (new LogEventPublisher)->publish($event);

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, PriceCreated::class));
});
