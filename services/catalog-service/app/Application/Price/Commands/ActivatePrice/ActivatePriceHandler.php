<?php

namespace App\Application\Price\Commands\ActivatePrice;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;

final class ActivatePriceHandler
{
    public function __construct(private readonly IPriceRepositoryPort $repository) {}

    public function handle(ActivatePriceCommand $command): Price
    {
        $price = $this->repository->get(PriceId::fromString($command->priceId));

        $price->activate();

        $this->repository->save($price);

        return $price;
    }
}
