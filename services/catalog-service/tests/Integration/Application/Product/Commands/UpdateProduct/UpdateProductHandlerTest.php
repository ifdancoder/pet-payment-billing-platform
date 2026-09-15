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

test('handle renames an existing product', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    $handler = app(UpdateProductHandler::class);

    $updated = $handler->handle(new UpdateProductCommand($product->id()->toString(), 'Pro Plan v2'));

    expect($updated->name()->toString())->toBe('Pro Plan v2');
    $persisted = app(IProductRepositoryPort::class)->get($product->id());
    expect($persisted->name()->toString())->toBe('Pro Plan v2');
});

test('handle throws ProductNotFound when the product does not exist', function () {
    $handler = app(UpdateProductHandler::class);

    $handler->handle(new UpdateProductCommand(ProductId::generate()->toString(), 'Pro Plan v2'));
})->throws(ProductNotFound::class);

test('handle throws ArchivedProductCannotBeModified when the product is archived', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    app(ArchiveProductHandler::class)->handle(new ArchiveProductCommand($product->id()->toString()));
    $handler = app(UpdateProductHandler::class);

    $handler->handle(new UpdateProductCommand($product->id()->toString(), 'Pro Plan v2'));
})->throws(ArchivedProductCannotBeModified::class);
