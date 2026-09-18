<?php

namespace App\Application\Merchant\Commands\CreateMerchant;

use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;

final class CreateMerchantHandler
{
    public function __construct(private readonly IMerchantRepositoryPort $repository) {}

    public function handle(CreateMerchantCommand $command): Merchant
    {
        $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString($command->name));

        $this->repository->save($merchant);

        return $merchant;
    }
}
