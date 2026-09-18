<?php

use App\Domain\Payment\Exceptions\PaymentNotFound;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Infrastructure\Payment\Adapters\Persistence\Mappers\PaymentMapper;
use App\Infrastructure\Payment\Adapters\Persistence\Models\PaymentModel;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function makePayment(?MerchantId $merchantId = null, ?InvoiceId $invoiceId = null): Payment
{
    return Payment::create(
        PaymentId::generate(),
        $invoiceId ?? InvoiceId::generate(),
        $merchantId ?? MerchantId::generate(),
        CustomerId::generate(),
        Money::of(1999, Currency::USD),
    );
}

test('save persists a new payment with no attempts', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $payment = makePayment();

    $repository->save($payment);

    $model = PaymentModel::query()->with('attempts')->find($payment->id()->toString());
    expect($model)->not->toBeNull()
        ->and($model->attempts)->toHaveCount(0)
        ->and($model->status)->toBe(1);
});

test('save persists new attempts and updates existing ones as they transition', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $payment = makePayment();
    $repository->save($payment);
    $attemptId = $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable)->id();
    $repository->save($payment);

    $payment->succeed($attemptId, ProviderReference::of('pi_123'), new DateTimeImmutable);
    $repository->save($payment);

    $model = PaymentModel::query()->with('attempts')->find($payment->id()->toString());
    expect($model->attempts)->toHaveCount(1)
        ->and($model->attempts->first()->status)->toBe(2)
        ->and($model->attempts->first()->provider_reference)->toBe('pi_123')
        ->and($model->status)->toBe(3);
});

test('get returns the matching payment for the owning merchant, with its attempts intact', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $merchantId = MerchantId::generate();
    $payment = makePayment($merchantId);
    $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
    $repository->save($payment);

    $found = $repository->get($payment->id(), $merchantId);

    expect($found->id()->equals($payment->id()))->toBeTrue()
        ->and($found->attempts())->toHaveCount(1)
        ->and($found->money()->equals($payment->money()))->toBeTrue();
});

test('get throws PaymentNotFound when no payment matches', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);

    $repository->get(PaymentId::generate(), MerchantId::generate());
})->throws(PaymentNotFound::class);

test('get throws PaymentNotFound when the payment belongs to a different merchant', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $payment = makePayment();
    $repository->save($payment);

    $repository->get($payment->id(), MerchantId::generate());
})->throws(PaymentNotFound::class);

test('all returns every persisted payment for the given merchant', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $merchantId = MerchantId::generate();
    $repository->save(makePayment($merchantId));
    $repository->save(makePayment($merchantId));
    $repository->save(makePayment());

    expect($repository->all($merchantId))->toHaveCount(2);
});

test('all returns an empty array when there are no payments for the given merchant', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);

    expect($repository->all(MerchantId::generate()))->toBe([]);
});

test('findByInvoiceId returns the matching payment', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $invoiceId = InvoiceId::generate();
    $payment = makePayment(null, $invoiceId);
    $repository->save($payment);

    $found = $repository->findByInvoiceId($invoiceId);

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($payment->id()))->toBeTrue();
});

test('findByInvoiceId returns null when no payment matches', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);

    expect($repository->findByInvoiceId(InvoiceId::generate()))->toBeNull();
});
