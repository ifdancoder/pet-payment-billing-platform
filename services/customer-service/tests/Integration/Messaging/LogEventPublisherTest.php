<?php

use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Messaging\LogEventPublisher;
use Illuminate\Support\Facades\Log;

test('publish logs the event class name', function () {
    Log::spy();
    $event = new CustomerCreated(CustomerId::generate(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    (new LogEventPublisher)->publish($event);

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, CustomerCreated::class));
});
