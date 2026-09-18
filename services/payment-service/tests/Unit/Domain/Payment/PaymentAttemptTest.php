<?php

use App\Domain\Payment\Exceptions\InvalidPaymentAttemptTransition;
use App\Domain\Payment\PaymentAttempt;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentAttemptStatus;
use App\Domain\Payment\ValueObjects\ProviderReference;

test('start exposes the given data and starts out Pending', function () {
    $id = PaymentAttemptId::generate();
    $startedAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');

    $attempt = PaymentAttempt::start($id, 'fake', $startedAt);

    expect($attempt->id()->equals($id))->toBeTrue()
        ->and($attempt->provider())->toBe('fake')
        ->and($attempt->status())->toBe(PaymentAttemptStatus::Pending)
        ->and($attempt->startedAt())->toEqual($startedAt)
        ->and($attempt->completedAt())->toBeNull()
        ->and($attempt->providerReference())->toBeNull();
});

test('succeed sets the status to Succeeded and records the provider reference', function () {
    $attempt = PaymentAttempt::start(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
    $reference = ProviderReference::of('pi_123');
    $completedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $attempt->succeed($reference, $completedAt);

    expect($attempt->status())->toBe(PaymentAttemptStatus::Succeeded)
        ->and($attempt->providerReference()->equals($reference))->toBeTrue()
        ->and($attempt->completedAt())->toEqual($completedAt);
});

test('succeed throws when the attempt is not Pending', function () {
    $attempt = PaymentAttempt::start(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
    $attempt->succeed(ProviderReference::of('pi_123'), new DateTimeImmutable);

    $attempt->succeed(ProviderReference::of('pi_456'), new DateTimeImmutable);
})->throws(InvalidPaymentAttemptTransition::class);

test('fail sets the status to Failed and records the failure reason', function () {
    $attempt = PaymentAttempt::start(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
    $completedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $attempt->fail('card_declined', 'Your card was declined.', $completedAt);

    expect($attempt->status())->toBe(PaymentAttemptStatus::Failed)
        ->and($attempt->failureCode())->toBe('card_declined')
        ->and($attempt->failureMessage())->toBe('Your card was declined.')
        ->and($attempt->completedAt())->toEqual($completedAt);
});

test('fail throws when the attempt is not Pending', function () {
    $attempt = PaymentAttempt::start(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
    $attempt->fail('card_declined', null, new DateTimeImmutable);

    $attempt->fail('card_declined', null, new DateTimeImmutable);
})->throws(InvalidPaymentAttemptTransition::class);

test('reconstitute exposes the given data', function () {
    $id = PaymentAttemptId::generate();
    $reference = ProviderReference::of('pi_123');
    $startedAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $completedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $attempt = PaymentAttempt::reconstitute($id, 'stripe', $reference, PaymentAttemptStatus::Succeeded, null, null, $startedAt, $completedAt);

    expect($attempt->id()->equals($id))->toBeTrue()
        ->and($attempt->provider())->toBe('stripe')
        ->and($attempt->providerReference()->equals($reference))->toBeTrue()
        ->and($attempt->status())->toBe(PaymentAttemptStatus::Succeeded)
        ->and($attempt->startedAt())->toEqual($startedAt)
        ->and($attempt->completedAt())->toEqual($completedAt);
});
