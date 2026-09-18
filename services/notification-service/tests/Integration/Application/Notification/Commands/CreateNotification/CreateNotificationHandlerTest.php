<?php

use App\Application\Notification\Commands\CreateNotification\CreateNotificationCommand;
use App\Application\Notification\Commands\CreateNotification\CreateNotificationHandler;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function aCreateNotificationCommand(array $overrides = []): CreateNotificationCommand
{
    $defaults = [
        'eventId' => (string) Str::uuid(),
        'eventType' => 'payment.succeeded.v1',
        'merchantId' => MerchantId::generate()->toString(),
        'type' => NotificationType::PaymentReceipt->value,
        'channel' => NotificationChannel::Email->value,
        'recipientEmail' => 'customer@example.com',
        'subject' => 'Your receipt',
        'bodyText' => 'Thanks for your payment.',
        'bodyHtml' => '<p>Thanks for your payment.</p>',
        'deduplicationKey' => 'payment_receipt:'.Str::uuid().':customer@example.com',
    ];

    return new CreateNotificationCommand(...array_merge($defaults, $overrides));
}

test('handle creates a Pending notification with the given content', function () {
    $command = aCreateNotificationCommand();

    $notification = app(CreateNotificationHandler::class)->handle($command);

    expect($notification)->not->toBeNull()
        ->and($notification->merchantId()->toString())->toBe($command->merchantId)
        ->and($notification->recipient()->toString())->toBe('customer@example.com')
        ->and($notification->subject())->toBe('Your receipt')
        ->and($notification->status())->toBe(NotificationStatus::Pending);
});

test('handle persists the notification', function () {
    $command = aCreateNotificationCommand();

    $notification = app(CreateNotificationHandler::class)->handle($command);

    $persisted = app(INotificationRepositoryPort::class)->get($notification->id(), $notification->merchantId());
    expect($persisted->id()->equals($notification->id()))->toBeTrue();
});

test('handle is a no-op and returns null when the same event id is redelivered', function () {
    $command = aCreateNotificationCommand();
    app(CreateNotificationHandler::class)->handle($command);

    $result = app(CreateNotificationHandler::class)->handle($command);

    expect($result)->toBeNull()
        ->and(app(INotificationRepositoryPort::class)->all(MerchantId::fromString($command->merchantId)))->toHaveCount(1);
});

test('handle returns the existing notification instead of double-creating when a different event has the same deduplication key', function () {
    $deduplicationKey = 'payment_receipt:'.Str::uuid().':customer@example.com';
    $first = aCreateNotificationCommand(['deduplicationKey' => $deduplicationKey]);
    $original = app(CreateNotificationHandler::class)->handle($first);

    $second = aCreateNotificationCommand(['eventId' => (string) Str::uuid(), 'merchantId' => $first->merchantId, 'deduplicationKey' => $deduplicationKey]);
    $result = app(CreateNotificationHandler::class)->handle($second);

    expect($result->id()->equals($original->id()))->toBeTrue()
        ->and(app(INotificationRepositoryPort::class)->all(MerchantId::fromString($first->merchantId)))->toHaveCount(1);
});
