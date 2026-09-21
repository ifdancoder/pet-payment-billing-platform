<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\EventPublisher;
use Tests\Support\Services;

test('billing opening an invoice eventually produces a succeeded payment', function () {
    $merchantId = Uuid::uuid4()->toString();
    $customerId = Uuid::uuid4()->toString();
    $subscriptionId = Uuid::uuid4()->toString();

    EventPublisher::publishSubscriptionCreated([
        'subscription_id' => $subscriptionId,
        'merchant_id' => $merchantId,
        'customer_id' => $customerId,
        'price_id' => Uuid::uuid4()->toString(),
        'product_id' => Uuid::uuid4()->toString(),
        'amount_minor_units' => 2999,
        'currency' => 'USD',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);

    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        expect($invoice[0]['status'])->toBe('open');
        expect($invoice[0]['total_amount_minor_units'])->toBe(2999);

        $invoiceId = $invoice[0]['id'];
    });

    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        expect($payments->getStatusCode())->toBe(200);

        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('succeeded');
        expect($payment[0]['amount_minor_units'])->toBe(2999);
        expect($payment[0]['currency'])->toBe('USD');
    });
});
