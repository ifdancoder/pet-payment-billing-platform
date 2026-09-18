<?php

namespace App\Infrastructure\Notification\Adapters\Persistence\Mappers;

use App\Domain\Notification\DeliveryAttempt;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\DeliveryAttemptStatus;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationStatus;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Domain\Notification\ValueObjects\ProviderReference;
use App\Infrastructure\Notification\Adapters\Persistence\Models\NotificationDeliveryAttemptModel;
use App\Infrastructure\Notification\Adapters\Persistence\Models\NotificationModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class NotificationMapper
{
    public function toDomain(NotificationModel $model): Notification
    {
        $attempts = $model->attempts
            ->map(fn (NotificationDeliveryAttemptModel $attempt) => DeliveryAttempt::reconstitute(
                DeliveryAttemptId::fromString($attempt->id),
                $attempt->provider,
                $attempt->provider_reference === null ? null : ProviderReference::of($attempt->provider_reference),
                DeliveryAttemptStatus::from($attempt->status),
                $attempt->failure_code,
                $attempt->failure_message,
                $attempt->started_at->toDateTimeImmutable(),
                $attempt->completed_at?->toDateTimeImmutable(),
            ))
            ->all();

        return Notification::reconstitute(
            NotificationId::fromString($model->id),
            MerchantId::fromString($model->merchant_id),
            $model->source_event_id,
            NotificationType::from($model->type),
            NotificationChannel::from($model->channel),
            EmailAddress::fromString($model->recipient),
            $model->subject,
            $model->body_text,
            $model->body_html,
            $model->deduplication_key,
            NotificationStatus::from($model->status),
            $attempts,
            $model->sent_at?->toDateTimeImmutable(),
            $model->failed_at?->toDateTimeImmutable(),
        );
    }

    public function toModel(Notification $notification, ?NotificationModel $model = null): NotificationModel
    {
        $model ??= new NotificationModel;

        $model->id = $notification->id()->toString();
        $model->merchant_id = $notification->merchantId()->toString();
        $model->source_event_id = $notification->sourceEventId();
        $model->type = $notification->type()->value;
        $model->channel = $notification->channel()->value;
        $model->recipient = $notification->recipient()->toString();
        $model->subject = $notification->subject();
        $model->body_text = $notification->bodyText();
        $model->body_html = $notification->bodyHtml();
        $model->status = $notification->status()->value;
        $model->deduplication_key = $notification->deduplicationKey();
        $model->sent_at = $notification->sentAt();
        $model->failed_at = $notification->failedAt();

        return $model;
    }
}
