<?php

use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;

test('create exposes the given id and name', function () {
    $id = ProductId::generate();
    $name = ProductName::fromString('Pro Plan');

    $product = Product::create($id, $name);

    expect($product->id()->equals($id))->toBeTrue()
        ->and($product->name()->equals($name))->toBeTrue();
});

test('create records a ProductCreated event carrying the same data', function () {
    $id = ProductId::generate();
    $name = ProductName::fromString('Pro Plan');

    $product = Product::create($id, $name);
    $events = $product->pullRecordedEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(ProductCreated::class);
    expect($events[0]->productId->equals($id))->toBeTrue();
    expect($events[0]->name->equals($name))->toBeTrue();
});

test('pullRecordedEvents empties the recorded events', function () {
    $product = Product::create(ProductId::generate(), ProductName::fromString('Pro Plan'));

    $product->pullRecordedEvents();

    expect($product->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given id and name without recording an event', function () {
    $id = ProductId::generate();
    $name = ProductName::fromString('Pro Plan');

    $product = Product::reconstitute($id, $name);

    expect($product->id()->equals($id))->toBeTrue()
        ->and($product->name()->equals($name))->toBeTrue()
        ->and($product->pullRecordedEvents())->toBe([]);
});
