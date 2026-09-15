<?php

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;

test('handle persists a new product with the given name', function () {
    $handler = app(CreateProductHandler::class);

    $product = $handler->handle(new CreateProductCommand('Pro Plan'));

    $persisted = app(IProductRepositoryPort::class)->get($product->id());
    expect($persisted->name()->toString())->toBe('Pro Plan');
});
