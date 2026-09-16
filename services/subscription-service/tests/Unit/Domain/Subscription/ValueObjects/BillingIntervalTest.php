<?php

use App\Domain\Subscription\ValueObjects\BillingInterval;

test('it can be created from a known interval', function () {
    $interval = BillingInterval::from(3);

    expect($interval)->toBe(BillingInterval::Month);
});

test('it throws when the interval is unknown', function () {
    BillingInterval::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (BillingInterval $interval, string $label) {
    expect($interval->label())->toBe($label);
})->with([
    [BillingInterval::Day, 'day'],
    [BillingInterval::Week, 'week'],
    [BillingInterval::Month, 'month'],
    [BillingInterval::Year, 'year'],
]);

test('it can be created from a known label', function (string $label, BillingInterval $interval) {
    expect(BillingInterval::fromLabel($label))->toBe($interval);
})->with([
    ['day', BillingInterval::Day],
    ['week', BillingInterval::Week],
    ['month', BillingInterval::Month],
    ['year', BillingInterval::Year],
]);

test('it throws when the label is unknown', function () {
    BillingInterval::fromLabel('fortnight');
})->throws(ValueError::class, '"fortnight" is not a valid billing interval label.');
