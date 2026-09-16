<?php

use App\Domain\Subscription\ValueObjects\Currency;

test('it can be created from a known ISO 4217 code', function () {
    $currency = Currency::from('USD');

    expect($currency)->toBe(Currency::USD);
});

test('it throws when the code is unknown', function () {
    Currency::from('XXX');
})->throws(ValueError::class);
