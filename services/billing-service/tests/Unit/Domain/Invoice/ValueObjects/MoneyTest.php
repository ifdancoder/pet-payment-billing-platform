<?php

use App\Domain\Invoice\Exceptions\CurrencyMismatch;
use App\Domain\Invoice\Exceptions\InvalidMoney;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\Money;

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

test('add sums two amounts of the same currency', function () {
    $a = Money::of(2000, Currency::USD);
    $b = Money::of(1500, Currency::USD);

    expect($a->add($b)->amountMinorUnits())->toBe(3500);
});

test('add throws when the currencies differ', function () {
    $a = Money::of(2000, Currency::USD);
    $b = Money::of(1500, Currency::EUR);

    $a->add($b);
})->throws(CurrencyMismatch::class, 'Cannot combine USD with EUR.');

test('multiply scales the amount by the given factor', function () {
    $money = Money::of(2000, Currency::USD);

    expect($money->multiply(3)->amountMinorUnits())->toBe(6000);
});

test('multiply by zero results in a zero amount', function () {
    $money = Money::of(2000, Currency::USD);

    expect($money->multiply(0)->amountMinorUnits())->toBe(0);
});

test('multiply throws when the factor is negative', function () {
    $money = Money::of(2000, Currency::USD);

    $money->multiply(-1);
})->throws(InvalidMoney::class, '-2000 is not a valid amount: it must not be negative.');
