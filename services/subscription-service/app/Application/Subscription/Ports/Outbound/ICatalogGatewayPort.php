<?php

namespace App\Application\Subscription\Ports\Outbound;

use App\Application\Subscription\DataTransferObjects\PriceData;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface ICatalogGatewayPort
{
    public function findPrice(MerchantId $merchantId, PriceId $priceId): ?PriceData;
}
