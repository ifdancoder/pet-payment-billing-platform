<?php

use App\Domain\Product\Events\ProductArchived;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductStatus;

test('create exposes the given id and name, active by default', function () {
    $id = ProductId::generate();
    $name = ProductName::fromString('Pro Plan');

    $product = Product::create($id, $name);

    expect($product->id()->equals($id))->toBeTrue()
        ->and($product->name()->equals($name))->toBeTrue()
        ->and($product->status())->toBe(ProductStatus::Active);
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

test('reconstitute exposes the given id, name and status without recording an event', function () {
    $id = ProductId::generate();
    $name = ProductName::fromString('Pro Plan');

    $product = Product::reconstitute($id, $name, ProductStatus::Archived);

    expect($product->id()->equals($id))->toBeTrue()
        ->and($product->name()->equals($name))->toBeTrue()
        ->and($product->status())->toBe(ProductStatus::Archived)
        ->and($product->pullRecordedEvents())->toBe([]);
});

test('archive sets the status to Archived and records a ProductArchived event', function () {
    $product = Product::create(ProductId::generate(), ProductName::fromString('Pro Plan'));
    $product->pullRecordedEvents();

    $product->archive();

    expect($product->status())->toBe(ProductStatus::Archived);
    $events = $product->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ProductArchived::class)
        ->and($events[0]->productId->equals($product->id()))->toBeTrue();
});

test('archive throws when the product is already archived', function () {
    $product = Product::reconstitute(
        ProductId::generate(),
        ProductName::fromString('Pro Plan'),
        ProductStatus::Archived,
    );

    $product->archive();
})->throws(ProductAlreadyArchived::class);
