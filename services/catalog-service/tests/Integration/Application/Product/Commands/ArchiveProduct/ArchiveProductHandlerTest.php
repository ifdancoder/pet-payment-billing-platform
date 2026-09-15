<?php

use App\Application\Product\Commands\ArchiveProduct\ArchiveProductCommand;
use App\Application\Product\Commands\ArchiveProduct\ArchiveProductHandler;
use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Events\ProductArchived;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\Exceptions\ProductNotFound;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductStatus;
use Illuminate\Support\Facades\Log;

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

test('handle publishes a ProductArchived event', function () {
    $product = app(CreateProductHandler::class)->handle(new CreateProductCommand('Pro Plan'));
    Log::spy();
    $handler = app(ArchiveProductHandler::class);

    $handler->handle(new ArchiveProductCommand($product->id()->toString()));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, ProductArchived::class));
});
