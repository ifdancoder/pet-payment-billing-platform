<?php

use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\ArchiveProduct\ArchiveProductHandler;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Commands\UpdateProduct\UpdateProductCommand;
use App\Application\Product\Commands\UpdateProduct\UpdateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Exceptions\ArchivedProductCannotBeModified;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle renames an existing product', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    $handler = app(UpdateProductHandler::class);

    $updated = $handler->handle(new UpdateProductCommand($merchantId->toString(), $product->id()->toString(), 'Pro Plan v2'));

    expect($updated->name()->toString())->toBe('Pro Plan v2');
    $persisted = app(IProductRepositoryPort::class)->get($product->id(), $merchantId);
    expect($persisted->name()->toString())->toBe('Pro Plan v2');
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(UpdateProductHandler::class);

    $handler->handle(new UpdateProductCommand(MerchantId::generate()->toString(), ProductId::generate()->toString(), 'Pro Plan v2'));
})->throws(ProductNotFound::class);

test('handle throws ProductNotFound when the product belongs to a different merchant', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand(MerchantId::generate()->toString(), 'Pro Plan'));
    $handler = app(UpdateProductHandler::class);

    $handler->handle(new UpdateProductCommand(MerchantId::generate()->toString(), $product->id()->toString(), 'Pro Plan v2'));
})->throws(ProductNotFound::class);

test('handle throws ArchivedProductCannotBeModified when the product is archived', function () {
    $merchantId = MerchantId::generate();
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand($merchantId->toString(), 'Pro Plan'));
    app(ArchiveProductHandler::class)->handle(new ArchiveProductCommand($merchantId->toString(), $product->id()->toString()));
    $handler = app(UpdateProductHandler::class);

    $handler->handle(new UpdateProductCommand($merchantId->toString(), $product->id()->toString(), 'Pro Plan v2'));
})->throws(ArchivedProductCannotBeModified::class);
