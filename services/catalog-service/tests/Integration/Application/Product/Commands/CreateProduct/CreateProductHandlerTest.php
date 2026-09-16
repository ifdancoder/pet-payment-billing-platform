<?php

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle persists a new product with the given merchant, name and description', function () {
    $handler = app(CreateProductHandler::class);
    $merchantId = MerchantId::generate();

    $product = $handler->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan', 'Pro tier subscription'));

    $persisted = app(IProductRepositoryPort::class)->get($product->id(), $merchantId);
    expect($persisted->name()->toString())->toBe('Pro Plan')
        ->and($persisted->merchantId()->equals($merchantId))->toBeTrue()
        ->and($persisted->description())->toBe('Pro tier subscription');
});

test('handle records a ProductCreated integration event in the outbox', function () {
    $handler = app(CreateProductHandler::class);

    $product = $handler->handle(new CreateProductCommand(MerchantId::generate()->toString(), 'Pro Plan'));

    $unpublished = app(IOutboxPort::class)->unpublished();
    expect($unpublished)->toHaveCount(1)
        ->and($unpublished[0]->eventType)->toBe('product.created.v1')
        ->and($unpublished[0]->aggregateId)->toBe($product->id()->toString())
        ->and($unpublished[0]->payload)->toBe([
            'product_id' => $product->id()->toString(),
            'name' => 'Pro Plan',
        ]);
});
