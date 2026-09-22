<?php

use App\Application\Subscription\Commands\RenewSubscriptions\RenewSubscriptionsHandler;
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
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

function renewalCandidate(SubscriptionStatus $status, bool $pending, string $periodEnd): Subscription
{
    return Subscription::reconstitute(
        SubscriptionId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        PriceSnapshot::of(
            PriceId::generate(),
            ProductId::generate(),
            Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
        $status,
        new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        new DateTimeImmutable($periodEnd),
        $pending,
    );
}

test('handle queues one renewal for each due Active subscription only', function () {
    $repository = app(ISubscriptionRepositoryPort::class);
    $due = renewalCandidate(SubscriptionStatus::Active, false, '2026-10-01T00:00:00+00:00');
    $future = renewalCandidate(SubscriptionStatus::Active, false, '2026-11-01T00:00:00+00:00');
    $pending = renewalCandidate(SubscriptionStatus::Active, true, '2026-10-01T00:00:00+00:00');
    $pastDue = renewalCandidate(SubscriptionStatus::PastDue, false, '2026-10-01T00:00:00+00:00');
    foreach ([$due, $future, $pending, $pastDue] as $subscription) {
        $repository->save($subscription);
    }

    $count = app(RenewSubscriptionsHandler::class)->handle(new DateTimeImmutable('2026-10-15T00:00:00+00:00'));

    expect($count)->toBe(1);
    $persisted = $repository->get($due->id(), $due->merchantId());
    expect($persisted->renewalPending())->toBeTrue()
        ->and($persisted->currentPeriodStart()->format(DATE_ATOM))->toBe('2026-10-01T00:00:00+00:00')
        ->and($persisted->currentPeriodEnd()->format(DATE_ATOM))->toBe('2026-11-01T00:00:00+00:00');

    $renewals = collect(app(IOutboxPort::class)->unpublished())->where('eventType', 'subscription.renewal_due.v1');
    expect($renewals)->toHaveCount(1)
        ->and($renewals->first()->payload['subscription_id'])->toBe($due->id()->toString())
        ->and($renewals->first()->payload['period_start'])->toBe('2026-10-01T00:00:00+00:00');
});

test('handle cannot queue the same billing cycle twice', function () {
    $repository = app(ISubscriptionRepositoryPort::class);
    $due = renewalCandidate(SubscriptionStatus::Active, false, '2026-10-01T00:00:00+00:00');
    $repository->save($due);
    $handler = app(RenewSubscriptionsHandler::class);
    $asOf = new DateTimeImmutable('2026-10-15T00:00:00+00:00');

    expect($handler->handle($asOf))->toBe(1)
        ->and($handler->handle($asOf))->toBe(0)
        ->and(collect(app(IOutboxPort::class)->unpublished())->where('eventType', 'subscription.renewal_due.v1'))->toHaveCount(1);
});

test('handle respects the batch limit', function () {
    $repository = app(ISubscriptionRepositoryPort::class);
    $repository->save(renewalCandidate(SubscriptionStatus::Active, false, '2026-10-01T00:00:00+00:00'));
    $repository->save(renewalCandidate(SubscriptionStatus::Active, false, '2026-10-01T00:00:00+00:00'));

    expect(app(RenewSubscriptionsHandler::class)->handle(new DateTimeImmutable('2026-10-15T00:00:00+00:00'), 1))->toBe(1);
});
