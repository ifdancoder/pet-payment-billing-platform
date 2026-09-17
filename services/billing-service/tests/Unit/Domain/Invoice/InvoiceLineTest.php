<?php

use App\Domain\Invoice\Exceptions\InvalidInvoiceLine;
use App\Domain\Invoice\InvoiceLine;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PriceId;
use App\Domain\Invoice\ValueObjects\ProductId;

test('create computes the total as unit amount times quantity', function () {
    $id = InvoiceLineId::generate();
    $productId = ProductId::generate();
    $priceId = PriceId::generate();
    $unitAmount = Money::of(2000, Currency::USD);

    $line = InvoiceLine::create($id, $productId, $priceId, 'Pro Plan', $unitAmount, 3);

    expect($line->id()->equals($id))->toBeTrue()
        ->and($line->productId()->equals($productId))->toBeTrue()
        ->and($line->priceId()->equals($priceId))->toBeTrue()
        ->and($line->description())->toBe('Pro Plan')
        ->and($line->unitAmount()->equals($unitAmount))->toBeTrue()
        ->and($line->quantity())->toBe(3)
        ->and($line->total()->amountMinorUnits())->toBe(6000);
});

test('create allows a null product and price reference', function () {
    $line = InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Ad-hoc charge', Money::of(500, Currency::USD), 1);

    expect($line->productId())->toBeNull()
        ->and($line->priceId())->toBeNull();
});

test('create throws when the quantity is less than one', function () {
    InvoiceLine::create(InvoiceLineId::generate(), null, null, 'Pro Plan', Money::of(2000, Currency::USD), 0);
})->throws(InvalidInvoiceLine::class, '0 is not a valid invoice line quantity: it must be at least 1.');

test('two lines with the same data are equal', function () {
    $id = InvoiceLineId::generate();
    $unitAmount = Money::of(2000, Currency::USD);

    $a = InvoiceLine::create($id, null, null, 'Pro Plan', $unitAmount, 2);
    $b = InvoiceLine::create($id, null, null, 'Pro Plan', $unitAmount, 2);

    expect($a->equals($b))->toBeTrue();
});

test('two lines with different data are not equal', function () {
    $id = InvoiceLineId::generate();
    $unitAmount = Money::of(2000, Currency::USD);

    $a = InvoiceLine::create($id, null, null, 'Pro Plan', $unitAmount, 2);
    $b = InvoiceLine::create($id, null, null, 'Pro Plan', $unitAmount, 3);

    expect($a->equals($b))->toBeFalse();
});
