<?php

namespace App\Application\Subscription\Queries\GetSubscription;

use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;

final class GetSubscriptionHandler
{
    public function __construct(private readonly ISubscriptionRepositoryPort $repository) {}

    public function handle(GetSubscriptionQuery $query): Subscription
    {
        return $this->repository->get(
            SubscriptionId::fromString($query->id),
            MerchantId::fromString($query->merchantId),
        );
    }
}
