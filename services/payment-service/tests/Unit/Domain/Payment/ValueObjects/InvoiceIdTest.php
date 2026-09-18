<?php

use App\Domain\Payment\Exceptions\InvalidInvoiceId;
use App\Domain\Payment\ValueObjects\InvoiceId;

test('it generates a valid unique id', function () {
    $a = InvoiceId::generate();
    $b = InvoiceId::generate();

    expect($a->equals($b))->toBeFalse();
});

test('it can be created from a valid uuid string', function () {
    $id = InvoiceId::fromString('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');

    expect($id->toString())->toBe('9e3b1f2a-1c2d-4e3f-8a9b-0123456789ab');
});

test('it throws when the string is not a valid uuid', function () {
    InvoiceId::fromString('not-a-uuid');
})->throws(InvalidInvoiceId::class, '"not-a-uuid" is not a valid invoice id.');

test('two ids with the same value are equal', function () {
    $id = InvoiceId::generate();

    expect($id->equals(InvoiceId::fromString($id->toString())))->toBeTrue();
});

test('it can be cast to a string', function () {
    $id = InvoiceId::generate();

    expect((string) $id)->toBe($id->toString());
});
