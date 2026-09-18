<?php

use App\Application\Notification\Commands\CreateNotification\CreateNotificationCommand;
use App\Application\Notification\Commands\CreateNotification\CreateNotificationHandler;
use App\Application\Notification\Commands\DeliverNotification\DeliverNotificationCommand;
use App\Application\Notification\Commands\DeliverNotification\DeliverNotificationHandler;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

test('handle delivers a Pending notification and marks it Sent', function () {
    $command = new CreateNotificationCommand(
        (string) Str::uuid(),
        'payment.succeeded.v1',
        MerchantId::generate()->toString(),
        NotificationType::PaymentReceipt->value,
        NotificationChannel::Email->value,
        'customer@example.com',
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:'.Str::uuid().':customer@example.com',
    );
    $notification = app(CreateNotificationHandler::class)->handle($command);

    $delivered = app(DeliverNotificationHandler::class)->handle(new DeliverNotificationCommand($notification->id()->toString(), $notification->merchantId()->toString()));

    expect($delivered->status())->toBe(NotificationStatus::Sent)
        ->and($delivered->sentAt())->not->toBeNull()
        ->and($delivered->attempts())->toHaveCount(1);
});
