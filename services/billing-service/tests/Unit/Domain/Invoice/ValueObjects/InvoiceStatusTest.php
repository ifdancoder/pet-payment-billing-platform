<?php

use App\Domain\Invoice\ValueObjects\InvoiceStatus;

test('it can be created from a known status', function () {
    $status = InvoiceStatus::from(1);

    expect($status)->toBe(InvoiceStatus::Open);
});

test('it throws when the status is unknown', function () {
    InvoiceStatus::from(99);
})->throws(ValueError::class);

test('every case has a readable label', function (InvoiceStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [InvoiceStatus::Open, 'open'],
    [InvoiceStatus::Paid, 'paid'],
    [InvoiceStatus::Void, 'void'],
]);
