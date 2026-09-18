<?php

use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Infrastructure\Payment\Adapters\Persistence\Mappers\PaymentMapper;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function seedAPayment(MerchantId $merchantId): Payment
{
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), $merchantId, CustomerId::generate(), Money::of(1999, Currency::USD));
    (new EloquentPaymentRepository(new PaymentMapper))->save($payment);

    return $payment;
}

test('a request returns every existing payment for the given merchant', function () {
    $merchantId = MerchantId::generate();
    seedAPayment($merchantId);
    seedAPayment($merchantId);
    seedAPayment(MerchantId::generate());

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/payments");

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('a request returns an empty list when there are no payments for the given merchant', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/payments');

    $response->assertOk()->assertJsonCount(0, 'data');
});
