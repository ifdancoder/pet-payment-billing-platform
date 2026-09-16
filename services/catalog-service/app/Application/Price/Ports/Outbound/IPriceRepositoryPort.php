<?php

namespace App\Application\Price\Ports\Outbound;

use App\Domain\Price\Exceptions\PriceNotFound;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface IPriceRepositoryPort
{
    public function save(Price $price): void;

    /**
     * @throws PriceNotFound
     */
    public function get(PriceId $id, MerchantId $merchantId): Price;
}
