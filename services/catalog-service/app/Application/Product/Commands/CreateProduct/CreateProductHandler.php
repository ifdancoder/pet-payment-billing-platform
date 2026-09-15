<?php

namespace App\Application\Product\Commands\CreateProduct;

use App\Application\Product\Ports\Outbound\IEventPublisherPort;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;

final class CreateProductHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $repository,
        private readonly IEventPublisherPort $eventPublisher,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        $product = Product::create(
            ProductId::generate(),
            ProductName::fromString($command->name),
        );

        $this->repository->save($product);
        $this->dispatch($product);

        return $product;
    }

    private function dispatch(Product $product): void
    {
        foreach ($product->pullRecordedEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
