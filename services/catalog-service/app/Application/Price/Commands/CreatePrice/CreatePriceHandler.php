<?php

namespace App\Application\Price\Commands\CreatePrice;

use App\Application\Price\IntegrationEvents\PriceCreatedIntegrationEvent;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class CreatePriceHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $products,
        private readonly IPriceRepositoryPort $prices,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(CreatePriceCommand $command): Price
    {
        $productId = ProductId::fromString($command->productId);
        $this->products->get($productId);

        $billingPeriod = $command->billingInterval === null
            ? null
            : BillingPeriod::of(BillingInterval::from($command->billingInterval), $command->billingIntervalCount ?? 1);

        $price = Price::create(
            PriceId::generate(),
            $productId,
            Money::of($command->amountMinorUnits, Currency::from($command->currency)),
            PriceType::from($command->type),
            $billingPeriod,
        );

        $this->transaction->run(function () use ($price): void {
            $this->prices->save($price);
            $this->recordIntegrationEvents($price);
        });

        return $price;
    }

    private function recordIntegrationEvents(Price $price): void
    {
        foreach ($price->pullRecordedEvents() as $event) {
            if ($event instanceof PriceCreated) {
                $this->outbox->add(PriceCreatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
