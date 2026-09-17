<?php

use App\Domain\Invoice\Exceptions\InvalidInvoiceLineId;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;

test('it generates a valid unique id', function () {
    $a = InvoiceLineId::generate();
    $b = InvoiceLineId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = InvoiceLineId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    InvoiceLineId::fromString('not-a-uuid');
})->throws(InvalidInvoiceLineId::class, '"not-a-uuid" is not a valid invoice line id.');

test('two ids with the same value are equal', function () {
    $id = InvoiceLineId::generate();

    expect($id->equals(InvoiceLineId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = InvoiceLineId::generate();

    expect((string) $id)->toBe($id->toString());
});
