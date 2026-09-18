<?php

use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Infrastructure\Payment\Adapters\Persistence\Mappers\PaymentMapper;
use App\Infrastructure\Payment\Adapters\Persistence\Repositories\EloquentPaymentRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('a request returns the matching payment with its attempts', function () {
    $merchantId = MerchantId::generate();
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), $merchantId, CustomerId::generate(), Money::of(1999, Currency::USD));
    $payment->startAttempt(PaymentAttemptId::generate(), 'fake', new DateTimeImmutable);
    (new EloquentPaymentRepository(new PaymentMapper))->save($payment);

    $response = $this->getJson("/api/v1/merchants/{$merchantId->toString()}/payments/{$payment->id()->toString()}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payment->id()->toString())
        ->assertJsonPath('data.status', 'processing')
        ->assertJsonPath('data.amount_minor_units', 1999)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonCount(1, 'data.attempts')
        ->assertJsonPath('data.attempts.0.provider', 'fake');
});

test('a request for a non-existent payment returns not found', function () {
    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString().'/payments/9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    $response->assertNotFound();
});

test('a request for a payment belonging to a different merchant returns not found', function () {
    $payment = Payment::create(PaymentId::generate(), InvoiceId::generate(), MerchantId::generate(), CustomerId::generate(), Money::of(1999, Currency::USD));
    (new EloquentPaymentRepository(new PaymentMapper))->save($payment);

    $response = $this->getJson('/api/v1/merchants/'.MerchantId::generate()->toString()."/payments/{$payment->id()->toString()}");

    $response->assertNotFound();
});
