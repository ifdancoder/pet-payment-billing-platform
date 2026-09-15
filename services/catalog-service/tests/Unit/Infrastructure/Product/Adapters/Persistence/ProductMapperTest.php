<?php

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductStatus;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;

test('toDomain builds a Product matching the model attributes', function () {
    $model = new ProductModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'name' => 'Pro Plan',
        'status' => ProductStatus::Archived->value,
    ]);

    $product = (new ProductMapper)->toDomain($model);

    expect($product->id()->toString())->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($product->name()->toString())->toBe('Pro Plan')
        ->and($product->status())->toBe(ProductStatus::Archived);
});

test('toDomain does not record a ProductCreated event', function () {
    $model = new ProductModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'name' => 'Pro Plan',
        'status' => ProductStatus::Active->value,
    ]);

    $product = (new ProductMapper)->toDomain($model);

    expect($product->pullRecordedEvents())->toBe([]);
});

test('toModel fills a new model from a Product', function () {
    $product = Product::create(
        ProductId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        ProductName::fromString('Pro Plan'),
    );

    $model = (new ProductMapper)->toModel($product);

    expect($model->id)->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($model->name)->toBe('Pro Plan')
        ->and($model->status)->toBe(ProductStatus::Active->value);
});

test('toModel fills an existing model instance in place instead of creating a new one', function () {
    $existing = new ProductModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'name' => 'Old Name',
        'status' => ProductStatus::Active->value,
    ]);
    $product = Product::create(
        ProductId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        ProductName::fromString('New Name'),
    );
    $product->archive();

    $model = (new ProductMapper)->toModel($product, $existing);

    expect($model)->toBe($existing)
        ->and($model->name)->toBe('New Name')
        ->and($model->status)->toBe(ProductStatus::Archived->value);
});
