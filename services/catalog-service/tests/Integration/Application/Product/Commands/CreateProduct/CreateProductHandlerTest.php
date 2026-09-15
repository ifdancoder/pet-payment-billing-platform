<?php

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;

test('handle persists a new product with the given name', function () {
    $handler = app(CreateProductHandler::class);

    $product = $handler->handle(new CreateProductCommand('Pro Plan'));

    $persisted = app(IProductRepositoryPort::class)->get($product->id());
    expect($persisted->name()->toString())->toBe('Pro Plan');
});

test('handle records a ProductCreated integration event in the outbox', function () {
    $handler = app(CreateProductHandler::class);

    $product = $handler->handle(new CreateProductCommand('Pro Plan'));

    $unpublished = app(IOutboxPort::class)->unpublished();
    expect($unpublished)->toHaveCount(1)
        ->and($unpublished[0]->eventType)->toBe('product.created.v1')
        ->and($unpublished[0]->aggregateId)->toBe($product->id()->toString())
        ->and($unpublished[0]->payload)->toBe([
            'product_id' => $product->id()->toString(),
            'name' => 'Pro Plan',
        ]);
});
