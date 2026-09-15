<?php

use App\Domain\Price\Exceptions\InvalidBillingPeriod;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;

test('it exposes the interval and count', function () {
    $period = BillingPeriod::of(BillingInterval::Month, 3);

    expect($period->interval())->toBe(BillingInterval::Month)
        ->and($period->count())->toBe(3);
});

test('it throws when the count is less than one', function () {
    BillingPeriod::of(BillingInterval::Month, 0);
})->throws(InvalidBillingPeriod::class, '0 is not a valid billing period count: it must be at least 1.');

test('two periods with the same interval and count are equal', function () {
    $a = BillingPeriod::of(BillingInterval::Month, 3);
    $b = BillingPeriod::of(BillingInterval::Month, 3);

    expect($a->equals($b))->toBeTrue();
});

test('two periods with a different interval or count are not equal', function () {
    $monthly = BillingPeriod::of(BillingInterval::Month, 1);
    $quarterly = BillingPeriod::of(BillingInterval::Month, 3);
    $yearly = BillingPeriod::of(BillingInterval::Year, 1);

    expect($monthly->equals($quarterly))->toBeFalse()
        ->and($monthly->equals($yearly))->toBeFalse();
});
