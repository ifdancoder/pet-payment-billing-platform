<?php

use App\Domain\Price\ValueObjects\PriceStatus;

test('it can be created from a known status', function () {
    $status = PriceStatus::from(1);

    expect($status)->toBe(PriceStatus::Active);
});

test('it throws when the status is unknown', function () {
    PriceStatus::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (PriceStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [PriceStatus::Active, 'active'],
    [PriceStatus::Inactive, 'inactive'],
]);
