<?php

use App\Application\Notification\Commands\CreateNotification\CreateNotificationCommand;
use App\Application\Notification\Commands\CreateNotification\CreateNotificationHandler;
use App\Application\Notification\Commands\DeliverNotifications\DeliverNotificationsHandler;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

function aPendingNotificationCommand(): CreateNotificationCommand
{
    return new CreateNotificationCommand(
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
}

test('handle delivers every Pending notification and returns how many it delivered', function () {
    app(CreateNotificationHandler::class)->handle(aPendingNotificationCommand());
    app(CreateNotificationHandler::class)->handle(aPendingNotificationCommand());

    $delivered = app(DeliverNotificationsHandler::class)->handle();

    expect($delivered)->toBe(2);
});

test('handle leaves no Pending notifications behind', function () {
    app(CreateNotificationHandler::class)->handle(aPendingNotificationCommand());

    app(DeliverNotificationsHandler::class)->handle();

    expect(app(INotificationRepositoryPort::class)->pending())->toBe([]);
});

test('handle returns zero when there is nothing pending', function () {
    expect(app(DeliverNotificationsHandler::class)->handle())->toBe(0);
});
