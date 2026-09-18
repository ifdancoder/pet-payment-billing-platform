<?php

use App\Domain\Payment\Events\PaymentCreated;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Payment\Events\PaymentSucceeded;
use App\Domain\Payment\Exceptions\InvalidPaymentTransition;
use App\Domain\Payment\Exceptions\PaymentAlreadyCompleted;
use App\Domain\Payment\Exceptions\PaymentAttemptNotFound;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\PaymentStatus;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;

test('create exposes the given data and starts out Pending with no attempts', function () {
    $id = PaymentId::generate();
    $invoiceId = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $money = Money::of(1999, Currency::USD);

    $payment = Payment::create($id, $invoiceId, $merchantId, $customerId, $money);

    expect($payment->id()->equals($id))->toBeTrue()
        ->and($payment->invoiceId()->equals($invoiceId))->toBeTrue()
        ->and($payment->merchantId()->equals($merchantId))->toBeTrue()
        ->and($payment->customerId()->equals($customerId))->toBeTrue()
        ->and($payment->money()->equals($money))->toBeTrue()
        ->and($payment->status())->toBe(PaymentStatus::Pending)
        ->and($payment->attempts())->toBe([]);
});

test('create records a PaymentCreated event', function () {
    $id = PaymentId::generate();
    $invoiceId = InvoiceId::generate();

    $payment = Payment::create($id, $invoiceId, MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $events = $payment->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentCreated::class)
        ->and($events[0]->paymentId->equals($id))->toBeTrue()
        ->and($events[0]->invoiceId->equals($invoiceId))->toBeTrue();
});

test('startAttempt appends a new attempt and sets the status to Processing', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $attemptId = PaymentAttemptId::generate();

    $attempt = $payment->startAttempt($attemptId, 'fake', new DateTimeImmutable);

    expect($attempt->id()->equals($attemptId))->toBeTrue()
        ->and($payment->status())->toBe(PaymentStatus::Processing)
        ->and($payment->attempts())->toHaveCount(1);
});

test('startAttempt allows a retry after a Failed attempt', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $firstAttemptId = PaymentAttemptId::generate();
    $payment->startAttempt($firstAttemptId, 'fake', new DateTimeImmutable);
    $payment->fail($firstAttemptId, 'card_declined', null, new DateTimeImmutable);

    $secondAttempt = $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);

    expect($payment->status())->toBe(PaymentStatus::Processing)
        ->and($payment->attempts())->toHaveCount(2)
        ->and($secondAttempt->id()->equals($payment->attempts()[1]->id()))->toBeTrue();
});

test('startAttempt throws PaymentAlreadyCompleted when the payment already Succeeded', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $attemptId = PaymentAttemptId::generate();
    $payment->startAttempt($attemptId, 'fake', new DateTimeImmutable);
    $payment->succeed($attemptId, ProviderReference::of('pi_123'), new DateTimeImmutable);

    $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
})->throws(PaymentAlreadyCompleted::class);

test('startAttempt throws InvalidPaymentTransition when an attempt is already in flight', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);

    $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
})->throws(InvalidPaymentTransition::class);

test('succeed sets the status to Succeeded, marks the attempt Succeeded and records a PaymentSucceeded event', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $attemptId = $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $payment->pullRecordedEvents();
    $reference = ProviderReference::of('pi_123');
    $paidAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $payment->succeed($attemptId, $reference, $paidAt);

    expect($payment->status())->toBe(PaymentStatus::Succeeded)
        ->and($payment->paidAt())->toEqual($paidAt)
        ->and($payment->attempts()[0]->status()->value)->toBe(2); // Succeeded
    $events = $payment->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentSucceeded::class)
        ->and($events[0]->paymentId->equals($payment->id()))->toBeTrue()
        ->and($events[0]->providerReference->equals($reference))->toBeTrue();
});

test('succeed is idempotent when the payment is already Succeeded', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $attemptId = $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $payment->succeed($attemptId, ProviderReference::of('pi_123'), new DateTimeImmutable);
    $payment->pullRecordedEvents();

    $payment->succeed($attemptId, ProviderReference::of('pi_999'), new DateTimeImmutable);

    expect($payment->status())->toBe(PaymentStatus::Succeeded)
        ->and($payment->pullRecordedEvents())->toBe([]);
});

test('succeed throws InvalidPaymentTransition when there is no attempt in flight', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));

    $payment->succeed(PaymentAttemptId::generate(), ProviderReference::of('pi_123'), new DateTimeImmutable);
})->throws(InvalidPaymentTransition::class);

test('succeed throws PaymentAttemptNotFound when the attempt id does not belong to this payment', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);

    $payment->succeed(PaymentAttemptId::generate(), ProviderReference::of('pi_123'), new DateTimeImmutable);
})->throws(PaymentAttemptNotFound::class);

test('fail sets the status to Failed, marks the attempt Failed and records a PaymentFailed event', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    $attemptId = $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $payment->pullRecordedEvents();
    $failedAt = new DateTimeImmutable('2026-09-01T00:05:00+00:00');

    $payment->fail($attemptId, 'card_declined', 'Your card was declined.', $failedAt);

    expect($payment->status())->toBe(PaymentStatus::Failed)
        ->and($payment->failedAt())->toEqual($failedAt)
        ->and($payment->attempts()[0]->status()->value)->toBe(3); // Failed
    $events = $payment->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PaymentFailed::class)
        ->and($events[0]->paymentId->equals($payment->id()))->toBeTrue()
        ->and($events[0]->failureCode)->toBe('card_declined');
});

test('fail throws InvalidPaymentTransition when there is no attempt in flight', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));

    $payment->fail(PaymentAttemptId::generate(), 'card_declined', null, new DateTimeImmutable);
})->throws(InvalidPaymentTransition::class);

test('pullRecordedEvents empties the recorded events', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));

    $payment->pullRecordedEvents();

    expect($payment->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data and status without recording an event', function () {
    $id = PaymentId::generate();

    $payment = Payment::reconstitute(
        $id,
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        Money::of(1999, Currency::USD),
        PaymentStatus::Succeeded,
        [],
        new DateTimeImmutable,
        null,
    );

    expect($payment->id()->equals($id))->toBeTrue()
        ->and($payment->status())->toBe(PaymentStatus::Succeeded)
        ->and($payment->pullRecordedEvents())->toBe([]);
});
