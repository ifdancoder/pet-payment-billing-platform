<?php

use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductStatus;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Models\ProductModel;
use App\Shared\Domain\ValueObjects\MerchantId;

test('toDomain builds a Product matching the model attributes', function () {
    $merchantId = MerchantId::generate();
    $model = new ProductModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => $merchantId->toString(),
        'name' => 'Pro Plan',
        'description' => 'Pro tier subscription',
        'status' => ProductStatus::Archived->value,
    ]);

    $product = (new ProductMapper)->toDomain($model);

    expect($product->id()->toString())->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($product->merchantId()->equals($merchantId))->toBeTrue()
        ->and($product->name()->toString())->toBe('Pro Plan')
        ->and($product->description())->toBe('Pro tier subscription')
        ->and($product->status())->toBe(ProductStatus::Archived);
});

test('toDomain does not record a ProductCreated event', function () {
    $model = new ProductModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => MerchantId::generate()->toString(),
        'name' => 'Pro Plan',
        'status' => ProductStatus::Active->value,
    ]);

    $product = (new ProductMapper)->toDomain($model);

    expect($product->pullRecordedEvents())->toBe([]);
});

test('toModel fills a new model from a Product', function () {
    $merchantId = MerchantId::generate();
    $product = Product::create(
        ProductId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        $merchantId,
        ProductName::fromString('Pro Plan'),
        'Pro tier subscription',
    );

    $model = (new ProductMapper)->toModel($product);

    expect($model->id)->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($model->merchant_id)->toBe($merchantId->toString())
        ->and($model->name)->toBe('Pro Plan')
        ->and($model->description)->toBe('Pro tier subscription')
        ->and($model->status)->toBe(ProductStatus::Active->value);
});

test('toModel fills an existing model instance in place instead of creating a new one', function () {
    $merchantId = MerchantId::generate();
    $existing = new ProductModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => $merchantId->toString(),
        'name' => 'Old Name',
        'status' => ProductStatus::Active->value,
    ]);
    $product = Product::create(
        ProductId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        $merchantId,
        ProductName::fromString('New Name'),
    );
    $product->archive();

    $model = (new ProductMapper)->toModel($product, $existing);

    expect($model)->toBe($existing)
        ->and($model->name)->toBe('New Name')
        ->and($model->status)->toBe(ProductStatus::Archived->value);
});
