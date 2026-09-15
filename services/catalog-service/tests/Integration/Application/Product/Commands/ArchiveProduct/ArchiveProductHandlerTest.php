<?php

use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\ArchiveProduct\ArchiveProductHandler;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductStatus;
use App\Shared\Application\Ports\Outbound\IOutboxPort;

test('handle archives an existing product', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(ArchiveProductHandler::class);

    $archived = $handler->handle(new ArchiveProductCommand($product->id()->toString()));

    expect($archived->status())->toBe(ProductStatus::Archived);
    $persisted = app(IProductRepositoryPort::class)->get($product->id());
    expect($persisted->status())->toBe(ProductStatus::Archived);
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(ArchiveProductHandler::class);

    $handler->handle(new ArchiveProductCommand(ProductId::generate()->toString()));
})->throws(ProductNotFound::class);

test('handle throws ProductAlreadyArchived when the product is already archived', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(ArchiveProductHandler::class);
    $handler->handle(new ArchiveProductCommand($product->id()->toString()));

    $handler->handle(new ArchiveProductCommand($product->id()->toString()));
})->throws(ProductAlreadyArchived::class);

test('handle records a ProductArchived integration event in the outbox', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(ArchiveProductHandler::class);

    $handler->handle(new ArchiveProductCommand($product->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $archived = collect($unpublished)->firstWhere('eventType', 'product.archived.v1');
    expect($archived)->not->toBeNull()
        ->and($archived->aggregateId)->toBe($product->id()->toString())
        ->and($archived->payload)->toBe(['product_id' => $product->id()->toString()]);
});
