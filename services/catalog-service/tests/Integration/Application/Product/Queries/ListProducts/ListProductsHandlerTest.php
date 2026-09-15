<?php

use App\Application\Product\Queries\ListProducts\ListProductsHandler;
use App\Application\Product\Queries\ListProducts\ListProductsQuery;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Infrastructure\Product\Adapters\Persistence\Mappers\ProductMapper;
use App\Infrastructure\Product\Adapters\Persistence\Repositories\EloquentProductRepository;

test('handle returns every persisted product', function () {
    $repository = new EloquentProductRepository(new ProductMapper);
    $repository->save(Product::create(ProductId::generate(), ProductName::fromString('Pro Plan')));
    $repository->save(Product::create(ProductId::generate(), ProductName::fromString('Team Plan')));
    $handler = new ListProductsHandler($repository);

    $products = $handler->handle(new ListProductsQuery);

    expect($products)->toHaveCount(2);
});

test('handle returns an empty array when there are no products', function () {
    $handler = new ListProductsHandler(new EloquentProductRepository(new ProductMapper));

    expect($handler->handle(new ListProductsQuery))->toBe([]);
});
