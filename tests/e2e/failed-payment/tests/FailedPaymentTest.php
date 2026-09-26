<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

/**
 * The platform's second full end-to-end scenario, and its first
 * failure-path one: a customer purchases a subscription, but the
 * charge is declined. Same seven real services, same real HTTP-only
 * chain as tests/e2e/successful-subscription/ — the only difference is
 * the Price's amount, set to FakePaymentGateway::DECLINE_TRIGGER_AMOUNT_MINOR_UNITS
 * (66660000, any currency), the one deterministic failure signal that
 * survives unmodified all the way from this test's own
 * `POST .../prices` call down to the gateway (see that class's own
 * comment in services/payment-service for why it's the amount, not a
 * card/token field, that carries this). See
 * docs/architecture/testing-strategy.md, "Fake providers".
 *
 * Proves the platform leaves a declined charge exactly where a real
 * decline should: the Invoice stays Open (never Paid), the Payment
 * itself is Failed with a provider failure code, and the
 * Subscription — which never got to Active in the first place — stays
 * Pending rather than being forced into PastDue (see
 * HandleInvoicePaymentFailedHandler's own guard, already covered on
 * its own by tests/integration/billing-to-subscription/). Nothing here
 * is direct-published; every event on the wire, including
 * payment.failed.v1 and invoice.payment_failed.v1, is produced by the
 * actual service whose job that is.
 *
 * Run: docker compose up -d --build; composer install; composer test
 */
test('a declined charge leaves the invoice open, the payment failed, and the subscription pending', function () {
    $registration = Services::identity()->post('/api/v1/auth/register', [
        'json' => [
            'email' => 'owner-failed-'.Uuid::uuid4()->toString().'@example.com',
            'password' => 'correct horse battery staple',
            'merchant_name' => 'E2E Failed Payment Merchant',
        ],
    ]);
    expect($registration->getStatusCode())->toBe(201);
    $registrationBody = json_decode($registration->getBody()->getContents(), true);
    $merchantId = $registrationBody['merchant_id'];
    Services::authenticate($registrationBody['access_token']);

    $customerEmail = 'e2e-failed-payment-'.Uuid::uuid4()->toString().'@example.com';
    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => $customerEmail,
            'name' => 'E2E Failed Payment Customer',
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'E2E Failed Payment Plan'],
    ]);
    expect($product->getStatusCode())->toBe(201);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    // The decline-trigger amount, not a realistic price — the whole
    // point of this scenario is a charge FakePaymentGateway is
    // guaranteed to decline.
    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 66660000,
            'currency' => 'USD',
            'type' => 2, // recurring
            'billing_interval' => 3, // month
            'billing_interval_count' => 1,
        ],
    ]);
    expect($price->getStatusCode())->toBe(201);
    $priceId = json_decode($price->getBody()->getContents(), true)['data']['id'];

    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionBody = json_decode($subscription->getBody()->getContents(), true)['data'];
    expect($subscriptionBody['status'])->toBe('pending');
    $subscriptionId = $subscriptionBody['id'];

    // subscription.created.v1 -> Billing opens an Invoice ->
    // invoice.created.v1 -> Payment processes it against
    // FakePaymentGateway, which declines this exact amount. The
    // Invoice itself is unaffected by a failed payment — it's created
    // Open and MarkInvoicePaidHandler is simply never reached.
    $invoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$invoiceId): void {
        $invoices = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        expect($invoices->getStatusCode())->toBe(200);

        $body = json_decode($invoices->getBody()->getContents(), true)['data'];
        $invoice = array_values(array_filter($body, fn (array $i) => $i['subscription_id'] === $subscriptionId));

        expect($invoice)->toHaveCount(1);
        $invoiceId = $invoice[0]['id'];
    }, timeoutSeconds: 15);

    eventually(function () use ($merchantId, $invoiceId): void {
        $payments = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        expect($payments->getStatusCode())->toBe(200);

        $body = json_decode($payments->getBody()->getContents(), true)['data'];
        $payment = array_values(array_filter($body, fn (array $p) => $p['invoice_id'] === $invoiceId));

        expect($payment)->toHaveCount(1);
        expect($payment[0]['status'])->toBe('failed');
        expect($payment[0]['attempts'])->toHaveCount(1);
        expect($payment[0]['attempts'][0]['failure_code'])->toBe('card_declined');
    }, timeoutSeconds: 15);

    // Everything downstream of the Payment turning Failed is proving an
    // absence — the Invoice not becoming Paid, the Subscription not
    // becoming Active or PastDue — and eventually() is built to wait
    // for a condition to become true, not to prove one stays false.
    // Same deliberate sleep()-over-eventually() exception documented in
    // tests/integration/payment-to-notification/ and
    // tests/resilience/consumer-crash/: this gives payment.failed.v1
    // real time to reach billing-consumer (which relays
    // invoice.payment_failed.v1 without touching the Invoice) and
    // subscription-consumer (whose own guard leaves a Pending
    // subscription right where it was) — several real worker loop
    // iterations, not a race against the assertion above.
    sleep(5);

    $invoice = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices/{$invoiceId}");
    expect($invoice->getStatusCode())->toBe(200);
    expect(json_decode($invoice->getBody()->getContents(), true)['data']['status'])->toBe('open');

    $subscription = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
    expect($subscription->getStatusCode())->toBe(200);
    expect(json_decode($subscription->getBody()->getContents(), true)['data']['status'])->toBe('pending');

    // Notification only ever consumes payment.succeeded.v1 — a declined
    // charge never produces one, so nothing should exist for this
    // customer at all, not even a Failed/undelivered record.
    $notifications = Services::notification()->get("/api/v1/merchants/{$merchantId}/notifications");
    expect($notifications->getStatusCode())->toBe(200);
    $body = json_decode($notifications->getBody()->getContents(), true)['data'];
    $receipt = array_values(array_filter($body, fn (array $n) => $n['recipient'] === $customerEmail));
    expect($receipt)->toBeEmpty();
});
