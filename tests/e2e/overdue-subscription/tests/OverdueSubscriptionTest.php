<?php

use BillingPlatform\TestSupport\DockerCompose;
use Ramsey\Uuid\Uuid;
use Tests\Support\Services;

test('a failed renewal payment moves an active subscription to past due', function () {
    $registration = Services::identity()->post('/api/v1/auth/register', [
        'json' => [
            'email' => 'owner-overdue-'.Uuid::uuid4()->toString().'@example.com',
            'password' => 'correct horse battery staple',
            'merchant_name' => 'E2E Overdue Subscription Merchant',
        ],
    ]);
    expect($registration->getStatusCode())->toBe(201);
    $registrationBody = json_decode($registration->getBody()->getContents(), true);
    $merchantId = $registrationBody['merchant_id'];
    Services::authenticate($registrationBody['access_token']);

    $customer = Services::customer()->post("/api/v1/merchants/{$merchantId}/customers", [
        'json' => [
            'email' => 'e2e-overdue-'.Uuid::uuid4()->toString().'@example.com',
            'name' => 'E2E Overdue Subscription Customer',
        ],
    ]);
    expect($customer->getStatusCode())->toBe(201);
    $customerId = json_decode($customer->getBody()->getContents(), true)['data']['id'];

    $product = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products", [
        'json' => ['name' => 'E2E Renewal Decline Plan'],
    ]);
    expect($product->getStatusCode())->toBe(201);
    $productId = json_decode($product->getBody()->getContents(), true)['data']['id'];

    // This reserved fake-provider amount succeeds for subscription_create
    // and declines only subscription_cycle, so the first payment genuinely
    // activates the subscription before its renewal genuinely fails.
    $price = Services::catalog()->post("/api/v1/merchants/{$merchantId}/products/{$productId}/prices", [
        'json' => [
            'amount_minor_units' => 77770000,
            'currency' => 'USD',
            'type' => 2,
            'billing_interval' => 1, // day
            'billing_interval_count' => 1,
        ],
    ]);
    expect($price->getStatusCode())->toBe(201);
    $priceId = json_decode($price->getBody()->getContents(), true)['data']['id'];

    $subscription = Services::subscription()->post("/api/v1/merchants/{$merchantId}/subscriptions", [
        'json' => ['customer_id' => $customerId, 'price_id' => $priceId],
    ]);
    expect($subscription->getStatusCode())->toBe(201);
    $subscriptionId = json_decode($subscription->getBody()->getContents(), true)['data']['id'];

    $periodEnd = null;
    eventually(function () use ($merchantId, $subscriptionId, &$periodEnd): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect($response->getStatusCode())->toBe(200);
        $body = json_decode($response->getBody()->getContents(), true)['data'];
        expect($body['status'])->toBe('active');
        $periodEnd = $body['current_period_end'];
    }, timeoutSeconds: 20);

    $initialInvoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, &$initialInvoiceId): void {
        $response = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        $invoices = array_values(array_filter(
            json_decode($response->getBody()->getContents(), true)['data'],
            fn (array $invoice) => $invoice['subscription_id'] === $subscriptionId,
        ));
        expect($invoices)->toHaveCount(1)
            ->and($invoices[0]['status'])->toBe('paid');
        $initialInvoiceId = $invoices[0]['id'];
    }, timeoutSeconds: 20);

    $compose = new DockerCompose(dirname(__DIR__));
    $output = $compose->exec('subscription-api', [
        'php', 'artisan', 'subscriptions:renew', "--as-of={$periodEnd}",
    ]);
    expect($output)->toContain('Queued 1 subscription renewal(s).');

    $renewalInvoiceId = null;
    eventually(function () use ($merchantId, $subscriptionId, $initialInvoiceId, &$renewalInvoiceId): void {
        $response = Services::billing()->get("/api/v1/merchants/{$merchantId}/invoices");
        $invoices = array_values(array_filter(
            json_decode($response->getBody()->getContents(), true)['data'],
            fn (array $invoice) => $invoice['subscription_id'] === $subscriptionId,
        ));
        expect($invoices)->toHaveCount(2);
        $renewal = array_values(array_filter($invoices, fn (array $invoice) => $invoice['id'] !== $initialInvoiceId));
        expect($renewal)->toHaveCount(1)
            ->and($renewal[0]['status'])->toBe('open');
        $renewalInvoiceId = $renewal[0]['id'];
    }, timeoutSeconds: 20);

    eventually(function () use ($merchantId, $initialInvoiceId, $renewalInvoiceId): void {
        $response = Services::payment()->get("/api/v1/merchants/{$merchantId}/payments");
        $payments = json_decode($response->getBody()->getContents(), true)['data'];
        $initial = array_values(array_filter($payments, fn (array $payment) => $payment['invoice_id'] === $initialInvoiceId));
        $renewal = array_values(array_filter($payments, fn (array $payment) => $payment['invoice_id'] === $renewalInvoiceId));

        expect($initial)->toHaveCount(1)
            ->and($initial[0]['status'])->toBe('succeeded')
            ->and($initial[0]['billing_reason'])->toBe('subscription_create')
            ->and($renewal)->toHaveCount(1)
            ->and($renewal[0]['status'])->toBe('failed')
            ->and($renewal[0]['billing_reason'])->toBe('subscription_cycle')
            ->and($renewal[0]['attempts'][0]['failure_code'])->toBe('card_declined');
    }, timeoutSeconds: 20);

    eventually(function () use ($merchantId, $subscriptionId): void {
        $response = Services::subscription()->get("/api/v1/merchants/{$merchantId}/subscriptions/{$subscriptionId}");
        expect(json_decode($response->getBody()->getContents(), true)['data']['status'])->toBe('past_due');
    }, timeoutSeconds: 20);

    // PastDue is not eligible for another cycle, even if the scheduler
    // clock moves much farther forward.
    $output = $compose->exec('subscription-api', [
        'php', 'artisan', 'subscriptions:renew', '--as-of=2030-01-01T00:00:00+00:00',
    ]);
    expect($output)->toContain('Queued 0 subscription renewal(s).');
});
