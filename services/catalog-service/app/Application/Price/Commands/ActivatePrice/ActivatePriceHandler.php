<?php

namespace App\Application\Price\Commands\ActivatePrice;

use App\Application\Price\IntegrationEvents\PriceActivatedIntegrationEvent;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Events\PriceActivated;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ActivatePriceHandler
{
    public function __construct(
        private readonly IPriceRepositoryPort $repository,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(ActivatePriceCommand $command): Price
    {
        $price = $this->repository->get(
            PriceId::fromString($command->priceId),
            MerchantId::fromString($command->merchantId),
        );

        $price->activate();

        $this->transaction->run(function () use ($price): void {
            $this->repository->save($price);
            $this->recordIntegrationEvents($price);
        });

        return $price;
    }

    private function recordIntegrationEvents(Price $price): void
    {
        foreach ($price->pullRecordedEvents() as $event) {
            if ($event instanceof PriceActivated) {
                $this->outbox->add(PriceActivatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
