<?php

namespace App\Application\Price\Commands\DeactivatePrice;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;

final class DeactivatePriceHandler
{
    public function __construct(private readonly IPriceRepositoryPort $repository) {}

    public function handle(DeactivatePriceCommand $command): Price
    {
        $price = $this->repository->get(PriceId::fromString($command->priceId));

        $price->deactivate();

        $this->repository->save($price);

        return $price;
    }
}
