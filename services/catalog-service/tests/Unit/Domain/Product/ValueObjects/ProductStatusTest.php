<?php

use App\Domain\Product\ValueObjects\ProductStatus;

test('it can be created from a known status', function () {
    $status = ProductStatus::from(1);

    expect($status)->toBe(ProductStatus::Active);
});

test('it throws when the status is unknown', function () {
    ProductStatus::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (ProductStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [ProductStatus::Active, 'active'],
    [ProductStatus::Archived, 'archived'],
]);
