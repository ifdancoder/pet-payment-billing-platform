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
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle archives an existing product', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(ArchiveProductHandler::class);

    $archived = $handler->handle(new ArchiveProductCommand($merchantId->toString(), $product->id()->toString()));

    expect($archived->status())->toBe(ProductStatus::Archived);
    $persisted = app(IProductRepositoryPort::class)->get($product->id(), $merchantId);
    expect($persisted->status())->toBe(ProductStatus::Archived);
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(ArchiveProductHandler::class);

    $handler->handle(new ArchiveProductCommand(MerchantId::generate()->toString(), ProductId::generate()->toString()));
})->throws(ProductNotFound::class);

test('handle throws ProductNotFound when the product belongs to a different merchant', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand(MerchantId::generate()->toString(), 'Pro Plan'));
    $handler = app(ArchiveProductHandler::class);

    $handler->handle(new ArchiveProductCommand(MerchantId::generate()->toString(), $product->id()->toString()));
})->throws(ProductNotFound::class);

test('handle throws ProductAlreadyArchived when the product is already archived', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(ArchiveProductHandler::class);
    $handler->handle(new ArchiveProductCommand($merchantId->toString(), $product->id()->toString()));

    $handler->handle(new ArchiveProductCommand($merchantId->toString(), $product->id()->toString()));
})->throws(ProductAlreadyArchived::class);

test('handle records a ProductArchived integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(ArchiveProductHandler::class);

    $handler->handle(new ArchiveProductCommand($merchantId->toString(), $product->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $archived = collect($unpublished)->firstWhere('eventType', 'product.archived.v1');
    expect($archived)->not->toBeNull()
        ->and($archived->aggregateId)->toBe($product->id()->toString())
        ->and($archived->payload)->toBe(['product_id' => $product->id()->toString()]);
});
