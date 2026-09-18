<?php

namespace App\Application\Merchant\Ports\Inbound;

use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantCommand;
use App\Application\Merchant\Commands\DisableMerchant\DisableMerchantCommand;
use App\Domain\Merchant\Merchant;

interface IMerchantServicePort
{
    public function createMerchant(CreateMerchantCommand $command): Merchant;

    public function disableMerchant(DisableMerchantCommand $command): Merchant;
}
