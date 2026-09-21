<?php

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Infrastructure\Notification\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function aPaymentSucceededPayload(array $overrides = []): array
{
    return array_merge([
        'payment_id' => (string) Str::uuid(),
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => MerchantId::generate()->toString(),
        'customer_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'paid_at' => '2026-09-01T00:05:00+00:00',
    ], $overrides);
}

function fakeCustomerServiceContact(string $customerId, ?string $email = 'jane@example.com'): void
{
    Http::fake([
        "*/api/v1/customers/{$customerId}" => $email === null
            ? Http::response(['message' => 'not found'], 404)
            : Http::response(['data' => ['id' => $customerId, 'email' => $email, 'name' => 'Jane Doe']], 200),
    ]);
}

test('handle creates a Pending notification with rendered email content', function () {
    $payload = aPaymentSucceededPayload();
    fakeCustomerServiceContact($payload['customer_id']);

    app(PaymentSucceededConsumer::class)->handle((string) Str::uuid(), $payload);

    $notifications = app(INotificationRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]->status())->toBe(NotificationStatus::Pending)
        ->and($notifications[0]->recipient()->toString())->toBe('jane@example.com')
        ->and($notifications[0]->subject())->toBe('Your payment receipt (19.99 USD)')
        ->and($notifications[0]->bodyText())->toContain($payload['payment_id']);
});

test('handle does nothing when the customer has no contact details', function () {
    $payload = aPaymentSucceededPayload();
    fakeCustomerServiceContact($payload['customer_id'], null);

    app(PaymentSucceededConsumer::class)->handle((string) Str::uuid(), $payload);

    $notifications = app(INotificationRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($notifications)->toBe([]);
});

test('handle does nothing when the same event id is redelivered', function () {
    $payload = aPaymentSucceededPayload();
    fakeCustomerServiceContact($payload['customer_id']);
    $eventId = (string) Str::uuid();
    app(PaymentSucceededConsumer::class)->handle($eventId, $payload);

    app(PaymentSucceededConsumer::class)->handle($eventId, $payload);

    $notifications = app(INotificationRepositoryPort::class)->all(MerchantId::fromString($payload['merchant_id']));
    expect($notifications)->toHaveCount(1);
});
