<?php

use App\Domain\Price\ValueObjects\PriceType;

test('it can be created from a known type', function () {
    $type = PriceType::from(1);

    expect($type)->toBe(PriceType::OneTime);
});

test('it throws when the type is unknown', function () {
    PriceType::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (PriceType $type, string $label) {
    expect($type->label())->toBe($label);
})->with([
    [PriceType::OneTime, 'one_time'],
    [PriceType::Recurring, 'recurring'],
]);
