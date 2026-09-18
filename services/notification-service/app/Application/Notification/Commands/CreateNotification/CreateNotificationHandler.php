<?php

namespace App\Application\Notification\Commands\CreateNotification;

use App\Application\Notification\Ports\Outbound\INotificationRepositoryPort;
use App\Domain\Notification\Notification;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Domain\Notification\ValueObjects\NotificationChannel;
use App\Domain\Notification\ValueObjects\NotificationId;
use App\Domain\Notification\ValueObjects\NotificationType;
use App\Shared\Application\Ports\Outbound\IInboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Database\UniqueConstraintViolationException;

final class CreateNotificationHandler
{
    public function __construct(
        private readonly INotificationRepositoryPort $repository,
        private readonly IInboxPort $inbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    /**
     * Records a Pending Notification with its content already rendered,
     * or returns null without doing anything when this exact event has
     * already been processed (an at-least-once redelivery of
     * payment.succeeded.v1).
     *
     * No outbox write here: this service publishes nothing downstream —
     * nothing subscribes to "a notification was created/sent" today.
     * Delivery itself is a separate, independently-scalable worker
     * (DeliverNotificationsCommand) that polls for Pending notifications
     * rather than being chained onto this handler.
     */
    public function handle(CreateNotificationCommand $command): ?Notification
    {
        return $this->transaction->run(function () use ($command): ?Notification {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            // A second, different event for the same underlying business
            // fact must not create a second notification either —
            // business idempotency on top of the Inbox's event-id dedup.
            $existing = $this->repository->findByDeduplicationKey($command->deduplicationKey);
            if ($existing !== null) {
                return $existing;
            }

            $notification = Notification::create(
                NotificationId::generate(),
                MerchantId::fromString($command->merchantId),
                $command->eventId,
                NotificationType::from($command->type),
                NotificationChannel::from($command->channel),
                EmailAddress::fromString($command->recipientEmail),
                $command->subject,
                $command->bodyText,
                $command->bodyHtml,
                $command->deduplicationKey,
            );

            try {
                $this->repository->save($notification);
            } catch (UniqueConstraintViolationException) {
                // Lost a race with a concurrent delivery for the same
                // deduplication key — the winner's notification is the truth.
                return $this->repository->findByDeduplicationKey($command->deduplicationKey);
            }

            return $notification;
        });
    }
}
