<?php

use App\Domain\Notification\DeliveryAttempt;
use App\Domain\Notification\Exceptions\InvalidDeliveryAttemptTransition;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\DeliveryAttemptStatus;
use App\Domain\Notification\ValueObjects\ProviderReference;

test('start exposes the given data and starts out Pending', function () {
    $id = DeliveryAttemptId::generate();
    $startedAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');

    $attempt = DeliveryAttempt::start($id, 'fake', $startedAt);

    expect($attempt->id()->equals($id))->toBeTrue()
        ->and($attempt->provider())->toBe('fake')
        ->and($attempt->status())->toBe(DeliveryAttemptStatus::Pending)
        ->and($attempt->startedAt())->toEqual($startedAt)
        ->and($attempt->completedAt())->toBeNull()
        ->and($attempt->providerReference())->toBeNull();
});

test('succeed sets the status to Succeeded and records the provider reference', function () {
    $attempt = DeliveryAttempt::start(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
    $reference = ProviderReference::of('0102018f-ses-message-id');
    $completedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $attempt->succeed($reference, $completedAt);

    expect($attempt->status())->toBe(DeliveryAttemptStatus::Succeeded)
        ->and($attempt->providerReference()->equals($reference))->toBeTrue()
        ->and($attempt->completedAt())->toEqual($completedAt);
});

test('succeed throws when the attempt is not Pending', function () {
    $attempt = DeliveryAttempt::start(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
    $attempt->succeed(ProviderReference::of('0102018f-ses-message-id'), new DateTimeImmutable);

    $attempt->succeed(ProviderReference::of('0102018f-other-message-id'), new DateTimeImmutable);
})->throws(InvalidDeliveryAttemptTransition::class);

test('fail sets the status to Failed and records the failure reason', function () {
    $attempt = DeliveryAttempt::start(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
    $completedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $attempt->fail('smtp_rejected', 'Mailbox unavailable.', $completedAt);

    expect($attempt->status())->toBe(DeliveryAttemptStatus::Failed)
        ->and($attempt->failureCode())->toBe('smtp_rejected')
        ->and($attempt->failureMessage())->toBe('Mailbox unavailable.')
        ->and($attempt->completedAt())->toEqual($completedAt);
});

test('fail throws when the attempt is not Pending', function () {
    $attempt = DeliveryAttempt::start(DeliveryAttemptId::generate(), 'fake', new DateTimeImmutable);
    $attempt->fail('smtp_rejected', null, new DateTimeImmutable);

    $attempt->fail('smtp_rejected', null, new DateTimeImmutable);
})->throws(InvalidDeliveryAttemptTransition::class);

test('reconstitute exposes the given data', function () {
    $id = DeliveryAttemptId::generate();
    $reference = ProviderReference::of('0102018f-ses-message-id');
    $startedAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $completedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $attempt = DeliveryAttempt::reconstitute($id, 'ses', $reference, DeliveryAttemptStatus::Succeeded, null, null, $startedAt, $completedAt);

    expect($attempt->id()->equals($id))->toBeTrue()
        ->and($attempt->provider())->toBe('ses')
        ->and($attempt->providerReference()->equals($reference))->toBeTrue()
        ->and($attempt->status())->toBe(DeliveryAttemptStatus::Succeeded)
        ->and($attempt->startedAt())->toEqual($startedAt)
        ->and($attempt->completedAt())->toEqual($completedAt);
});
