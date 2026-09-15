<?php

namespace App\Application\Price;

use App\Application\Price\Commands\ActivatePrice\ActivatePriceCommand;
use App\Application\Price\Commands\ActivatePrice\ActivatePriceHandler;
use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceHandler;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceCommand;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceHandler;
use App\Application\Price\Ports\Inbound\IPriceServicePort;
use App\Application\Price\Queries\GetPrice\GetPriceHandler;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Domain\Price\Price;

final class PriceService implements IPriceServicePort
{
    public function __construct(
        private readonly CreatePriceHandler $createPriceHandler,
        private readonly GetPriceHandler $getPriceHandler,
        private readonly ActivatePriceHandler $activatePriceHandler,
        private readonly DeactivatePriceHandler $deactivatePriceHandler,
    ) {}

    public function createPrice(CreatePriceCommand $command): Price
    {
        return $this->createPriceHandler->handle($command);
    }

    public function getPrice(GetPriceQuery $query): Price
    {
        return $this->getPriceHandler->handle($query);
    }

    public function activatePrice(ActivatePriceCommand $command): Price
    {
        return $this->activatePriceHandler->handle($command);
    }

    public function deactivatePrice(DeactivatePriceCommand $command): Price
    {
        return $this->deactivatePriceHandler->handle($command);
    }
}
