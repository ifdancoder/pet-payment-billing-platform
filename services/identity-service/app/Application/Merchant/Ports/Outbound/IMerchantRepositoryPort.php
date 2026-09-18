<?php

namespace App\Application\Merchant\Ports\Outbound;

use App\Domain\Merchant\Exceptions\MerchantNotFound;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;

interface IMerchantRepositoryPort
{
    public function save(Merchant $merchant): void;

    /**
     * @throws MerchantNotFound
     */
    public function get(MerchantId $id): Merchant;
}
