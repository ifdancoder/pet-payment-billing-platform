<?php

use App\Domain\Notification\Events\NotificationCreated;
use App\Domain\Notification\Events\NotificationFailed;
use App\Domain\Notification\Events\NotificationSent;
use App\Domain\Notification\Exceptions\DeliveryAttemptNotFound;
use App\Domain\Notification\Exceptions\InvalidNotificationTransition;
use App\Domain\Notification\Exceptions\NotificationAlreadySent;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Domain\Notification\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;

test('create exposes the given data and starts out Pending with no attempts', function () {
    $id = NotificationId::generate();
    $merchantId = MerchantId::generate();
    $recipient = EmailAddress::fromString('customer@example.com');

    $notification = Notification::create(
        $id,
        $merchantId,
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        $recipient,
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );

    expect($notification->id()->equals($id))->toBeTrue()
        ->and($notification->merchantId()->equals($merchantId))->toBeTrue()
        ->and($notification->sourceEventId())->toBe('evt_payment_succeeded_1')
        ->and($notification->type())->toBe(NotificationType::PaymentReceipt)
        ->and($notification->channel())->toBe(NotificationChannel::Email)
        ->and($notification->recipient()->equals($recipient))->toBeTrue()
        ->and($notification->subject())->toBe('Your receipt')
        ->and($notification->bodyText())->toBe('Thanks for your payment.')
        ->and($notification->bodyHtml())->toBe('<p>Thanks for your payment.</p>')
        ->and($notification->deduplicationKey())->toBe('payment_receipt:pay_1:customer@example.com')
        ->and($notification->status())->toBe(NotificationStatus::Pending)
        ->and($notification->attempts())->toBe([]);
});

test('create records a NotificationCreated event', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );

    $events = $notification->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(NotificationCreated::class)
        ->and($events[0]->notificationId->equals($notification->id()))->toBeTrue()
        ->and($events[0]->deduplicationKey)->toBe('payment_receipt:pay_1:customer@example.com');
});

test('startDelivery appends a new attempt and sets the status to Processing', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $attemptId = DeliveryAttemptId::generate();

    $attempt = $notification->startDelivery($attemptId, 'fake', new DateTimeImmutable);

    expect($attempt->id()->equals($attemptId))->toBeTrue()
        ->and($notification->status())->toBe(NotificationStatus::Processing)
        ->and($notification->attempts())->toHaveCount(1);
});

test('startDelivery allows a retry after a Failed attempt', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $firstAttemptId = DeliveryAttemptId::generate();
    $notification->startDelivery($firstAttemptId, 'fake', new DateTimeImmutable);
    $notification->markFailed($firstAttemptId, 'smtp_rejected', null, new DateTimeImmutable);

    $secondAttempt = $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);

    expect($notification->status())->toBe(NotificationStatus::Processing)
        ->and($notification->attempts())->toHaveCount(2)
        ->and($secondAttempt->id()->equals($notification->attempts()[1]->id()))->toBeTrue();
});

test('startDelivery throws NotificationAlreadySent when the notification was already Sent', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $attemptId = DeliveryAttemptId::generate();
    $notification->startDelivery($attemptId, 'fake', new DateTimeImmutable);
    $notification->markSent($attemptId, ProviderReference::of('0102018f-ses-message-id'), new DateTimeImmutable);

    $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
})->throws(NotificationAlreadySent::class);

test('startDelivery throws InvalidNotificationTransition when an attempt is already in flight', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);

    $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
})->throws(InvalidNotificationTransition::class);

test('markSent sets the status to Sent, marks the attempt Succeeded and records a NotificationSent event', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $attemptId = $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $notification->pullRecordedEvents();
    $reference = ProviderReference::of('0102018f-ses-message-id');
    $sentAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $notification->markSent($attemptId, $reference, $sentAt);

    expect($notification->status())->toBe(NotificationStatus::Sent)
        ->and($notification->sentAt())->toEqual($sentAt)
        ->and($notification->attempts()[0]->status()->value)->toBe(2);
    $events = $notification->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(NotificationSent::class)
        ->and($events[0]->notificationId->equals($notification->id()))->toBeTrue()
        ->and($events[0]->providerReference->equals($reference))->toBeTrue();
});

test('markSent is idempotent when the notification is already Sent', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $attemptId = $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $notification->markSent($attemptId, ProviderReference::of('0102018f-ses-message-id'), new DateTimeImmutable);
    $notification->pullRecordedEvents();

    $notification->markSent($attemptId, ProviderReference::of('0102018f-other-message-id'), new DateTimeImmutable);

    expect($notification->status())->toBe(NotificationStatus::Sent)
        ->and($notification->pullRecordedEvents())->toBe([]);
});

test('markSent throws InvalidNotificationTransition when there is no attempt in flight', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );

    $notification->markSent(DeliveryAttemptId::generate(), ProviderReference::of('0102018f-ses-message-id'), new DateTimeImmutable);
})->throws(InvalidNotificationTransition::class);

test('markSent throws DeliveryAttemptNotFound when the attempt id does not belong to this notification', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);

    $notification->markSent(DeliveryAttemptId::generate(), ProviderReference::of('0102018f-ses-message-id'), new DateTimeImmutable);
})->throws(DeliveryAttemptNotFound::class);

test('markFailed sets the status to Failed, marks the attempt Failed and records a NotificationFailed event', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );
    $attemptId = $notification->startDelivery(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $notification->pullRecordedEvents();
    $failedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $notification->markFailed($attemptId, 'smtp_rejected', 'Mailbox unavailable.', $failedAt);

    expect($notification->status())->toBe(NotificationStatus::Failed)
        ->and($notification->failedAt())->toEqual($failedAt)
        ->and($notification->attempts()[0]->status()->value)->toBe(3);
    $events = $notification->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(NotificationFailed::class)
        ->and($events[0]->notificationId->equals($notification->id()))->toBeTrue()
        ->and($events[0]->failureCode)->toBe('smtp_rejected');
});

test('markFailed throws InvalidNotificationTransition when there is no attempt in flight', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );

    $notification->markFailed(DeliveryAttemptId::generate(), 'smtp_rejected', null, new DateTimeImmutable);
})->throws(InvalidNotificationTransition::class);

test('pullRecordedEvents empties the recorded events', function () {
    $notification = Notification::create(
        NotificationId::generate(),
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
    );

    $notification->pullRecordedEvents();

    expect($notification->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data and status without recording an event', function () {
    $id = NotificationId::generate();

    $notification = Notification::reconstitute(
        $id,
        MerchantId::generate(),
        'evt_payment_succeeded_1',
        NotificationType::PaymentReceipt,
        NotificationChannel::Email,
        EmailAddress::fromString('customer@example.com'),
        'Your receipt',
        'Thanks for your payment.',
        '<p>Thanks for your payment.</p>',
        'payment_receipt:pay_1:customer@example.com',
        NotificationStatus::Sent,
        [],
        new DateTimeImmutable,
        null,
    );

    expect($notification->id()->equals($id))->toBeTrue()
        ->and($notification->status())->toBe(NotificationStatus::Sent)
        ->and($notification->pullRecordedEvents())->toBe([]);
});
