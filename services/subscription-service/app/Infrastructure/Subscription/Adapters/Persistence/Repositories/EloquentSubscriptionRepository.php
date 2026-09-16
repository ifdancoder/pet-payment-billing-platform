<?php

namespace App\Infrastructure\Subscription\Adapters\Persistence\Repositories;

use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
use App\Domain\Subscription\Exceptions\SubscriptionNotFound;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Infrastructure\Subscription\Adapters\Persistence\Mappers\SubscriptionMapper;
use App\Infrastructure\Subscription\Adapters\Persistence\Models\SubscriptionModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class EloquentSubscriptionRepository implements ISubscriptionRepositoryPort
{
    public function __construct(private readonly SubscriptionMapper $mapper) {}

    public function save(Subscription $subscription): void
    {
        $model = SubscriptionModel::query()->find($subscription->id()->toString());

        $this->mapper->toModel($subscription, $model)->save();
    }

    public function get(SubscriptionId $id, MerchantId $merchantId): Subscription
    {
        $model = SubscriptionModel::query()
            ->where('id', $id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw SubscriptionNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function all(MerchantId $merchantId): array
    {
        return SubscriptionModel::query()
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (SubscriptionModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
