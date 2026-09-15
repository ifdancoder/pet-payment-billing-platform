<?php

namespace App\Application\Price;

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Ports\Inbound\IPriceServicePort;
use App\Application\Price\Queries\GetPrice\GetPriceHandler;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Domain\Price\Price;

final class PriceService implements IPriceServicePort
{
    public function __construct(
        private readonly CreatePriceHandler $createPriceHandler,
        private readonly GetPriceHandler $getPriceHandler,
    ) {}

    public function createPrice(CreatePriceCommand $command): Price
    {
        return $this->createPriceHandler->handle($command);
    }

    public function getPrice(GetPriceQuery $query): Price
    {
        return $this->getPriceHandler->handle($query);
    }
}
