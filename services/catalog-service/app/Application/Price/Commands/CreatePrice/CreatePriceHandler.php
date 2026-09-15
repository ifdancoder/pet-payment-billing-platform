<?php

namespace App\Application\Price\Commands\CreatePrice;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Application\Product\Ports\Outbound\IProductRepositoryPort;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;

final class CreatePriceHandler
{
    public function __construct(
        private readonly IProductRepositoryPort $products,
        private readonly IPriceRepositoryPort $prices,
    ) {}

    public function handle(CreatePriceCommand $command): Price
    {
        $productId = ProductId::fromString($command->productId);
        $this->products->get($productId);

        $price = Price::create(
            PriceId::generate(),
            $productId,
            Money::of($command->amountMinorUnits, Currency::from($command->currency)),
            BillingInterval::from($command->billingInterval),
        );

        $this->prices->save($price);

        return $price;
    }
}
