<?php

namespace App\Application\Price\Queries\GetPrice;

use App\Application\Price\Ports\Outbound\IPriceRepositoryPort;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;

final class GetPriceHandler
{
    public function __construct(private readonly IPriceRepositoryPort $repository) {}

    public function handle(GetPriceQuery $query): Price
    {
        return $this->repository->get(PriceId::fromString($query->id));
    }
}
