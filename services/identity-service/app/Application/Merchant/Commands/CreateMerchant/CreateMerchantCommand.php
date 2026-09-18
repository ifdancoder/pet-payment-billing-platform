<?php

namespace App\Application\Merchant\Commands\CreateMerchant;

final class CreateMerchantCommand
{
    public function __construct(public readonly string $name) {}
}
