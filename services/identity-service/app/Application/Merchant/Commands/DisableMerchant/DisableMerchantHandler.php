<?php

namespace App\Application\Merchant\Commands\DisableMerchant;

use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;

final class DisableMerchantHandler
{
    public function __construct(private readonly IMerchantRepositoryPort $repository) {}

    public function handle(DisableMerchantCommand $command): Merchant
    {
        $merchant = $this->repository->get(MerchantId::fromString($command->merchantId));

        $merchant->disable();

        $this->repository->save($merchant);

        return $merchant;
    }
}
