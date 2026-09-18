<?php

namespace App\Infrastructure\Notification\Adapters\Persistence\Repositories;

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\Exceptions\NotificationNotFound;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Infrastructure\Notification\Adapters\Persistence\Mappers\NotificationMapper;
use App\Infrastructure\Notification\Adapters\Persistence\Models\NotificationModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class EloquentNotificationRepository implements INotificationRepositoryPort
{
    public function __construct(private readonly NotificationMapper $mapper) {}

    public function save(Notification $notification): void
    {
        $model = NotificationModel::query()->with('attempts')->find($notification->id()->toString());
        $model = $this->mapper->toModel($notification, $model);
        $model->save();

        $this->persistAttempts($model, $notification);
    }

    public function get(NotificationId $id, MerchantId $merchantId): Notification
    {
        $model = NotificationModel::query()
            ->with('attempts')
            ->where('id', $id->toString())
            ->where('merchant_id', $merchantId->toString())
            ->first();

        if ($model === null) {
            throw NotificationNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function all(MerchantId $merchantId): array
    {
        return NotificationModel::query()
            ->with('attempts')
            ->where('merchant_id', $merchantId->toString())
            ->get()
            ->map(fn (NotificationModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function findByDeduplicationKey(string $deduplicationKey): ?Notification
    {
        $model = NotificationModel::query()
            ->with('attempts')
            ->where('deduplication_key', $deduplicationKey)
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    /**
     * Unlike an Invoice's lines, a DeliveryAttempt is not immutable once
     * created — it starts Pending and later transitions to Succeeded or
     * Failed — so every attempt is an upsert, not an insert-only append.
     */
    private function persistAttempts(NotificationModel $model, Notification $notification): void
    {
        foreach ($notification->attempts() as $attempt) {
            $model->attempts()->updateOrCreate(
                ['id' => $attempt->id()->toString()],
                [
                    'provider' => $attempt->provider(),
                    'provider_reference' => $attempt->providerReference()?->toString(),
                    'status' => $attempt->status()->value,
                    'failure_code' => $attempt->failureCode(),
                    'failure_message' => $attempt->failureMessage(),
                    'started_at' => $attempt->startedAt(),
                    'completed_at' => $attempt->completedAt(),
                ],
            );
        }
    }
}
