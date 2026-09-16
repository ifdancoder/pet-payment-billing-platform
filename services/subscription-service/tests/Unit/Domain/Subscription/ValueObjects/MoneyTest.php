<?php

use App\Domain\Subscription\Exceptions\InvalidMoney;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\Money;

test('it exposes the amount in minor units and the currency', function () {
    $money = Money::of(1999, Currency::USD);

    expect($money->amountMinorUnits())->toBe(1999)
        ->and($money->currency())->toBe(Currency::USD);
});

test('it allows a zero amount', function () {
    $money = Money::of(0, Currency::USD);

    expect($money->amountMinorUnits())->toBe(0);
});

test('it throws when the amount is negative', function () {
    Money::of(-1, Currency::USD);
})->throws(InvalidMoney::class, '-1 is not a valid amount: it must not be negative.');

test('two amounts of the same currency and value are equal', function () {
    $a = Money::of(1999, Currency::USD);
    $b = Money::of(1999, Currency::USD);

    expect($a->equals($b))->toBeTrue();
});

test('two amounts of different currencies are not equal', function () {
    $a = Money::of(1999, Currency::USD);
    $b = Money::of(1999, Currency::EUR);

    expect($a->equals($b))->toBeFalse();
});

test('two amounts of different values are not equal', function () {
    $a = Money::of(1999, Currency::USD);
    $b = Money::of(2999, Currency::USD);

    expect($a->equals($b))->toBeFalse();
});
