<?php

use App\Domain\Price\ValueObjects\BillingInterval;

test('it can be created from a known interval', function () {
    $interval = BillingInterval::from('monthly');

    expect($interval)->toBe(BillingInterval::Monthly);
});

test('it throws when the interval is unknown', function () {
    BillingInterval::from('fortnightly');
})->throws(ValueError::class);
