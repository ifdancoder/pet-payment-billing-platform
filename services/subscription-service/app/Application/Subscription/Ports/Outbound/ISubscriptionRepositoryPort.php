<?php

namespace App\Application\Subscription\Ports\Outbound;

use App\Domain\Subscription\Exceptions\SubscriptionNotFound;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;

interface ISubscriptionRepositoryPort
{
    public function save(Subscription $subscription): void;

    /**
     * @throws SubscriptionNotFound
     */
    public function get(SubscriptionId $id, MerchantId $merchantId): Subscription;

    /**
     * @return array<int, Subscription>
     */
    public function all(MerchantId $merchantId): array;
}
