<?php

use App\Application\Product\Queries\ListProducts\ListProductsHandler;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle returns every persisted product for the given merchant', function () {
    $repository = new EloquentProductRepository(new ProductMapper);
    $merchantId = MerchantId::generate();
    $repository->save(Product::create(ProductId::generate(), $merchantId, ProductName::fromString('Pro Plan')));
    $repository->save(Product::create(ProductId::generate(), $merchantId, ProductName::fromString('Team Plan')));
    $repository->save(Product::create(ProductId::generate(), MerchantId::generate(), ProductName::fromString('Other Merchant Plan')));
    $handler = new ListProductsHandler($repository);

    $products = $handler->handle(new ListProductsQuery($merchantId->toString()));

    expect($products)->toHaveCount(2);
});

test('handle returns an empty array when there are no products for the given merchant', function () {
    $handler = new ListProductsHandler(new EloquentProductRepository(new ProductMapper));

    expect($handler->handle(new ListProductsQuery(MerchantId::generate()->toString())))->toBe([]);
});
