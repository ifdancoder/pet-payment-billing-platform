<?php

namespace App\Application\Merchant\Commands\DisableMerchant;

final class DisableMerchantCommand
{
    public function __construct(public readonly string $merchantId) {}
}
