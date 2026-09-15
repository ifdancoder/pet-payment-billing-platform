<?php

namespace App\Application\Product\Commands\ArchiveProduct;

use App\Application\Product\Ports\Outbound\IEventPublisherPort;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;

final class ArchiveProductHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $repository,
        private readonly IEventPublisherPort $eventPublisher,
    ) {}

    public function handle(ArchiveProductCommand $command): Product
    {
        $product = $this->repository->get(ProductId::fromString($command->productId));

        $product->archive();

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
