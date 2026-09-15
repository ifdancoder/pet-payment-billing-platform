<?php

namespace App\Application\Product\Commands\CreateProduct;

use App\Application\Product\IntegrationEvents\ProductCreatedIntegrationEvent;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class CreateProductHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        $product = Product::create(
            ProductId::generate(),
            ProductName::fromString($command->name),
        );

        $this->transaction->run(function () use ($product): void {
            $this->repository->save($product);
            $this->recordIntegrationEvents($product);
        });

        return $product;
    }

    private function recordIntegrationEvents(Product $product): void
    {
        foreach ($product->pullRecordedEvents() as $event) {
            if ($event instanceof ProductCreated) {
                $this->outbox->add(ProductCreatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
