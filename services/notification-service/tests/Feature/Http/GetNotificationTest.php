<?php

use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Infrastructure\Notification\Adapters\Persistence\Mappers\NotificationMapper;
use App\Infrastructure\Notification\Adapters\Persistence\Repositories\EloquentNotificationRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function aNotification(MerchantId $merchantId): Notification
{
    return Notification::create(
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
}

test('a request returns the matching notification with its attempts', function () {
    $merchantId = MerchantId::generate();
    $notification = aNotification($merchantId);
    $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
    (new EloquentNotificationRepository(new NotificationMapper))->save($notification);

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/notifications/{$notification->id()->toString()}");

    $response->assertOk()
        ->assertJsonPath('data.id', $notification->id()->toString())
        ->assertJsonPath('data.status', 'processing')
        ->assertJsonPath('data.recipient', 'customer@example.com')
        ->assertJsonPath('data.subject', 'Your receipt')
        ->assertJsonCount(1, 'data.attempts')
        ->assertJsonPath('data.attempts.0.provider', 'fake');
});

test('a request for a non-existent notification returns not found', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/notifications/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});

test('a request for a notification belonging to a different merchant returns not found', function () {
    $notification = aNotification(MerchantId::generate());
    (new EloquentNotificationRepository(new NotificationMapper))->save($notification);

    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/notifications/{$notification->id()->toString()}");

    $response->assertNotFound();
});
