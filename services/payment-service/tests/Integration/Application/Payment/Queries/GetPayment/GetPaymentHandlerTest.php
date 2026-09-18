<?php

use App\Application\Payment\Queries\GetPayment\GetPaymentHandler;
use App\Application\Payment\Queries\GetPayment\GetPaymentQuery;
use App\Domain\Payment\Exceptions\PaymentNotFound;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Infrastructure\Payment\Adapters\Persistence\Mappers\PaymentMapper;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle returns the matching payment', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $merchantId = MerchantId::generate();
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), $merchantId, CustomerId::generate(), Money::of(1999, Currency::USD));
    $repository->save($payment);
    $handler = new GetPaymentHandler($repository);

    $found = $handler->handle(new GetPaymentQuery($merchantId->toString(), $payment->id()->toString()));

    expect($found->id()->equals($payment->id()))->toBeTrue();
});

test('handle throws PaymentNotFound when no payment matches', function () {
    $handler = new GetPaymentHandler(new EloquentPaymentRepository(new PaymentMapper));

    $handler->handle(new GetPaymentQuery(MerchantId::generate()->toString(), PaymentId::generate()->toString()));
})->throws(PaymentNotFound::class);
