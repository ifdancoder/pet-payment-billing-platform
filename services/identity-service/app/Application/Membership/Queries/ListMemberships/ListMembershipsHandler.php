<?php

namespace App\Application\Membership\Queries\ListMemberships;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Domain\Membership\Membership;
use App\Domain\Merchant\ValueObjects\MerchantId;

final class ListMembershipsHandler
{
    public function __construct(private readonly IMembershipRepositoryPort $repository) {}

    /**
     * @return array<int, Membership>
     */
    public function handle(ListMembershipsQuery $query): array
    {
        return $this->repository->listByMerchant(MerchantId::fromString($query->merchantId));
    }
}
