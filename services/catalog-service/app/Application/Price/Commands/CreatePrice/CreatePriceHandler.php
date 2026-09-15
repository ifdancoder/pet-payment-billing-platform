<?php

namespace App\Application\Price\Commands\CreatePrice;

use App\Application\Price\Ports\Outbound\IEventPublisherPort;
use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;

final class CreatePriceHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $products,
        private readonly IPriceRepositoryPort $prices,
        private readonly IEventPublisherPort $eventPublisher,
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

        $this->prices->save($price);
        $this->dispatch($price);

        return $price;
    }

    private function dispatch(Price $price): void
    {
        foreach ($price->pullRecordedEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
