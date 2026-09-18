<?php

use App\Application\Payment\Queries\ListPayments\ListPaymentsHandler;
use App\Application\Payment\Queries\ListPayments\ListPaymentsQuery;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Infrastructure\Payment\Adapters\Persistence\Mappers\PaymentMapper;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function aPaymentFor(MerchantId $merchantId): Payment
{
    return Payment::create(PaymentId::generate(), InvoiceId::generate(), $merchantId, CustomerId::generate(), Money::of(1999, Currency::USD));
}

test('handle returns every persisted payment for the given merchant', function () {
    $repository = new EloquentPaymentRepository(new PaymentMapper);
    $merchantId = MerchantId::generate();
    $repository->save(aPaymentFor($merchantId));
    $repository->save(aPaymentFor($merchantId));
    $repository->save(aPaymentFor(MerchantId::generate()));
    $handler = new ListPaymentsHandler($repository);

    $payments = $handler->handle(new ListPaymentsQuery($merchantId->toString()));

    expect($payments)->toHaveCount(2);
});

test('handle returns an empty array when there are no payments for the given merchant', function () {
    $handler = new ListPaymentsHandler(new EloquentPaymentRepository(new PaymentMapper));

    expect($handler->handle(new ListPaymentsQuery(MerchantId::generate()->toString())))->toBe([]);
});
