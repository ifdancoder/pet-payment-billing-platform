<?php

use App\Application\Notification\Commands\CreateNotification\CreateNotificationCommand;
use App\Application\Notification\Commands\CreateNotification\CreateNotificationHandler;
use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

test('it delivers every Pending notification and reports how many', function () {
    app(CreateNotificationHandler::class)->handle(new CreateNotificationCommand(
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
    ));

    $this->artisan('notifications:deliver')
        ->expectsOutputToContain('Delivered 1 notification(s).')
        ->assertExitCode(0);

    expect(app(INotificationRepositoryPort::class)->pending())->toBe([]);
});

test('it reports zero when there is nothing pending', function () {
    $this->artisan('notifications:deliver')
        ->expectsOutputToContain('Delivered 0 notification(s).')
        ->assertExitCode(0);
});
