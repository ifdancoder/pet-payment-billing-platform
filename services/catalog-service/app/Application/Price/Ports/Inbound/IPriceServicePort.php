<?php

namespace App\Application\Price\Ports\Inbound;

use App\Application\Price\Commands\ActivatePrice\ActivatePriceCommand;
use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Commands\DeactivatePrice\DeactivatePriceCommand;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Domain\Price\Price;

interface IPriceServicePort
{
    public function createPrice(CreatePriceCommand $command): Price;

    public function getPrice(GetPriceQuery $query): Price;

    public function activatePrice(ActivatePriceCommand $command): Price;

    public function deactivatePrice(DeactivatePriceCommand $command): Price;
}
