<?php

use App\Shared\Application\ReadModels\OutboxMessage;
use App\Shared\Infrastructure\Messaging\LogEventPublisher;
use Illuminate\Support\Facades\Log;

test('publish logs the event type and id', function () {
    Log::spy();
    $message = new OutboxMessage(
        '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'product.created.v1',
        'product',
        '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        ['name' => 'Pro Plan'],
        new DateTimeImmutable,
    );

    (new LogEventPublisher)->publish($message);

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $text) => str_contains($text, 'product.created.v1')
            && str_contains($text, '9f8e7d6c-5b4a-4321-9876-abcdef012345'));
});
