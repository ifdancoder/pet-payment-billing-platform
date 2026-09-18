<?php

use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Infrastructure\Notification\Adapters\Persistence\Mappers\NotificationMapper;
use App\Infrastructure\Notification\Adapters\Persistence\Repositories\EloquentNotificationRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function seedANotification(MerchantId $merchantId): Notification
{
    $notification = Notification::create(
        NotificationId::generate(),
        $merchantId,
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:'.uniqid().':customer@example.com',
    );
    (new EloquentNotificationRepository(new NotificationMapper))->save($notification);

    return $notification;
}

test('a request returns every existing notification for the given merchant', function () {
    $merchantId = MerchantId::generate();
    seedANotification($merchantId);
    seedANotification($merchantId);
    seedANotification(MerchantId::generate());

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/notifications");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no notifications for the given merchant', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/notifications');

    $response->assertOk()->assertJsonCount(0, 'data');
});
