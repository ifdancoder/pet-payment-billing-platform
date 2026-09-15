<?php

namespace App\Application\Product\Commands\ArchiveProduct;

use App\Application\Product\IntegrationEvents\ProductArchivedIntegrationEvent;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Product\Events\ProductArchived;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class ArchiveProductHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(ArchiveProductCommand $command): Product
    {
        $product = $this->repository->get(ProductId::fromString($command->productId));

        $product->archive();

        $this->transaction->run(function () use ($product): void {
            $this->repository->save($product);
            $this->recordIntegrationEvents($product);
        });

        return $product;
    }

    private function recordIntegrationEvents(Product $product): void
    {
        foreach ($product->pullRecordedEvents() as $event) {
            if ($event instanceof ProductArchived) {
                $this->outbox->add(ProductArchivedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
