<?php

use App\Application\Product\Commands\CreateProduct\CreateProductCommand;
use App\Application\Product\Commands\CreateProduct\CreateProductHandler;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Events\ProductCreated;
use Illuminate\Support\Facades\Log;

test('handle persists a new product with the given name', function () {
    $handler = app(CreateProductHandler::class);

    $product = $handler->handle(new CreateProductCommand('Pro Plan'));

    $persisted = app(IProductRepositoryPort::class)->get($product->id());
    expect($persisted->name()->toString())->toBe('Pro Plan');
});

test('handle publishes a ProductCreated event', function () {
    Log::spy();
    $handler = app(CreateProductHandler::class);

    $handler->handle(new CreateProductCommand('Pro Plan'));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, ProductCreated::class));
});
