<?php

namespace App\Application\Price\Commands\DeactivatePrice;

use App\Application\Price\IntegrationEvents\PriceDeactivatedIntegrationEvent;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Events\PriceDeactivated;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class DeactivatePriceHandler
{
    public function __construct(
        private readonly IPriceRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(DeactivatePriceCommand $command): Price
    {
        $price = $this->repository->get(PriceId::fromString($command->priceId));

        $price->deactivate();

        $this->transaction->run(function () use ($price): void {
            $this->repository->save($price);
            $this->recordIntegrationEvents($price);
        });

        return $price;
    }

    private function recordIntegrationEvents(Price $price): void
    {
        foreach ($price->pullRecordedEvents() as $event) {
            if ($event instanceof PriceDeactivated) {
                $this->outbox->add(PriceDeactivatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
