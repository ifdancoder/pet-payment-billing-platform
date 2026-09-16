<?php

namespace App\Application\Price\Commands\ActivatePrice;

final class ActivatePriceCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $priceId,
    ) {}
}
