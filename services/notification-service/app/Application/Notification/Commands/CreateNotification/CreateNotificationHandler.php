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

    public function handle(CreateNotificationCommand $command): ?Notification
    {
        return $this->transaction->run(function () use ($command): ?Notification {
            if (! $this->inbox->recordIfNew($command->eventId, $command->eventType)) {
                return null;
            }

            // Inbox handles redelivery; the deduplication key handles distinct events for the same payment.
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
                // A concurrent delivery committed this notification first.
                return $this->repository->findByDeduplicationKey($command->deduplicationKey);
            }

            return $notification;
        });
    }
}
