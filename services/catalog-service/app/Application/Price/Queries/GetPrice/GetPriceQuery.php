<?php

namespace App\Application\Price\Queries\GetPrice;

final class GetPriceQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $id,
    ) {}
}
