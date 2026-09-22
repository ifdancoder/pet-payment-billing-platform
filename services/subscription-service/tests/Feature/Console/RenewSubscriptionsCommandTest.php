<?php

use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\BillingInterval;
use App\Domain\Subscription\ValueObjects\BillingPeriod;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\Money;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\ProductId;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Shared\Domain\ValueObjects\MerchantId;

test('subscriptions:renew accepts an operational as-of time and queues due work', function () {
    $subscription = Subscription::reconstitute(
        SubscriptionId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        PriceSnapshot::of(
            PriceId::generate(), ProductId::generate(), Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
        SubscriptionStatus::Active,
        new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        new DateTimeImmutable('2026-10-01T00:00:00+00:00'),
        false,
    );
    app(ISubscriptionRepositoryPort::class)->save($subscription);

    $this->artisan('subscriptions:renew --as-of=2026-10-01T00:00:00+00:00')
        ->expectsOutput('Queued 1 subscription renewal(s).')
        ->assertSuccessful();
});

test('subscriptions:renew rejects invalid options', function () {
    $this->artisan('subscriptions:renew --as-of=not-a-date')
        ->expectsOutput('--as-of must be a valid date/time.')
        ->assertFailed();

    $this->artisan('subscriptions:renew --limit=0')
        ->expectsOutput('--limit must be an integer between 1 and 1000.')
        ->assertFailed();
});
