<?php

use App\Domain\Price\ValueObjects\BillingInterval;

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
