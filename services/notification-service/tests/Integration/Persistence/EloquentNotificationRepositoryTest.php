<?php

use App\Domain\Notification\Exceptions\NotificationNotFound;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Domain\Notification\ValueObjects\ProviderReference;
use App\Infrastructure\Notification\Adapters\Persistence\Mappers\NotificationMapper;
use App\Infrastructure\Notification\Adapters\Persistence\Models\NotificationModel;
use App\Infrastructure\Notification\Adapters\Persistence\Repositories\EloquentNotificationRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function makeNotification(?MerchantId $merchantId = null, ?string $deduplicationKey = null): Notification
{
    return Notification::create(
        NotificationId::generate(),
        $merchantId ?? MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        $deduplicationKey ?? 'payment_receipt:pay_'.uniqid().':customer@example.com',
    );
}

test('save persists a new notification with no attempts', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);
    $notification = makeNotification();

    $repository->save($notification);

    $model = NotificationModel::query()->with('attempts')->find($notification->id()->toString());
    expect($model)->not->toBeNull()
        ->and($model->attempts)->toHaveCount(0)
        ->and($model->status)->toBe(1); // Pending
});

test('save persists new attempts and updates existing ones as they transition', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);
    $notification = makeNotification();
    $repository->save($notification);
    $attemptId = $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $repository->save($notification);

    $notification->markSent($attemptId, ProviderReference::of('0102018f-ses-message-id'), new DateTimeImmutable);
    $repository->save($notification);

    $model = NotificationModel::query()->with('attempts')->find($notification->id()->toString());
    expect($model->attempts)->toHaveCount(1)
        ->and($model->attempts->first()->status)->toBe(2) // Succeeded
        ->and($model->attempts->first()->provider_reference)->toBe('0102018f-ses-message-id')
        ->and($model->status)->toBe(3); // Sent
});

test('get returns the matching notification for the owning merchant, with its attempts intact', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);
    $merchantId = MerchantId::generate();
    $notification = makeNotification($merchantId);
    $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
    $repository->save($notification);

    $found = $repository->get($notification->id(), $merchantId);

    expect($found->id()->equals($notification->id()))->toBeTrue()
        ->and($found->attempts())->toHaveCount(1)
        ->and($found->recipient()->equals($notification->recipient()))->toBeTrue();
});

test('get throws NotificationNotFound when no notification matches', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);

    $repository->get(NotificationId::generate(), MerchantId::generate());
})->throws(NotificationNotFound::class);

test('get throws NotificationNotFound when the notification belongs to a different merchant', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);
    $notification = makeNotification();
    $repository->save($notification);

    $repository->get($notification->id(), MerchantId::generate());
})->throws(NotificationNotFound::class);

test('all returns every persisted notification for the given merchant', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);
    $merchantId = MerchantId::generate();
    $repository->save(makeNotification($merchantId));
    $repository->save(makeNotification($merchantId));
    $repository->save(makeNotification());

    expect($repository->all($merchantId))->toHaveCount(2);
});

test('all returns an empty array when there are no notifications for the given merchant', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);

    expect($repository->all(MerchantId::generate()))->toBe([]);
});

test('findByDeduplicationKey returns the matching notification', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);
    $notification = makeNotification(null, 'payment_receipt:pay_1:customer@example.com');
    $repository->save($notification);

    $found = $repository->findByDeduplicationKey('payment_receipt:pay_1:customer@example.com');

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($notification->id()))->toBeTrue();
});

test('findByDeduplicationKey returns null when no notification matches', function () {
    $repository = new EloquentNotificationRepository(new NotificationMapper);

    expect($repository->findByDeduplicationKey('payment_receipt:pay_missing:customer@example.com'))->toBeNull();
});
