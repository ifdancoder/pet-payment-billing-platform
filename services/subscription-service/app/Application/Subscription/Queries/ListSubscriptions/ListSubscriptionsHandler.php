<?php

namespace App\Application\Subscription\Queries\ListSubscriptions;

use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Subscription;
use App\Shared\Domain\ValueObjects\MerchantId;

final class ListSubscriptionsHandler
{
    public function __construct(private readonly ISubscriptionRepositoryPort $repository) {}

    /**
     * @return array<int, Subscription>
     */
    public function handle(ListSubscriptionsQuery $query): array
    {
        return $this->repository->all(MerchantId::fromString($query->merchantId));
    }
}
