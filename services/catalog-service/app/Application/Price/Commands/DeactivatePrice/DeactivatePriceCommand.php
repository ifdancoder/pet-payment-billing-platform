<?php

namespace App\Application\Price\Commands\DeactivatePrice;

final class DeactivatePriceCommand
{
    public function __construct(public readonly string $priceId) {}
}
