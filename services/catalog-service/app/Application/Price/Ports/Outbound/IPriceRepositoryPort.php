<?php

namespace App\Application\Price\Ports\Outbound;

use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;

interface IPriceRepositoryPort
{
    public function save(Price $price): void;

    /**
     * @throws PriceNotFound
     */
    public function get(PriceId $id): Price;
}
