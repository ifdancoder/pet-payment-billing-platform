<?php

namespace App\Application\Merchant;

use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantCommand;
use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantHandler;
use App\Application\Merchant\Commands\DisableMerchant\DisableMerchantCommand;
use App\Application\Merchant\Commands\DisableMerchant\DisableMerchantHandler;
use App\Application\Merchant\Ports\Inbound\IMerchantServicePort;
use App\Domain\Merchant\Merchant;

final class MerchantService implements IMerchantServicePort
{
    public function __construct(
        private readonly CreateMerchantHandler $createMerchantHandler,
        private readonly DisableMerchantHandler $disableMerchantHandler,
    ) {}

    public function createMerchant(CreateMerchantCommand $command): Merchant
    {
        return $this->createMerchantHandler->handle($command);
    }

    public function disableMerchant(DisableMerchantCommand $command): Merchant
    {
        return $this->disableMerchantHandler->handle($command);
    }
}
